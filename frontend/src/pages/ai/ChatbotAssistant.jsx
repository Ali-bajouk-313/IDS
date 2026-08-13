import { useEffect, useMemo, useRef, useState } from "react";
import { FiAlertCircle, FiMessageCircle, FiRefreshCw, FiSend, FiShield, FiZap } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import aiService from "../../services/aiService.js";

const starterPrompts = [
  "How can I troubleshoot a network problem?",
  "What should I do if my ticket is still unresolved?",
  "Summarize my recent ticket.",
  "What are the common causes of a printer problem?",
];

function ChatbotAssistant() {
  const user = JSON.parse(localStorage.getItem("user") || "{}") || {};
  const role = user.role || "Employee";

  const initialMessages = useMemo(() => ([
    {
      id: "welcome",
      role: "assistant",
      content: "I can help with HelpDeskPro tickets, troubleshooting, and support questions. Ask about a ticket you can access or describe a technical issue.",
    },
  ]), []);

  const [messages, setMessages] = useState(initialMessages);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const messageListRef = useRef(null);

  useEffect(() => {
    if (messageListRef.current) {
      messageListRef.current.scrollTop = messageListRef.current.scrollHeight;
    }
  }, [messages, loading]);

  const clearConversation = () => {
    setMessages(initialMessages);
    setInput("");
    setError("");
  };

  const handleStarterPrompt = (prompt) => {
    setInput(prompt);
    setError("");
  };

  const handleSend = async () => {
    const trimmedMessage = input.trim();

    if (!trimmedMessage || loading) {
      return;
    }

    const conversationForRequest = messages.slice(-6).map(({ role: messageRole, content }) => ({
      role: messageRole,
      content,
    }));

    const userMessage = {
      id: `user-${Date.now()}`,
      role: "user",
      content: trimmedMessage,
    };

    setMessages((current) => [...current, userMessage]);
    setInput("");
    setLoading(true);
    setError("");

    try {
      const response = await aiService.sendChatMessage(trimmedMessage, conversationForRequest);
      const responseConversation = response.data?.data?.conversation;

      if (Array.isArray(responseConversation) && responseConversation.length > 0) {
        setMessages(responseConversation.map((message, index) => ({
          id: `${message.role}-${index}-${Date.now()}`,
          role: message.role,
          content: message.content,
        })));
      } else {
        setMessages((current) => [...current, {
          id: `assistant-${Date.now()}`,
          role: "assistant",
          content: response.data?.data?.answer || "I could not generate a response.",
        }]);
      }
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to process the chat request.");
    } finally {
      setLoading(false);
    }
  };

  const handleKeyDown = (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      handleSend();
    }
  };

  return (
    <DashboardLayout role={role} title="AI Chatbot" subtitle="An authenticated HelpDeskPro assistant for tickets, troubleshooting, and support guidance.">
      <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div className="rounded-[1.75rem] border border-slate-200 bg-white/90 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-indigo-700">HelpDeskPro AI Assistant</p>
              <h2 className="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Conversational support for your workspace</h2>
              <p className="mt-2 text-sm text-slate-500">Use the chatbot for ticket-aware help, technical troubleshooting, and support guidance that respects your permissions.</p>
            </div>
            <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
              <FiShield />
              Authenticated
            </div>
          </div>

          <div ref={messageListRef} className="max-h-[66vh] overflow-y-auto px-6 py-6">
            <div className="space-y-4">
              {messages.map((message) => (
                <div key={message.id} className={`flex ${message.role === "user" ? "justify-end" : "justify-start"}`}>
                  <div className={`max-w-[85%] rounded-[1.4rem] px-4 py-3 text-sm leading-7 shadow-sm ${message.role === "user" ? "bg-indigo-600 text-white" : "border border-slate-200 bg-slate-50 text-slate-700"}`}>
                    <div className="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] opacity-80">
                      {message.role === "user" ? <FiMessageCircle /> : <FiZap />}
                      {message.role === "user" ? "You" : "AI Assistant"}
                    </div>
                    <p className="whitespace-pre-line">{message.content}</p>
                  </div>
                </div>
              ))}

              {loading ? (
                <div className="flex justify-start">
                  <div className="border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm rounded-[1.4rem]">
                    <div className="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                      <FiZap />
                      AI Assistant
                    </div>
                    <div className="flex items-center gap-1">
                      <span className="h-2 w-2 animate-pulse rounded-full bg-slate-400" />
                      <span className="h-2 w-2 animate-pulse rounded-full bg-slate-400 [animation-delay:120ms]" />
                      <span className="h-2 w-2 animate-pulse rounded-full bg-slate-400 [animation-delay:240ms]" />
                    </div>
                  </div>
                </div>
              ) : null}
            </div>
          </div>

          <div className="border-t border-slate-200 px-6 py-5">
            {error ? (
              <div className="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {error}
              </div>
            ) : null}

            <div className="space-y-3">
              <textarea
                value={input}
                onChange={(event) => setInput(event.target.value)}
                onKeyDown={handleKeyDown}
                rows={3}
                placeholder="Ask a HelpDeskPro question..."
                className="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100"
              />

              <div className="flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={handleSend}
                  disabled={loading || !input.trim()}
                  className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                >
                  <FiSend />
                  {loading ? "Sending…" : "Send"}
                </button>
                <button
                  type="button"
                  onClick={clearConversation}
                  className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                  <FiRefreshCw />
                  Clear Conversation
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="space-y-6">
          <div className="rounded-[1.5rem] border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <FiMessageCircle className="text-indigo-600" />
              Quick Prompts
            </div>
            <p className="mt-2 text-sm text-slate-500">Choose a starter question to begin without sending anything automatically.</p>
            <div className="mt-4 space-y-3">
              {starterPrompts.map((prompt) => (
                <button
                  key={prompt}
                  type="button"
                  onClick={() => handleStarterPrompt(prompt)}
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50"
                >
                  {prompt}
                </button>
              ))}
            </div>
          </div>

          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <FiAlertCircle className="text-amber-600" />
              Guardrails
            </div>
            <ul className="mt-4 space-y-3 text-sm leading-6 text-slate-600">
              <li>Only accessible ticket data is included in the AI context.</li>
              <li>Private tickets, comments, and history stay behind existing RBAC rules.</li>
              <li>Conversation state lives only in the current browser session.</li>
              <li>AI provider keys remain on the backend only.</li>
            </ul>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}

export default ChatbotAssistant;
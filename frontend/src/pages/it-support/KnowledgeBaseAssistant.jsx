import { useState } from "react";
import { FiBookOpen, FiRefreshCw, FiSearch, FiShield, FiZap } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import aiService from "../../services/aiService.js";

function KnowledgeBaseAssistant() {
  const [question, setQuestion] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [answerData, setAnswerData] = useState(null);

  const handleSubmit = async (event) => {
    event.preventDefault();

    const trimmedQuestion = question.trim();

    if (!trimmedQuestion) {
      setError("Enter a technical question before asking AI.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      const response = await aiService.askKnowledgeBaseQuestion(trimmedQuestion);
      const payload = response.data?.data || response.data || {};
      setAnswerData(payload);
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to answer the knowledge base question.");
    } finally {
      setLoading(false);
    }
  };

  const handleReset = () => {
    setQuestion("");
    setError("");
    setAnswerData(null);
  };

  const sourceContext = answerData?.sourceContext || {};
  const categories = sourceContext.categories || [];
  const tickets = sourceContext.tickets || [];
  const comments = sourceContext.comments || [];
  const keywords = sourceContext.keywords || [];
  const troubleshootingSteps = answerData?.troubleshootingSteps || [];

  return (
    <DashboardLayout
      role="IT Support"
      title="Knowledge Base Assistant"
      subtitle="Ask technical questions and reuse live support context from HelpDeskPro records."
    >
      <div className="space-y-6">
        <div className="rounded-[1.75rem] border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div className="max-w-3xl">
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-indigo-700">AI Knowledge Base</p>
              <h2 className="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Technical guidance from live HelpDeskPro context</h2>
              <p className="mt-2 text-sm text-slate-500">
                Ask a support question and the assistant will look at categories, resolved tickets, and comments before responding.
              </p>
            </div>
            <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
              <div className="flex items-center gap-2 font-semibold text-slate-900">
                <FiShield className="text-indigo-600" />
                Restricted to support roles
              </div>
              <p className="mt-1 text-xs text-slate-500">Uses the backend AI provider stack and does not expose keys to the browser.</p>
            </div>
          </div>
        </div>

        <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label htmlFor="knowledge-base-question" className="mb-2 block text-sm font-semibold text-slate-700">
                  Question
                </label>
                <textarea
                  id="knowledge-base-question"
                  value={question}
                  onChange={(event) => setQuestion(event.target.value)}
                  rows={8}
                  placeholder="Example: How do I troubleshoot a VPN connection that keeps dropping after a laptop update?"
                  className="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                />
              </div>

              {error ? <div className="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

              <div className="flex flex-wrap gap-3">
                <button
                  type="submit"
                  disabled={loading || !question.trim()}
                  className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                >
                  {loading ? <FiZap className="animate-pulse" /> : <FiSearch />}
                  {loading ? "Asking AI…" : "Ask AI"}
                </button>
                <button
                  type="button"
                  onClick={handleReset}
                  className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                  <FiRefreshCw />
                  Clear
                </button>
              </div>
            </form>
          </div>

          <div className="space-y-6">
            <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
              <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
                <FiBookOpen className="text-indigo-600" />
                Answer
              </div>
              {answerData?.answer ? (
                <div className="mt-4 space-y-4">
                  <p className="whitespace-pre-line text-sm leading-7 text-slate-700">{answerData.answer}</p>

                  {troubleshootingSteps.length > 0 ? (
                    <div>
                      <p className="text-sm font-semibold text-slate-900">Troubleshooting steps</p>
                      <ol className="mt-3 space-y-3">
                        {troubleshootingSteps.map((step, index) => (
                          <li key={`${step}-${index}`} className="flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                              {index + 1}
                            </span>
                            <span>{step}</span>
                          </li>
                        ))}
                      </ol>
                    </div>
                  ) : null}
                </div>
              ) : (
                <div className="mt-4 rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-sm text-slate-600">
                  Ask a question to receive a grounded support answer.
                </div>
              )}
            </div>

            <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
              <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
                <FiBookOpen className="text-sky-600" />
                Source Context
              </div>
              {answerData ? (
                <div className="mt-4 space-y-4 text-sm text-slate-700">
                  <div className="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <p className="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Question</p>
                    <p className="mt-2 text-sm text-slate-800">{sourceContext.question || answerData.question || question}</p>
                    {keywords.length > 0 ? <p className="mt-2 text-xs text-slate-500">Keywords: {keywords.join(", ")}</p> : null}
                  </div>

                  {categories.length > 0 ? (
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Categories</p>
                      <ul className="mt-2 space-y-2">
                        {categories.map((category) => (
                          <li key={category.id} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p className="font-semibold text-slate-900">{category.name}</p>
                            {category.description ? <p className="mt-1 text-xs text-slate-500">{category.description}</p> : null}
                          </li>
                        ))}
                      </ul>
                    </div>
                  ) : null}

                  {tickets.length > 0 ? (
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Related tickets</p>
                      <ul className="mt-2 space-y-2">
                        {tickets.map((ticket) => (
                          <li key={ticket.id} className="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p className="font-semibold text-slate-900">{ticket.ticketNumber}</p>
                            <p className="mt-1 text-xs text-slate-500">{ticket.title}</p>
                            <p className="mt-1 text-xs text-slate-500">{ticket.category} • {ticket.status} • {ticket.priority}</p>
                          </li>
                        ))}
                      </ul>
                    </div>
                  ) : null}

                  {comments.length > 0 ? (
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recent comments</p>
                      <ul className="mt-2 space-y-2">
                        {comments.map((comment, index) => (
                          <li key={`${comment.ticketNumber}-${index}`} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p className="text-xs font-semibold text-slate-900">{comment.ticketNumber} • {comment.author}</p>
                            <p className="mt-1 text-xs leading-6 text-slate-600">{comment.commentText}</p>
                          </li>
                        ))}
                      </ul>
                    </div>
                  ) : null}

                  {categories.length === 0 && tickets.length === 0 && comments.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                      No matching categories, resolved tickets, or comments were found for this question.
                    </div>
                  ) : null}
                </div>
              ) : (
                <div className="mt-4 rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-sm text-slate-600">
                  Context will appear here after you ask a question.
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}

export default KnowledgeBaseAssistant;
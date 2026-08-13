import api from "../api/axios.js";

const aiService = {
  askKnowledgeBaseQuestion(question) {
    return api.post("/ai/knowledge-base/ask", { question }, { skipSuccessToast: true });
  },

  sendChatMessage(message, conversation = []) {
    return api.post("/ai/chat", { message, conversation }, { skipSuccessToast: true });
  },
};

export default aiService;
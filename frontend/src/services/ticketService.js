import api from "../api/axios.js";

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");

  if (token) {
    config.headers = config.headers || {};
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

const ticketService = {
  getTickets(filters = {}) {
    const params = {};

    Object.entries(filters).forEach(([key, value]) => {
      if (value) {
        params[key] = value;
      }
    });

    return api.get("/tickets", { params });
  },

  getTicketById(id) {
    return api.get(`/tickets/${id}`);
  },

  createTicket(data) {
    return api.post("/tickets", data);
  },

  updateTicket(id, data) {
    return api.put(`/tickets/${id}`, data);
  },

  deleteTicket(id) {
    return api.delete(`/tickets/${id}`);
  },

  getCategories() {
    return api.get("/categories");
  },
};

export default ticketService;

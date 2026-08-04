import api from "../api/axios.js";

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

  getTicketComments(id) {
    return api.get(`/tickets/${id}/comments`);
  },

  getTicketHistory(id) {
    return api.get(`/tickets/${id}/history`);
  },

  addTicketComment(id, commentText) {
    return api.post(`/tickets/${id}/comments`, { commentText });
  },

  getInternalNotes(id) {
    return api.get(`/tickets/${id}/internal-notes`);
  },

  addInternalNote(id, note) {
    return api.post(`/tickets/${id}/internal-notes`, { note });
  },

  deleteInternalNote(id) {
    return api.delete(`/internal-notes/${id}`);
  },

  createTicket(data) {
    return api.post("/tickets", data);
  },

  updateTicket(id, data) {
    return api.put(`/tickets/${id}`, data);
  },

  updateTicketStatus(id, data) {
    return api.put(`/tickets/${id}/status`, data);
  },

  deleteTicket(id) {
    return api.delete(`/tickets/${id}`);
  },

  getCategories() {
    return api.get("/categories");
  },

  assignTicket(id, assignedTo) {
    return api.post(`/tickets/${id}/assign`, { assigned_to: assignedTo });
  },

  unassignTicket(id) {
    return api.post(`/tickets/${id}/unassign`);
  },

  getMyAssignedTickets(filters = {}) {
    const params = {};

    Object.entries(filters).forEach(([key, value]) => {
      if (value) {
        params[key] = value;
      }
    });

    return api.get("/tickets/my-assigned", { params });
  },

  getActivityLogs() {
    return api.get('/activity-logs');
  },
};

export default ticketService;

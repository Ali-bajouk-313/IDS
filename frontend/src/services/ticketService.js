import api from "../api/axios.js";

const notificationService = {
  getNotifications() {
    return api.get("/notifications");
  },

  getUnreadNotificationCount() {
    return api.get("/notifications").then((response) => response?.data?.unread_count ?? 0);
  },

  markNotificationAsRead(id) {
    return api.post(`/notifications/${id}/read`);
  },

  markAllNotificationsAsRead() {
    return api.get("/notifications").then(async (response) => {
      const notifications = response?.data?.notifications || [];
      await Promise.all(notifications.filter((item) => !item.read).map((item) => api.post(`/notifications/${item.id}/read`)));
      return response;
    });
  },
};

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

  getAttachments(id) {
    return api.get(`/tickets/${id}/attachments`);
  },

  uploadAttachment(id, file) {
    const formData = new FormData();
    formData.append("file", file);

    return api.post(`/tickets/${id}/attachments`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },

  deleteAttachment(id) {
    return api.delete(`/attachments/${id}`);
  },

  downloadAttachment(id) {
    return api.get(`/attachments/${id}/download`, { responseType: "blob" });
  },

  createTicket(data) {
    if (data && data.file) {
      const formData = new FormData();
      formData.append('title', data.title);
      formData.append('description', data.description);
      formData.append('categoryId', data.categoryId);
      formData.append('priority', data.priority);
      formData.append('file', data.file);

      return api.post("/tickets", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    }

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

  returnTicketToAdmin(id, reason) {
    return api.post(`/tickets/${id}/return-to-admin`, { reason });
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

export { notificationService };
export default ticketService;

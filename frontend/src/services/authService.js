import api from "../api/axios";

const authService = {
  login(credentials) {
    return api.post("/login", credentials);
  },

  register(data) {
    return api.post("/register", data);
  },

  forgotPassword(data) {
    return api.post("/forgot-password", data);
  },

  resetPassword(data) {
    return api.post("/reset-password", data);
  },

  logout() {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
  },
};

export default authService;

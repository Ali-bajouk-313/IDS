import api, { readRecord } from "../api/axios";

const profileService = {
  async getProfile() {
    const response = await api.get("/profile", { cache: false });
    return readRecord(response, "data")?.user || null;
  },

  async updateProfile(payload) {
    const response = await api.put("/profile", payload);
    return readRecord(response, "data")?.user || null;
  },

  async changePassword(payload) {
    const response = await api.put("/profile/password", payload);
    return readRecord(response, "data");
  },
};

export default profileService;
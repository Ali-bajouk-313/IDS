import axios from "axios";
import { showToast } from "../utils/toastBus";

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || "/api",
    headers: {
        "Content-Type": "application/json"
    }
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem("token");

    if (token) {
        const headers = config.headers || {};

        if (typeof headers.set === "function") {
            headers.set("Authorization", `Bearer ${token}`);
        } else {
            headers.Authorization = `Bearer ${token}`;
        }

        config.headers = headers;
    }

    return config;
});

api.interceptors.response.use(
    (response) => {
        const method = response?.config?.method?.toLowerCase();
        const isMutation = ["post", "put", "patch", "delete"].includes(method);

        if (isMutation) {
            const message = response?.data?.message || "Action completed successfully.";
            showToast({ type: "success", message });
        }

        return response;
    },
    (error) => Promise.reject(error)
);

export function readCollection(response, key) {
    const payload = response?.data ?? {};

    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.[key])) {
        return payload[key];
    }

    if (payload && typeof payload === "object" && Array.isArray(payload.data)) {
        return payload.data;
    }

    return [];
}

export function readRecord(response, key) {
    const payload = response?.data ?? {};

    if (payload && typeof payload === "object" && payload[key] !== undefined) {
        return payload[key];
    }

    return payload;
}

export default api;
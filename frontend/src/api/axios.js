import axios from "axios";
import { showToast } from "../utils/toastBus";

const api = axios.create({

    baseURL: "http://127.0.0.1:8000/api",

    headers: {
        "Content-Type": "application/json"
    }

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


export default api;
import axios from "axios";
import { showToast } from "../utils/toastBus";

const GET_CACHE_TTL_MS = 30000;
const getCache = new Map();
const inFlightGetRequests = new Map();

function normalizeParams(params = {}) {
    if (!params || typeof params !== "object") {
        return "";
    }

    return Object.keys(params)
        .sort()
        .map((key) => {
            const value = params[key];
            if (value === undefined || value === null || value === "") {
                return null;
            }

            if (Array.isArray(value)) {
                return value
                    .map((item) => `${encodeURIComponent(key)}[]=${encodeURIComponent(String(item))}`)
                    .join("&");
            }

            return `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`;
        })
        .filter(Boolean)
        .join("&");
}

function buildGetCacheKey(url, config = {}) {
    const token = localStorage.getItem("token") || "guest";
    const tokenScope = token.slice(-12);
    const params = normalizeParams(config.params);
    const fullUrl = `${url}${params ? `?${params}` : ""}`;
    return `GET:${tokenScope}:${fullUrl}`;
}

function cloneCachedResponse(cached, config) {
    return {
        data: cached.data,
        status: cached.status,
        statusText: cached.statusText,
        headers: cached.headers,
        config,
        request: undefined,
    };
}

function clearGetCache() {
    getCache.clear();
}

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
        const skipSuccessToast = response?.config?.skipSuccessToast === true;

        if (isMutation && !skipSuccessToast) {
            clearGetCache();
            const message = response?.data?.message || "Action completed successfully.";
            showToast({ type: "success", message });
        }

        return response;
    },
    (error) => Promise.reject(error)
);

const originalGet = api.get.bind(api);

api.get = (url, config = {}) => {
    const useCache = config.cache !== false;

    if (!useCache) {
        return originalGet(url, config);
    }

    const cacheKey = buildGetCacheKey(url, config);
    const cached = getCache.get(cacheKey);

    if (cached && cached.expiresAt > Date.now()) {
        return Promise.resolve(cloneCachedResponse(cached, { ...config, method: "get", url }));
    }

    if (inFlightGetRequests.has(cacheKey)) {
        return inFlightGetRequests.get(cacheKey);
    }

    const requestPromise = originalGet(url, config)
        .then((response) => {
            getCache.set(cacheKey, {
                data: response.data,
                status: response.status,
                statusText: response.statusText,
                headers: response.headers,
                expiresAt: Date.now() + GET_CACHE_TTL_MS,
            });
            return response;
        })
        .finally(() => {
            inFlightGetRequests.delete(cacheKey);
        });

    inFlightGetRequests.set(cacheKey, requestPromise);
    return requestPromise;
};

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
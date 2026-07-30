import { useEffect, useMemo, useState } from "react";
import { subscribeToToasts } from "../utils/toastBus";

const TOAST_DURATION = 2800;

function ToastContainer() {
  const [toasts, setToasts] = useState([]);

  useEffect(() => {
    const unsubscribe = subscribeToToasts((payload) => {
      const id = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

      const toast = {
        id,
        type: payload?.type || "success",
        message: payload?.message || "Action completed successfully.",
      };

      setToasts((current) => [...current, toast]);

      window.setTimeout(() => {
        setToasts((current) => current.filter((item) => item.id !== id));
      }, TOAST_DURATION);
    });

    return unsubscribe;
  }, []);

  const containerStyle = useMemo(
    () => ({
      position: "fixed",
      top: 18,
      right: 18,
      zIndex: 9999,
      display: "flex",
      flexDirection: "column",
      gap: 10,
      pointerEvents: "none",
      width: "min(92vw, 340px)",
    }),
    []
  );

  if (toasts.length === 0) {
    return null;
  }

  return (
    <div style={containerStyle} aria-live="polite" aria-atomic="true">
      {toasts.map((toast) => (
        <div
          key={toast.id}
          style={{
            background: toast.type === "success" ? "#0f766e" : "#334155",
            color: "#ffffff",
            borderRadius: 12,
            boxShadow: "0 10px 28px rgba(15, 23, 42, 0.25)",
            padding: "11px 14px",
            fontSize: 13,
            fontWeight: 600,
            lineHeight: 1.4,
            transform: "translateY(0)",
            opacity: 1,
            transition: "all 0.2s ease",
          }}
          role="status"
        >
          {toast.message}
        </div>
      ))}
    </div>
  );
}

export default ToastContainer;

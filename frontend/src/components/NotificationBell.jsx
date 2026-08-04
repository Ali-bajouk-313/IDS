import { useEffect, useMemo, useState } from "react";
import { FiBell, FiCheckCircle, FiClock } from "react-icons/fi";
import api from "../api/axios";
import { formatDateTime } from "../utils/date";

function NotificationBell() {
  const [notifications, setNotifications] = useState([]);
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadNotifications = async () => {
    setError("");

    try {
      const response = await api.get("/notifications");
      setNotifications(response?.data?.notifications || []);
    } catch (err) {
      setError("Unable to load notifications. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadNotifications();
    const interval = window.setInterval(() => {
      loadNotifications();
    }, 30000);

    return () => window.clearInterval(interval);
  }, []);

  const unreadCount = useMemo(() => notifications.filter((item) => !item.read).length, [notifications]);

  const markAsRead = async (id) => {
    try {
      await api.post(`/notifications/${id}/read`);
      setNotifications((current) => current.map((item) => (item.id === id ? { ...item, read: true } : item)));
    } catch (error) {
      console.error("Unable to mark notification as read", error);
    }
  };

  const markAllAsRead = async () => {
    try {
      await Promise.all(notifications.filter((item) => !item.read).map((item) => api.post(`/notifications/${item.id}/read`)));
      setNotifications((current) => current.map((item) => ({ ...item, read: true })));
    } catch (error) {
      console.error("Unable to mark all notifications as read", error);
    }
  };

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen((current) => !current)}
        className="relative rounded-2xl border border-slate-200 p-2.5 text-slate-600 transition hover:bg-slate-100"
      >
        <FiBell className="h-5 w-5" />
        {unreadCount > 0 ? (
          <span className="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1 text-[11px] font-semibold text-white">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        ) : null}
      </button>

      {open ? (
        <div className="absolute right-0 z-20 mt-3 w-80 rounded-3xl border border-slate-200 bg-white p-3 shadow-[0_20px_50px_rgba(15,23,42,0.16)]">
          <div className="mb-2 flex items-center justify-between px-1">
            <div>
              <p className="text-sm font-semibold text-slate-900">Notifications</p>
              <p className="text-xs text-slate-500">{unreadCount} unread</p>
            </div>
            {unreadCount > 0 ? (
              <button type="button" onClick={markAllAsRead} className="text-xs font-semibold text-blue-600 hover:text-blue-700">
                Mark all read
              </button>
            ) : null}
          </div>

          {loading ? (
            <div className="space-y-3">
              {Array.from({ length: 4 }).map((_, index) => (
                <div key={index} className="rounded-2xl border border-slate-200 bg-slate-50 p-4 animate-pulse">
                  <div className="mb-3 flex items-center gap-2">
                    <div className="h-8 w-8 rounded-2xl bg-slate-200" />
                    <div className="h-3 w-3/4 rounded-full bg-slate-200" />
                  </div>
                  <div className="h-3 w-full rounded-full bg-slate-200" />
                  <div className="mt-2 h-2 w-2/3 rounded-full bg-slate-200" />
                </div>
              ))}
            </div>
          ) : error ? (
            <div className="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-center text-sm text-rose-700">
              {error}
            </div>
          ) : notifications.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center text-sm text-slate-500">
              You're all caught up.
            </div>
          ) : (
            <div className="max-h-80 space-y-2 overflow-auto">
              {notifications.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  onClick={() => markAsRead(item.id)}
                  className={`w-full rounded-2xl border px-3 py-3 text-left ${item.read ? "border-slate-200 bg-slate-50" : "border-blue-200 bg-blue-50/70"}`}
                >
                  <div className="flex items-start gap-2">
                    <div className={`mt-0.5 rounded-xl p-2 ${item.read ? "bg-slate-200 text-slate-700" : "bg-blue-600 text-white"}`}>
                      {item.read ? <FiClock className="h-3.5 w-3.5" /> : <FiBell className="h-3.5 w-3.5" />}
                    </div>
                    <div className="min-w-0 flex-1">
                      <div className="flex items-start justify-between gap-2">
                        <p className="text-sm font-semibold text-slate-900">{item.title}</p>
                        {!item.read ? <FiCheckCircle className="mt-0.5 h-3.5 w-3.5 flex-none text-blue-600" /> : null}
                      </div>
                      <p className="mt-1 text-sm text-slate-600">{item.message}</p>
                      <p className="mt-2 text-xs text-slate-500">{formatDateTime(item.created_at)}</p>
                    </div>
                  </div>
                </button>
              ))}
            </div>
          )}
        </div>
      ) : null}
    </div>
  );
}

export default NotificationBell;

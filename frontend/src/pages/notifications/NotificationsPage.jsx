import { useEffect, useMemo, useState } from "react";
import { FiBell, FiCheckCircle, FiClock } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import api from "../../api/axios";
import { formatDateTime } from "../../utils/date";

function NotificationsPage({ role, title, subtitle }) {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadNotifications = async () => {
    setError("");

    try {
      const response = await api.get("/notifications", { cache: false });
      setNotifications(response?.data?.notifications || []);
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to load notifications. Please refresh.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadNotifications();
    const interval = window.setInterval(loadNotifications, 15000);
    return () => window.clearInterval(interval);
  }, []);

  const unreadCount = useMemo(() => notifications.filter((item) => !item.read).length, [notifications]);

  const markAsRead = async (id) => {
    try {
      await api.post(`/notifications/${id}/read`);
      setNotifications((current) => current.map((item) => (item.id === id ? { ...item, read: true } : item)));
    } catch (err) {
      setError(err?.response?.data?.message || "Could not update notification.");
    }
  };

  return (
    <DashboardLayout role={role} title={title} subtitle={subtitle}>
      <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
        <div className="mb-4 flex items-center justify-between gap-3">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Notification Center</h3>
            <p className="text-sm text-slate-500">Stay updated with ticket activity and service changes.</p>
          </div>
          <div className="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
            {unreadCount} unread
          </div>
        </div>

        {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

        {loading ? (
          <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading notifications…</div>
        ) : notifications.length === 0 ? (
          <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">You're all caught up.</div>
        ) : (
          <div className="space-y-3">
            {notifications.map((item) => (
              <div key={item.id} className={`rounded-2xl border px-4 py-4 ${item.read ? "border-slate-200 bg-slate-50" : "border-blue-200 bg-blue-50/70"}`}>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div className="flex gap-3">
                    <div className={`rounded-2xl p-2.5 ${item.read ? "bg-slate-200 text-slate-700" : "bg-blue-600 text-white"}`}>
                      {item.read ? <FiClock className="h-4 w-4" /> : <FiBell className="h-4 w-4" />}
                    </div>
                    <div>
                      <p className="text-sm font-semibold text-slate-900">{item.title}</p>
                      <p className="mt-1 text-sm text-slate-600">{item.message}</p>
                      <p className="mt-2 text-xs text-slate-500">{formatDateTime(item.created_at)}</p>
                    </div>
                  </div>

                  {!item.read ? (
                    <button onClick={() => markAsRead(item.id)} className="inline-flex items-center gap-2 rounded-2xl border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">
                      <FiCheckCircle className="h-4 w-4" />
                      Mark read
                    </button>
                  ) : null}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </DashboardLayout>
  );
}

export default NotificationsPage;

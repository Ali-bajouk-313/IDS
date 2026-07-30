import { useEffect, useMemo, useState } from "react";
import DashboardLayout from "../../components/DashboardLayout";
import DataTable from "../../components/DataTable";
import ticketService from "../../services/ticketService";

function ActivityLogs() {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [actionFilter, setActionFilter] = useState("");
  const [userFilter, setUserFilter] = useState("");
  const [dateFilter, setDateFilter] = useState("");

  useEffect(() => {
    async function loadLogs() {
      setLoading(true);
      setError("");

      try {
        const response = await ticketService.getActivityLogs();
        setLogs(response.data.logs || []);
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load activity logs.");
      } finally {
        setLoading(false);
      }
    }

    loadLogs();
  }, []);

  const actions = useMemo(() => {
    return [...new Set(logs.map((log) => log.action).filter(Boolean))].sort();
  }, [logs]);

  const users = useMemo(() => {
    return [...new Set(logs.map((log) => log.user?.fullName).filter(Boolean))].sort();
  }, [logs]);

  const filteredLogs = useMemo(() => {
    const q = search.trim().toLowerCase();

    return logs.filter((log) => {
      const logDate = log.createdAt ? new Date(log.createdAt).toISOString().slice(0, 10) : "";
      const matchesSearch =
        !q ||
        String(log.action || "").toLowerCase().includes(q) ||
        String(log.description || "").toLowerCase().includes(q) ||
        String(log.ipAddress || "").toLowerCase().includes(q) ||
        String(log.user?.fullName || "").toLowerCase().includes(q) ||
        String(log.user?.email || "").toLowerCase().includes(q);

      const matchesAction = !actionFilter || log.action === actionFilter;
      const matchesUser = !userFilter || log.user?.fullName === userFilter;
      const matchesDate = !dateFilter || logDate === dateFilter;

      return matchesSearch && matchesAction && matchesUser && matchesDate;
    });
  }, [logs, search, actionFilter, userFilter, dateFilter]);

  const columns = ["Date", "User", "Action", "Description", "IP Address"];

  const rows = filteredLogs.map((log) => ({
    date: log.createdAt ? new Date(log.createdAt).toLocaleString() : "-",
    user: log.user ? `${log.user.fullName} (${log.user.email})` : "Unknown user",
    action: log.action || "-",
    description: log.description || "-",
    ipAddress: log.ipAddress || "-",
  }));

  return (
    <DashboardLayout role="Admin" title="Activity Logs" subtitle="System-wide audit trail of authentication and ticket actions.">
      <div className="space-y-6">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label className="block text-sm text-slate-700">
              Search
              <input
                type="text"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search by user, action, description, or IP"
                className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              />
            </label>

            <label className="block text-sm text-slate-700">
              Action
              <select
                value={actionFilter}
                onChange={(event) => setActionFilter(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              >
                <option value="">All actions</option>
                {actions.map((action) => (
                  <option key={action} value={action}>
                    {action}
                  </option>
                ))}
              </select>
            </label>

            <label className="block text-sm text-slate-700">
              User
              <select
                value={userFilter}
                onChange={(event) => setUserFilter(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              >
                <option value="">All users</option>
                {users.map((user) => (
                  <option key={user} value={user}>
                    {user}
                  </option>
                ))}
              </select>
            </label>

            <label className="block text-sm text-slate-700">
              Date
              <input
                type="date"
                value={dateFilter}
                onChange={(event) => setDateFilter(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              />
            </label>
          </div>
        </div>

        {loading ? (
          <div className="rounded-3xl border border-slate-200 bg-white p-8 text-center text-slate-600 shadow-sm">Loading activity logs...</div>
        ) : null}

        {error ? (
          <div className="rounded-3xl border border-rose-200 bg-rose-50 p-8 text-center text-rose-700">{error}</div>
        ) : null}

        {!loading && !error ? (
          <>
            <div className="text-sm text-slate-600">Showing {rows.length} activity records.</div>
            <DataTable columns={columns} rows={rows} />
          </>
        ) : null}
      </div>
    </DashboardLayout>
  );
}

export default ActivityLogs;

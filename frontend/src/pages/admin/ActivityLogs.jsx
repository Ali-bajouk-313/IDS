import { useEffect, useMemo, useState } from "react";
import DashboardLayout from "../../components/DashboardLayout";
import DataTable from "../../components/DataTable";
import ticketService from "../../services/ticketService";
import { readCollection } from "../../api/axios.js";
import { formatDateTime } from "../../utils/date";

function ActivityLogs({ role = "Admin" }) {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [actionFilter, setActionFilter] = useState("");
  const [userFilter, setUserFilter] = useState("");
  const [dateFilter, setDateFilter] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadLogs() {
      setLoading(true);
      setError("");

      try {
        const response = await ticketService.getActivityLogs();
        if (!isMounted) {
          return;
        }
        setLogs(readCollection(response, "logs"));
      } catch (err) {
        if (!isMounted) {
          return;
        }
        setError(err?.response?.data?.message || "Unable to load activity logs.");
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    loadLogs();

    return () => {
      isMounted = false;
    };
  }, []);

  const actions = useMemo(() => [...new Set(logs.map((log) => log.action).filter(Boolean))].sort(), [logs]);
  const users = useMemo(() => [...new Set(logs.map((log) => log.user?.fullName).filter(Boolean))].sort(), [logs]);

  const filteredLogs = useMemo(() => {
    const q = search.trim().toLowerCase();

    return logs.filter((log) => {
      const logDate = log.createdAt ? new Date(log.createdAt).toISOString().slice(0, 10) : "";
      const matchesSearch = !q || [log.action, log.description, log.ipAddress, log.user?.fullName, log.user?.email].some((value) => String(value || "").toLowerCase().includes(q));
      const matchesAction = !actionFilter || log.action === actionFilter;
      const matchesUser = !userFilter || log.user?.fullName === userFilter;
      const matchesDate = !dateFilter || logDate === dateFilter;

      return matchesSearch && matchesAction && matchesUser && matchesDate;
    });
  }, [logs, search, actionFilter, userFilter, dateFilter]);

  const summary = useMemo(() => ({
    total: filteredLogs.length,
    uniqueUsers: new Set(filteredLogs.map((log) => log.user?.fullName).filter(Boolean)).size,
    actions: new Set(filteredLogs.map((log) => log.action).filter(Boolean)).size,
  }), [filteredLogs]);

  const columns = [
    { label: "Date", key: "date" },
    { label: "User", key: "user" },
    { label: "Action", key: "action" },
    { label: "Description", key: "description" },
    { label: "IP Address", key: "ipAddress" },
  ];
  const rows = filteredLogs.map((log) => ({
    date: formatDateTime(log.createdAt),
    user: log.user ? `${log.user.fullName} (${log.user.email})` : "Unknown user",
    action: log.action || "-",
    description: log.description || "-",
    ipAddress: log.ipAddress || "-",
  }));

  return (
    <DashboardLayout role={role} title="Activity Logs" subtitle={role === "IT Support" ? "Track assignment, updates, and support activity in one place." : "System-wide audit trail of authentication and ticket actions."}>
      <div className="space-y-6">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="mb-4 grid gap-4 md:grid-cols-3">
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Visible records</p>
              <p className="mt-1 text-2xl font-semibold text-slate-900">{summary.total}</p>
            </div>
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Contributors</p>
              <p className="mt-1 text-2xl font-semibold text-slate-900">{summary.uniqueUsers}</p>
            </div>
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Actions captured</p>
              <p className="mt-1 text-2xl font-semibold text-slate-900">{summary.actions}</p>
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label className="block text-sm text-slate-700">
              Search
              <input type="text" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search by user, action, description, or IP" className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
            </label>

            <label className="block text-sm text-slate-700">
              Action
              <select value={actionFilter} onChange={(event) => setActionFilter(event.target.value)} className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                <option value="">All actions</option>
                {actions.map((action) => (
                  <option key={action} value={action}>{action}</option>
                ))}
              </select>
            </label>

            <label className="block text-sm text-slate-700">
              User
              <select value={userFilter} onChange={(event) => setUserFilter(event.target.value)} className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                <option value="">All users</option>
                {users.map((user) => (
                  <option key={user} value={user}>{user}</option>
                ))}
              </select>
            </label>

            <label className="block text-sm text-slate-700">
              Date
              <input type="date" value={dateFilter} onChange={(event) => setDateFilter(event.target.value)} className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
            </label>
          </div>
        </div>

        {loading ? <div className="rounded-3xl border border-slate-200 bg-white p-8 text-center text-slate-600 shadow-sm">Loading activity logs...</div> : null}
        {error ? <div className="rounded-3xl border border-rose-200 bg-rose-50 p-8 text-center text-rose-700">{error}</div> : null}

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

import { useEffect, useMemo, useState } from "react";
import { FiAlertTriangle, FiDatabase, FiServer, FiUsers, FiMessageSquare, FiShield } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import ActivityFeed from "../../components/ActivityFeed";
import LoadingSkeleton from "../../components/LoadingSkeleton";
import ticketService from "../../services/ticketService";
import api, { readCollection } from "../../api/axios.js";
import DataTable from "../../components/DataTable";
import { formatDateTime } from "../../utils/date";

function AdminDashboard() {
  const [tickets, setTickets] = useState([]);
  const [users, setUsers] = useState([]);
  const [activityLogs, setActivityLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadDashboardData() {
      setLoading(true);
      setError("");

      try {
        const [ticketsResponse, usersResponse, logsResponse] = await Promise.all([
          ticketService.getTickets(),
          api.get("/users"),
          ticketService.getActivityLogs().catch(() => ({ data: { logs: [] } })),
        ]);

        if (!isMounted) {
          return;
        }

        setTickets(readCollection(ticketsResponse, "tickets"));
        setUsers(readCollection(usersResponse, "users"));
        setActivityLogs(readCollection(logsResponse, "logs").slice(0, 5));
      } catch (err) {
        if (!isMounted) {
          return;
        }
        setError(err?.response?.data?.message || "Unable to load dashboard data.");
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    loadDashboardData();

    return () => {
      isMounted = false;
    };
  }, []);

  const stats = useMemo(() => {
    const open = tickets.filter((ticket) => ticket.status !== "Resolved" && ticket.status !== "Closed").length;
    const resolved = tickets.filter((ticket) => ticket.status === "Resolved" || ticket.status === "Closed").length;
    const critical = tickets.filter((ticket) => ticket.priority === "Critical").length;
    const roleCounts = users.reduce((acc, user) => {
      const roleName = user.role?.roleName || "Employee";
      acc[roleName] = (acc[roleName] || 0) + 1;
      return acc;
    }, {});

    return { users: users.length, tickets: tickets.length, open, resolved, critical, roleCounts };
  }, [tickets, users]);

  const activityItems = activityLogs.map((log) => ({
    title: log.description || log.action || "System activity",
    time: formatDateTime(log.createdAt) || "Recently updated",
  }));

  const ticketHistoryRows = useMemo(() => {
    return [...tickets]
      .sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0))
      .map((ticket) => ({
        ticketNumber: ticket.ticketNumber || `#${ticket.id}`,
        title: ticket.title || "Untitled ticket",
        creator: ticket.creator?.fullName || "Unknown",
        createdAt: formatDateTime(ticket.createdAt),
        status: ticket.status || "Open",
      }));
  }, [tickets]);

  return (
    <DashboardLayout role="Admin" title="System Overview" subtitle="Monitor platform health, security, and service delivery.">
      {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

      <div className="grid gap-4 lg:grid-cols-4">
        <StatCard title="Total Users" value={stats.users} detail="Registered accounts" icon={FiUsers} accent="bg-blue-600" />
        <StatCard title="Total Tickets" value={stats.tickets} detail={`${stats.open} active`} icon={FiMessageSquare} accent="bg-emerald-600" />
        <StatCard title="Resolved" value={stats.resolved} detail="Completed requests" icon={FiServer} accent="bg-sky-600" />
        <StatCard title="Critical" value={stats.critical} detail="High priority" icon={FiAlertTriangle} accent="bg-rose-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-3">
        <ChartCard title="Ticket Activity">
          {loading ? (
            <div className="flex h-48 items-center justify-center rounded-2xl bg-slate-50 text-sm text-slate-600"><span className="animate-spin rounded-full border-2 border-slate-300 border-t-blue-600" /></div>
          ) : (
            <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
              {[stats.open, stats.resolved, stats.critical, Math.max(1, Math.round(stats.tickets / 3))].map((height, index) => (
                <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-blue-700 to-blue-400" style={{ height: `${Math.max(18, height * 8)}%` }} />
              ))}
            </div>
          )}
        </ChartCard>

        <ChartCard title="Role Distribution">
          {loading ? (
            <div className="flex h-48 items-center justify-center rounded-2xl bg-slate-50 text-sm text-slate-600"><span className="animate-spin rounded-full border-2 border-slate-300 border-t-blue-600" /></div>
          ) : (
            <div className="flex h-48 flex-col justify-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
              {Object.entries(stats.roleCounts).slice(0, 4).map(([role, count]) => (
                <div key={role} className="flex items-center justify-between"><span>{role}</span><span className="font-semibold">{count}</span></div>
              ))}
            </div>
          )}
        </ChartCard>

        <ChartCard title="System Health">
          <div className="flex h-48 flex-col justify-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
            <div className="flex items-center justify-between rounded-2xl bg-white px-3 py-2"><div className="flex items-center gap-3"><FiDatabase className="text-blue-600" /><span>Database</span></div><span className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">Operational</span></div>
            <div className="flex items-center justify-between rounded-2xl bg-white px-3 py-2"><div className="flex items-center gap-3"><FiServer className="text-blue-600" /><span>API Services</span></div><span className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">Operational</span></div>
            <div className="flex items-center justify-between rounded-2xl bg-white px-3 py-2"><div className="flex items-center gap-3"><FiShield className="text-blue-600" /><span>Email Service</span></div><span className="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">Monitoring</span></div>
          </div>
        </ChartCard>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-lg font-semibold text-slate-900">Operational Summary</h3>
          <div className="mt-4 space-y-3">
            <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span className="text-sm text-slate-600">Active tickets</span><span className="text-sm font-semibold text-slate-900">{stats.open}</span></div>
            <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span className="text-sm text-slate-600">Completed</span><span className="text-sm font-semibold text-slate-900">{stats.resolved}</span></div>
            <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span className="text-sm text-slate-600">Critical queue</span><span className="text-sm font-semibold text-slate-900">{stats.critical}</span></div>
          </div>
        </div>

        <ActivityFeed items={activityItems.length > 0 ? activityItems : [{ title: "No recent activity yet", time: "Just now" }]} />
      </div>

      <div className="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Ticket Creation History</h3>
            <p className="text-sm text-slate-500">A full list of tickets created across the system with their creation time.</p>
          </div>
          <span className="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">{ticketHistoryRows.length} tickets</span>
        </div>
        <DataTable columns={[
          { label: "Ticket #", key: "ticketNumber" },
          { label: "Title", key: "title" },
          { label: "Creator", key: "creator" },
          { label: "Created", key: "createdAt" },
          { label: "Status", key: "status" },
        ]} rows={ticketHistoryRows} emptyState="No tickets have been created yet." />
      </div>
    </DashboardLayout>
  );
}

export default AdminDashboard;

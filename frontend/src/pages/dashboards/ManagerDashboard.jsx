import { useEffect, useMemo, useState } from "react";
import { FiBarChart2, FiClock, FiUsers, FiMessageSquare, FiTrendingUp } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import DataTable from "../../components/DataTable";
import ActivityFeed from "../../components/ActivityFeed";
import LoadingSkeleton from "../../components/LoadingSkeleton";
import ticketService from "../../services/ticketService";
import { readCollection } from "../../api/axios.js";

function ManagerDashboard() {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadTickets() {
      setLoading(true);
      setError("");

      try {
        const response = await ticketService.getTickets();
        if (!isMounted) {
          return;
        }
        setTickets(readCollection(response, "tickets"));
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

    loadTickets();

    return () => {
      isMounted = false;
    };
  }, []);

  const stats = useMemo(() => {
    const pending = tickets.filter((ticket) => ticket.status !== "Resolved" && ticket.status !== "Closed").length;
    const inProgress = tickets.filter((ticket) => ticket.status === "In Progress").length;
    const resolved = tickets.filter((ticket) => ticket.status === "Resolved" || ticket.status === "Closed").length;
    const rate = tickets.length > 0 ? Math.round((resolved / tickets.length) * 100) : 0;

    return { total: tickets.length, pending, inProgress, resolved, rate };
  }, [tickets]);

  const rows = tickets.slice(0, 6).map((ticket) => ({
    ticketNumber: ticket.ticketNumber,
    title: ticket.title,
    createdBy: ticket.creator?.fullName || "-",
    assignedTo: ticket.assignedUser?.fullName || "Unassigned",
    priority: <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700">{ticket.priority}</span>,
    status: <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700">{ticket.status}</span>,
  }));

  return (
    <DashboardLayout role="Manager" title="Department Performance" subtitle="Monitor your team, service health, and operational load.">
      {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

      <div className="grid gap-4 lg:grid-cols-4">
        <StatCard title="Department Tickets" value={stats.total} detail="Latest view" icon={FiMessageSquare} accent="bg-blue-600" />
        <StatCard title="Pending Tickets" value={stats.pending} detail={`${stats.inProgress} in progress`} icon={FiClock} accent="bg-amber-600" />
        <StatCard title="Resolution Rate" value={`${stats.rate}%`} detail="Current performance" icon={FiTrendingUp} accent="bg-emerald-600" />
        <StatCard title="Active Team" value={tickets.filter((ticket) => ticket.assignedTo).length} detail="Assigned tickets" icon={FiUsers} accent="bg-sky-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <ChartCard title="Ticket Status Distribution">
          {loading ? (
            <div className="flex h-48 items-center justify-center rounded-2xl bg-slate-50 text-sm text-slate-600"><span className="animate-spin rounded-full border-2 border-slate-300 border-t-blue-600" /></div>
          ) : (
            <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
              {[stats.pending, stats.inProgress, stats.resolved, tickets.filter((ticket) => ticket.status === "Open").length].map((height, index) => (
                <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-amber-600 to-orange-400" style={{ height: `${Math.max(16, height * 8)}%` }} />
              ))}
            </div>
          )}
        </ChartCard>

        <ChartCard title="Delivery Health">
          <div className="flex h-48 flex-col justify-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
            <div className="flex items-center justify-between rounded-2xl bg-white px-3 py-2"><span>Open</span><span className="font-semibold">{tickets.filter((ticket) => ticket.status === "Open").length}</span></div>
            <div className="flex items-center justify-between rounded-2xl bg-white px-3 py-2"><span>Assigned</span><span className="font-semibold">{tickets.filter((ticket) => ticket.status === "Assigned").length}</span></div>
            <div className="flex items-center justify-between rounded-2xl bg-white px-3 py-2"><span>Resolved</span><span className="font-semibold">{tickets.filter((ticket) => ticket.status === "Resolved" || ticket.status === "Closed").length}</span></div>
          </div>
        </ChartCard>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="mb-3 flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold text-slate-900">Recent Tickets</h3>
              <p className="text-sm text-slate-500">Latest requests in your department.</p>
            </div>
          </div>
          {loading ? (
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading department tickets…</div>
          ) : rows.length === 0 ? (
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No department tickets found.</div>
          ) : (
            <DataTable columns={[
              { label: "Ticket #", key: "ticketNumber" },
              { label: "Title", key: "title" },
              { label: "Created By", key: "createdBy" },
              { label: "Assigned To", key: "assignedTo" },
              { label: "Priority", key: "priority" },
              { label: "Status", key: "status" },
            ]} rows={rows} />
          )}
        </div>
        <ActivityFeed items={[{ title: "Department activity updated", time: "Just now" }, { title: "Latest ticket status refreshed", time: "Recently" }]} />
      </div>
    </DashboardLayout>
  );
}

export default ManagerDashboard;

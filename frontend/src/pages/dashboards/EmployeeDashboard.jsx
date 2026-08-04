import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { FiClipboard, FiClock, FiFileText, FiMessageSquare, FiPackage, FiPlusCircle, FiServer } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import DataTable from "../../components/DataTable";
import ActivityFeed from "../../components/ActivityFeed";
import LoadingSkeleton from "../../components/LoadingSkeleton";
import ticketService from "../../services/ticketService";
import { readCollection } from "../../api/axios.js";

function EmployeeDashboard() {
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
        setError(err?.response?.data?.message || "Unable to load your tickets.");
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
    const open = tickets.filter((ticket) => ticket.status === "Open" || ticket.status === "Assigned").length;
    const inProgress = tickets.filter((ticket) => ticket.status === "In Progress").length;
    const resolved = tickets.filter((ticket) => ticket.status === "Resolved" || ticket.status === "Closed").length;
    return { open, inProgress, resolved, pending: tickets.filter((ticket) => ticket.status === "Assigned").length };
  }, [tickets]);

  const rows = tickets.slice(0, 6).map((ticket) => ({
    ticketNumber: ticket.ticketNumber,
    title: ticket.title,
    category: ticket.category?.categoryName || "-",
    priority: <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700">{ticket.priority}</span>,
    status: <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700">{ticket.status}</span>,
    createdDate: new Date(ticket.createdAt).toLocaleDateString(),
  }));

  return (
    <DashboardLayout role="Employee" title="My Requests" subtitle="Track your tickets and request support quickly.">
      {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

      <div className="grid gap-4 lg:grid-cols-4">
        <StatCard title="Open Tickets" value={stats.open} detail="Needs follow-up" icon={FiMessageSquare} accent="bg-blue-600" />
        <StatCard title="In Progress" value={stats.inProgress} detail="Being reviewed" icon={FiClock} accent="bg-amber-600" />
        <StatCard title="Resolved" value={stats.resolved} detail="Completed" icon={FiFileText} accent="bg-emerald-600" />
        <StatCard title="Pending" value={stats.pending} detail="Awaiting assignment" icon={FiPackage} accent="bg-sky-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <ChartCard title="Ticket Status">
          {loading ? (
            <div className="flex h-48 items-center justify-center rounded-2xl bg-slate-50 text-sm text-slate-600"><span className="animate-spin rounded-full border-2 border-slate-300 border-t-blue-600" /></div>
          ) : (
            <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
              {[stats.open, stats.inProgress, stats.resolved].map((height, index) => (
                <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-blue-700 to-cyan-400" style={{ height: `${Math.max(18, height * 8)}%` }} />
              ))}
            </div>
          )}
        </ChartCard>

        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-lg font-semibold text-slate-900">Quick Actions</h3>
          <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
            <Link to="/employee-dashboard/tickets/create" className="flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
              <FiPlusCircle className="h-4 w-4" /> Create Ticket
            </Link>
            <button className="flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
              <FiServer className="h-4 w-4" /> View Knowledge Base
            </button>
          </div>
        </div>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="mb-3 flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold text-slate-900">My Tickets</h3>
              <p className="text-sm text-slate-500">Your latest requests and progress updates.</p>
            </div>
          </div>
          {loading ? (
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading your tickets…</div>
          ) : rows.length === 0 ? (
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No tickets found yet. Create one to get started.</div>
          ) : (
            <DataTable columns={[
              { label: "Ticket #", key: "ticketNumber" },
              { label: "Title", key: "title" },
              { label: "Category", key: "category" },
              { label: "Priority", key: "priority" },
              { label: "Status", key: "status" },
              { label: "Created Date", key: "createdDate" },
            ]} rows={rows} />
          )}
        </div>
        <ActivityFeed items={[{ title: "Latest ticket status refreshed", time: "Recently" }, { title: "Support updates are now visible", time: "Live" }]} />
      </div>
    </DashboardLayout>
  );
}

export default EmployeeDashboard;

import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { FiAlertTriangle, FiCheckCircle, FiClock, FiMessageSquare, FiTrendingUp, FiUserCheck } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import DataTable from "../../components/DataTable";
import ActivityFeed from "../../components/ActivityFeed";
import LoadingSkeleton from "../../components/LoadingSkeleton";
import ticketService from "../../services/ticketService";
import { readCollection } from "../../api/axios.js";

function ITSupportDashboard() {
  const [assignedTickets, setAssignedTickets] = useState([]);
  const [allTickets, setAllTickets] = useState([]);
  const [activityLogs, setActivityLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadDashboardData() {
      setLoading(true);
      setError("");

      try {
        const [assignedResponse, allTicketsResponse, logsResponse] = await Promise.all([
          ticketService.getMyAssignedTickets(),
          ticketService.getTickets(),
          ticketService.getActivityLogs().catch(() => ({ data: { logs: [] } })),
        ]);

        if (!isMounted) {
          return;
        }

        setAssignedTickets(readCollection(assignedResponse, "tickets"));
        setAllTickets(readCollection(allTicketsResponse, "tickets"));
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
    const totalOpen = assignedTickets.filter((ticket) => ticket.status === "Open" || ticket.status === "Assigned").length;
    const highPriority = assignedTickets.filter((ticket) => ticket.priority === "High" || ticket.priority === "Critical").length;
    const resolvedToday = assignedTickets.filter((ticket) => ticket.status === "Resolved").length;

    return {
      assigned: assignedTickets.length,
      open: totalOpen,
      highPriority,
      resolvedToday,
      totalTickets: allTickets.length,
    };
  }, [assignedTickets, allTickets]);

  const statusBreakdown = useMemo(() => {
    const counts = { Open: 0, Assigned: 0, "In Progress": 0, Resolved: 0, Closed: 0 };

    allTickets.forEach((ticket) => {
      counts[ticket.status] = (counts[ticket.status] || 0) + 1;
    });

    return counts;
  }, [allTickets]);

  const priorityBreakdown = useMemo(() => {
    const counts = { Low: 0, Medium: 0, High: 0, Critical: 0 };

    allTickets.forEach((ticket) => {
      counts[ticket.priority] = (counts[ticket.priority] || 0) + 1;
    });

    return counts;
  }, [allTickets]);

  const assignedRows = assignedTickets.map((ticket) => ({
    ticketNumber: ticket.ticketNumber || `TICKET-${ticket.id}`,
    title: ticket.title,
    user: ticket.creator?.fullName || "-",
    priority: <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700">{ticket.priority}</span>,
    status: <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700">{ticket.status}</span>,
    action: (
      <Link to={`/tickets/${ticket.id}`} className="rounded-2xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
        Open
      </Link>
    ),
  }));

  const activityItems = activityLogs.map((log) => ({
    title: log.description || log.action || "Recent support activity",
    time: log.createdAt ? new Date(log.createdAt).toLocaleString() : "Recently updated",
  }));

  return (
    <DashboardLayout role="IT Support" title="Support Operations" subtitle="Monitor tickets, stay ahead of escalations, and keep service levels high.">
      <div className="space-y-6">
        {error ? (
          <div className="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div>
        ) : null}

        <div className="grid gap-4 lg:grid-cols-5">
          <StatCard title="All Tickets" value={stats.totalTickets} detail="Across the workspace" icon={FiMessageSquare} accent="bg-blue-600" />
          <StatCard title="Assigned Tickets" value={stats.assigned} detail={`${stats.open} active now`} icon={FiUserCheck} accent="bg-sky-600" />
          <StatCard title="High Priority" value={stats.highPriority} detail="Needs attention" icon={FiAlertTriangle} accent="bg-rose-600" />
          <StatCard title="Resolved" value={stats.resolvedToday} detail="Currently completed" icon={FiCheckCircle} accent="bg-emerald-600" />
          <StatCard title="Response" value="Live" detail="Updated in real time" icon={FiClock} accent="bg-amber-600" />
        </div>

        <div className="mt-6 grid gap-6 xl:grid-cols-2">
          <ChartCard title="Ticket Volume Trend">
            <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
              {[statusBreakdown.Open, statusBreakdown.Assigned, statusBreakdown["In Progress"], statusBreakdown.Resolved, statusBreakdown.Closed].map((height, index) => (
                <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-sky-700 to-cyan-400" style={{ height: `${Math.max(18, height * 12)}%` }} />
              ))}
            </div>
          </ChartCard>

          <ChartCard title="Priority Distribution">
            <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
              {[priorityBreakdown.Low, priorityBreakdown.Medium, priorityBreakdown.High, priorityBreakdown.Critical].map((height, index) => (
                <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-rose-600 to-orange-400" style={{ height: `${Math.max(16, height * 10)}%` }} />
              ))}
            </div>
          </ChartCard>
        </div>

        <div className="mt-6 grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
          <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-slate-900">Assigned Tickets</h3>
                <p className="text-sm text-slate-500">Your live queue with the latest ticket details.</p>
              </div>
              <span className="rounded-full bg-sky-50 px-3 py-1 text-sm font-semibold text-sky-700">{assignedTickets.length} active</span>
            </div>
            {loading ? (
              <LoadingSkeleton variant="table" rows={5} />
            ) : assignedRows.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No tickets are currently assigned to you.</div>
            ) : (
              <DataTable columns={[
                { label: "Ticket #", key: "ticketNumber" },
                { label: "Title", key: "title" },
                { label: "Requester", key: "user" },
                { label: "Priority", key: "priority" },
                { label: "Status", key: "status" },
                { label: "Action", key: "action" },
              ]} rows={assignedRows} emptyState="No tickets are currently assigned to you." />
            )}
          </div>
          <ActivityFeed items={activityItems.length > 0 ? activityItems : [{ title: "No recent activity yet", time: "Just updated" }]} />
        </div>
      </div>
    </DashboardLayout>
  );
}

export default ITSupportDashboard;

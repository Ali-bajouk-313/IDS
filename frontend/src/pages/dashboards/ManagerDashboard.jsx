import { useEffect, useMemo, useState } from "react";
import { FiBarChart2, FiClock, FiUsers, FiMessageSquare, FiPieChart, FiTrendingUp } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import DashboardCard from "../../components/DashboardCard";
import DataTable from "../../components/DataTable";
import api from "../../api/axios.js";
import TicketStatusChart from "../../components/charts/TicketStatusChart";
import PriorityChart from "../../components/charts/PriorityChart";
import CategoryChart from "../../components/charts/CategoryChart";

function ManagerDashboard() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadDashboardData() {
      setLoading(true);
      setError("");

      try {
        const response = await api.get("/dashboard/manager");
        if (!isMounted) {
          return;
        }
        setData(response.data?.data || null);
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

  const summary = data?.summary || {};
  const priority = data?.priority || {};
  const category = data?.category || {};
  const teamPerformance = data?.teamPerformance || {};
  const recentTickets = data?.recentTickets || [];

  const priorityRows = useMemo(() => [
    { label: "Low", value: priority.low || 0 },
    { label: "Medium", value: priority.medium || 0 },
    { label: "High", value: priority.high || 0 },
    { label: "Urgent", value: priority.urgent || 0 },
  ], [priority]);

  const statusChartData = useMemo(() => [
    { name: "Open", value: summary.openTickets || 0 },
    { name: "In Progress", value: summary.inProgressTickets || 0 },
    { name: "Resolved", value: summary.resolvedTickets || 0 },
    { name: "Closed", value: summary.closedTickets || 0 },
  ], [summary]);
  const priorityChartData = useMemo(() => priorityRows.map((row) => ({ name: row.label, value: row.value })), [priorityRows]);
  const categoryChartData = useMemo(() => Object.entries(category || {}).map(([name, value]) => ({ name, value })), [category]);

  return (
    <DashboardLayout role="Manager" title="Department Performance" subtitle="Monitor your team, ticket load, and service health.">
      {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

      <div className="grid gap-4 lg:grid-cols-4">
        <DashboardCard title="Department Tickets" value={summary.totalTickets || 0} detail="Current department scope" icon={FiMessageSquare} accent="bg-blue-600" />
        <DashboardCard title="Pending Tickets" value={(summary.openTickets || 0) + (summary.inProgressTickets || 0)} detail="Needs action" icon={FiClock} accent="bg-amber-600" />
        <DashboardCard title="Resolved" value={(summary.resolvedTickets || 0) + (summary.closedTickets || 0)} detail="Completed" icon={FiTrendingUp} accent="bg-emerald-600" />
        <DashboardCard title="Assigned Team" value={teamPerformance.assignedTickets || 0} detail="Tickets with owners" icon={FiUsers} accent="bg-sky-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiPieChart className="text-blue-600" />
            Status Overview
          </div>
          <TicketStatusChart data={statusChartData} loading={loading} emptyMessage="No ticket status data is available yet." />
        </div>

        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiBarChart2 className="text-emerald-600" />
            Team Performance
          </div>
          {loading ? (
            <div className="mt-4 flex h-56 items-center justify-center rounded-3xl bg-slate-50 text-sm text-slate-600">Loading metrics…</div>
          ) : (
            <div className="mt-4 space-y-3">
              <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span className="text-sm text-slate-600">Assigned tickets</span><span className="text-sm font-semibold text-slate-900">{teamPerformance.assignedTickets || 0}</span></div>
              <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span className="text-sm text-slate-600">Unassigned</span><span className="text-sm font-semibold text-slate-900">{teamPerformance.unassignedTickets || 0}</span></div>
              <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span className="text-sm text-slate-600">Resolved</span><span className="text-sm font-semibold text-slate-900">{teamPerformance.resolvedTickets || 0}</span></div>
            </div>
          )}
        </div>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiBarChart2 className="text-sky-600" />
            Priority Breakdown
          </div>
          <PriorityChart data={priorityChartData} loading={loading} emptyMessage="No priority data is available yet." />
        </div>

        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiUsers className="text-violet-600" />
            Category Breakdown
          </div>
          <CategoryChart data={categoryChartData} loading={loading} emptyMessage="No category data yet." />
        </div>
      </div>

      <div className="mt-6 rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Recent Department Tickets</h3>
            <p className="text-sm text-slate-500">The latest ticket activity in your department.</p>
          </div>
        </div>
        <DataTable columns={[
          { label: "Ticket #", key: "ticketNumber" },
          { label: "Title", key: "title" },
          { label: "Created By", key: "creator" },
          { label: "Assigned To", key: "assignedTo" },
          { label: "Priority", key: "priority" },
          { label: "Status", key: "status" },
        ]} rows={recentTickets.map((ticket) => ({ ...ticket }))} emptyState="No department tickets found." />
      </div>
    </DashboardLayout>
  );
}

export default ManagerDashboard;

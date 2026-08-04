import { useEffect, useMemo, useState } from "react";
import { FiAlertTriangle, FiBarChart2, FiMessageSquare, FiPieChart, FiServer, FiUsers } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import DashboardCard from "../../components/DashboardCard";
import api from "../../api/axios.js";
import DataTable from "../../components/DataTable";
import TicketStatusChart from "../../components/charts/TicketStatusChart";
import PriorityChart from "../../components/charts/PriorityChart";
import MonthlyTicketsChart from "../../components/charts/MonthlyTicketsChart";
import CategoryChart from "../../components/charts/CategoryChart";

function AdminDashboard() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadDashboardData() {
      setLoading(true);
      setError("");

      try {
        const response = await api.get("/dashboard/admin");
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
  const monthlyTickets = data?.monthlyTickets || [];
  const userStats = data?.userStats || {};
  const recentTickets = data?.recentTickets || [];

  const statusRows = useMemo(() => [
    { label: "Open", value: summary.openTickets || 0 },
    { label: "In Progress", value: summary.inProgressTickets || 0 },
    { label: "Resolved", value: summary.resolvedTickets || 0 },
    { label: "Closed", value: summary.closedTickets || 0 },
  ], [summary]);

  const priorityRows = useMemo(() => [
    { label: "Low", value: priority.low || 0 },
    { label: "Medium", value: priority.medium || 0 },
    { label: "High", value: priority.high || 0 },
    { label: "Urgent", value: priority.urgent || 0 },
  ], [priority]);

  const statusChartData = useMemo(() => statusRows.map((row) => ({ name: row.label, value: row.value })), [statusRows]);
  const priorityChartData = useMemo(() => priorityRows.map((row) => ({ name: row.label, value: row.value })), [priorityRows]);
  const monthlyChartData = useMemo(() => (monthlyTickets || []).map((item) => ({ name: item.month, value: item.count })), [monthlyTickets]);
  const categoryChartData = useMemo(() => Object.entries(category || {}).map(([name, value]) => ({ name, value })), [category]);

  return (
    <DashboardLayout role="Admin" title="System Overview" subtitle="Monitor platform health, service delivery, and ticket trends.">
      {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

      <div className="grid gap-4 lg:grid-cols-4">
        <DashboardCard title="Total Tickets" value={summary.totalTickets || 0} detail="All system tickets" icon={FiMessageSquare} accent="bg-blue-600" />
        <DashboardCard title="Open Tickets" value={summary.openTickets || 0} detail="Needs attention" icon={FiAlertTriangle} accent="bg-amber-600" />
        <DashboardCard title="Resolved Tickets" value={summary.resolvedTickets || 0} detail="Completed requests" icon={FiServer} accent="bg-emerald-600" />
        <DashboardCard title="High Priority" value={(priority.high || 0) + (priority.urgent || 0)} detail="Escalations" icon={FiBarChart2} accent="bg-rose-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiPieChart className="text-blue-600" />
            Ticket Status
          </div>
          <TicketStatusChart data={statusChartData} loading={loading} emptyMessage="No ticket status data is available yet." />
        </div>

        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiBarChart2 className="text-emerald-600" />
            Priority Breakdown
          </div>
          <PriorityChart data={priorityChartData} loading={loading} emptyMessage="No priority data is available yet." />
        </div>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiBarChart2 className="text-sky-600" />
            Monthly Ticket Volume
          </div>
          <MonthlyTicketsChart data={monthlyChartData} loading={loading} emptyMessage="No monthly ticket data is available." />
        </div>

        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiUsers className="text-violet-600" />
            Category Breakdown
          </div>
          <CategoryChart data={categoryChartData} loading={loading} emptyMessage="No category data is available yet." />
        </div>
      </div>

      <div className="mt-6 rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Recent Tickets</h3>
            <p className="text-sm text-slate-500">The latest ticket activity across the system.</p>
          </div>
        </div>
        <DataTable columns={[
          { label: "Ticket #", key: "ticketNumber" },
          { label: "Title", key: "title" },
          { label: "Creator", key: "creator" },
          { label: "Status", key: "status" },
          { label: "Priority", key: "priority" },
        ]} rows={recentTickets.map((ticket) => ({ ...ticket }))} emptyState="No recent tickets found." />
      </div>
    </DashboardLayout>
  );
}

export default AdminDashboard;

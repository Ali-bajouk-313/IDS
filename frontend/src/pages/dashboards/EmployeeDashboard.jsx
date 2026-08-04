import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { FiClock, FiFileText, FiMessageSquare, FiPackage, FiPieChart, FiPlusCircle, FiServer } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import DashboardCard from "../../components/DashboardCard";
import DataTable from "../../components/DataTable";
import api from "../../api/axios.js";
import TicketStatusChart from "../../components/charts/TicketStatusChart";
import PriorityChart from "../../components/charts/PriorityChart";

function EmployeeDashboard() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadDashboardData() {
      setLoading(true);
      setError("");

      try {
        const response = await api.get("/dashboard/employee");
        if (!isMounted) {
          return;
        }
        setData(response.data?.data || null);
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

    loadDashboardData();

    return () => {
      isMounted = false;
    };
  }, []);

  const summary = data?.summary || {};
  const priority = data?.priority || {};
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

  return (
    <DashboardLayout role="Employee" title="My Requests" subtitle="Track your tickets and keep an eye on your recent requests.">
      {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

      <div className="grid gap-4 lg:grid-cols-4">
        <DashboardCard title="Total Tickets" value={summary.totalCreatedTickets || 0} detail="Requests you created" icon={FiMessageSquare} accent="bg-blue-600" />
        <DashboardCard title="Open Tickets" value={summary.openTickets || 0} detail="Needs follow-up" icon={FiClock} accent="bg-amber-600" />
        <DashboardCard title="Resolved" value={summary.resolvedTickets || 0} detail="Completed" icon={FiFileText} accent="bg-emerald-600" />
        <DashboardCard title="Closed" value={summary.closedTickets || 0} detail="Finished" icon={FiPackage} accent="bg-sky-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiPieChart className="text-blue-600" />
            Personal Ticket Status
          </div>
          <TicketStatusChart data={statusChartData} loading={loading} emptyMessage="No personal ticket status data is available yet." />
        </div>

        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FiPieChart className="text-emerald-600" />
            Priority Breakdown
          </div>
          <PriorityChart data={priorityChartData} loading={loading} emptyMessage="No priority data is available yet." />
        </div>
      </div>

      <div className="mt-6 rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Recent Tickets</h3>
            <p className="text-sm text-slate-500">The latest requests you’ve submitted.</p>
          </div>
        </div>
        {loading ? (
          <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading your tickets…</div>
        ) : recentTickets.length === 0 ? (
          <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No tickets found yet. Create one to get started.</div>
        ) : (
          <DataTable columns={[
            { label: "Ticket #", key: "ticketNumber" },
            { label: "Title", key: "title" },
            { label: "Category", key: "category" },
            { label: "Priority", key: "priority" },
            { label: "Status", key: "status" },
          ]} rows={recentTickets.map((ticket) => ({ ...ticket }))} />
        )}
      </div>
    </DashboardLayout>
  );
}

export default EmployeeDashboard;

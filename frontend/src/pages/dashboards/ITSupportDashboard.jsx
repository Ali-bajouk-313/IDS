import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { FiAlertTriangle, FiCheckCircle, FiClock, FiMessageSquare, FiPieChart, FiUserCheck } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import DashboardCard from "../../components/DashboardCard";
import DataTable from "../../components/DataTable";
import api from "../../api/axios.js";
import TicketStatusChart from "../../components/charts/TicketStatusChart";
import PriorityChart from "../../components/charts/PriorityChart";

function ITSupportDashboard() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadDashboardData() {
      setLoading(true);
      setError("");

      try {
        const response = await api.get("/dashboard/support");
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
  const recentAssignedTickets = data?.recentAssignedTickets || [];

  const priorityRows = useMemo(() => [
    { label: "Low", value: priority.low || 0 },
    { label: "Medium", value: priority.medium || 0 },
    { label: "High", value: priority.high || 0 },
    { label: "Urgent", value: priority.urgent || 0 },
  ], [priority]);

  const statusChartData = useMemo(() => [
    { name: "Open", value: summary.openAssignedTickets || 0 },
    { name: "In Progress", value: summary.inProgressAssignedTickets || 0 },
    { name: "Resolved", value: summary.resolvedAssignedTickets || 0 },
    { name: "Closed", value: summary.closedAssignedTickets || 0 },
  ], [summary]);
  const priorityChartData = useMemo(() => priorityRows.map((row) => ({ name: row.label, value: row.value })), [priorityRows]);

  return (
    <DashboardLayout role="IT Support" title="Support Operations" subtitle="Focus on the tickets assigned to you and keep response times steady.">
      <div className="space-y-6">
        {error ? (
          <div className="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div>
        ) : null}

        <div className="grid gap-4 lg:grid-cols-4">
          <DashboardCard title="Assigned Tickets" value={summary.totalAssignedTickets || 0} detail="Current queue" icon={FiUserCheck} accent="bg-blue-600" />
          <DashboardCard title="Open Assigned" value={summary.openAssignedTickets || 0} detail="Needs follow-up" icon={FiClock} accent="bg-amber-600" />
          <DashboardCard title="Resolved" value={summary.resolvedAssignedTickets || 0} detail="Completed" icon={FiCheckCircle} accent="bg-emerald-600" />
          <DashboardCard title="Avg. Resolution" value={`${summary.averageResolutionTime ?? 0}h`} detail="Average time" icon={FiMessageSquare} accent="bg-sky-600" />
        </div>

        <div className="grid gap-6 xl:grid-cols-2">
          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <FiPieChart className="text-blue-600" />
              Assigned Ticket Status
            </div>
            <TicketStatusChart data={statusChartData} loading={loading} emptyMessage="No assigned ticket status data is available yet." />
          </div>

          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <FiAlertTriangle className="text-rose-600" />
              Priority Breakdown
            </div>
            <PriorityChart data={priorityChartData} loading={loading} emptyMessage="No priority data is available yet." />
          </div>
        </div>

        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold text-slate-900">Recent Assigned Tickets</h3>
              <p className="text-sm text-slate-500">Your latest work items and their current state.</p>
            </div>
          </div>
          {loading ? (
            <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading your assignments…</div>
          ) : recentAssignedTickets.length === 0 ? (
            <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No assigned tickets found.</div>
          ) : (
            <DataTable columns={[
              { label: "Ticket #", key: "ticketNumber" },
              { label: "Title", key: "title" },
              { label: "Requester", key: "creator" },
              { label: "Priority", key: "priority" },
              { label: "Status", key: "status" },
            ]} rows={recentAssignedTickets.map((ticket) => ({ ...ticket }))} emptyState="No assigned tickets found." />
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}

export default ITSupportDashboard;

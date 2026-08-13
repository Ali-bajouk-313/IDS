import { useEffect, useMemo, useState } from "react";
import { FiAlertTriangle, FiBarChart2, FiClock, FiGrid, FiPieChart, FiTrendingUp, FiUsers } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import DashboardCard from "../../components/DashboardCard";
import LoadingSkeleton from "../../components/LoadingSkeleton";
import DataTable from "../../components/DataTable";
import TicketStatusChart from "../../components/charts/TicketStatusChart";
import PriorityChart from "../../components/charts/PriorityChart";
import MonthlyTicketsChart from "../../components/charts/MonthlyTicketsChart";
import CategoryChart from "../../components/charts/CategoryChart";
import ticketService from "../../services/ticketService.js";

function getDownloadFilename(disposition, fallbackName) {
  if (!disposition) {
    return fallbackName;
  }

  const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1]);
  }

  const plainMatch = disposition.match(/filename="?([^";]+)"?/i);
  if (plainMatch?.[1]) {
    return plainMatch[1];
  }

  return fallbackName;
}

function ReportsPage({ role, title, subtitle }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState("");
  const [exportError, setExportError] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadReports() {
      setLoading(true);
      setError("");

      try {
        const response = await ticketService.getReports();
        if (!isMounted) {
          return;
        }

        setData(response.data?.data || null);
      } catch (err) {
        if (!isMounted) {
          return;
        }

        setError(err?.response?.data?.message || "Unable to load reports.");
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    loadReports();

    return () => {
      isMounted = false;
    };
  }, []);

  async function downloadReport(exporter, fallbackName, setExporting) {
    setExportError("");
    setExporting(true);

    try {
      const response = await exporter();
      const blob = new Blob([response.data], {
        type: response.headers?.["content-type"] || "application/octet-stream",
      });
      const filename = getDownloadFilename(response.headers?.["content-disposition"], fallbackName);
      const objectUrl = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = objectUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(objectUrl);
    } catch (err) {
      setExportError(err?.response?.data?.message || "Unable to export reports.");
    } finally {
      setExporting(false);
    }
  }

  const scope = data?.scope || {};
  const summary = data?.summary || {};
  const statusBreakdown = data?.statusBreakdown || [];
  const priorityBreakdown = data?.priorityBreakdown || [];
  const categoryBreakdown = data?.categoryBreakdown || [];
  const monthlyTickets = data?.monthlyTickets || [];
  const assignedAgents = data?.assignedAgents || [];
  const recentTickets = data?.recentTickets || [];
  const averageResolutionTime = Number(data?.averageResolutionTime || 0);

  const summaryCards = useMemo(() => ([
    {
      title: "Total Tickets",
      value: summary.totalTickets || 0,
      detail: scope.label || "Live database scope",
      icon: FiGrid,
      accent: "bg-blue-600",
    },
    {
      title: "Open",
      value: summary.openTickets || 0,
      detail: "Awaiting assignment or action",
      icon: FiAlertTriangle,
      accent: "bg-amber-600",
    },
    {
      title: "Assigned",
      value: summary.assignedTickets || 0,
      detail: "Tickets actively owned",
      icon: FiUsers,
      accent: "bg-sky-600",
    },
    {
      title: "In Progress",
      value: summary.inProgressTickets || 0,
      detail: "Currently being worked",
      icon: FiBarChart2,
      accent: "bg-violet-600",
    },
    {
      title: "Resolved",
      value: summary.resolvedTickets || 0,
      detail: "Ready for closure",
      icon: FiTrendingUp,
      accent: "bg-emerald-600",
    },
    {
      title: "Closed",
      value: summary.closedTickets || 0,
      detail: "Completed requests",
      icon: FiClock,
      accent: "bg-slate-700",
    },
    {
      title: "Avg. Resolution",
      value: `${averageResolutionTime.toFixed(2)}h`,
      detail: "Mean time to resolve",
      icon: FiClock,
      accent: "bg-rose-600",
    },
  ]), [averageResolutionTime, scope.label, summary]);

  const statusChartData = useMemo(() => statusBreakdown.map((item) => ({ name: item.name, value: item.value })), [statusBreakdown]);
  const priorityChartData = useMemo(() => priorityBreakdown.map((item) => ({ name: item.name, value: item.value })), [priorityBreakdown]);
  const monthlyChartData = useMemo(() => monthlyTickets.map((item) => ({ name: item.month, value: item.count })), [monthlyTickets]);
  const categoryChartData = useMemo(() => categoryBreakdown.map((item) => ({ name: item.name, value: item.value })), [categoryBreakdown]);

  const agentRows = useMemo(() => assignedAgents.map((item) => ({ name: item.name, value: item.value })), [assignedAgents]);

  return (
    <DashboardLayout
      role={role}
      title={title}
      subtitle={subtitle}
    >
      <div className="space-y-6">
        {error ? (
          <div className="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
            {error}
          </div>
        ) : null}

        <div className="rounded-[1.75rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <div className="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                {scope.label || "Live reports"}
              </div>
              <h2 className="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Real-time ticket reporting</h2>
              <p className="mt-2 max-w-3xl text-sm text-slate-500">
                {scope.description || "This page is powered directly by the database and updates as ticket records change."}
              </p>
            </div>
            <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
              <div>Scope: <span className="font-semibold text-slate-900">{scope.role || role}</span></div>
              <div className="mt-3 flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={() => downloadReport(ticketService.exportReportsPdf, "helpdeskpro-reports.pdf", setExportingPdf)}
                  disabled={exportingPdf || loading}
                  className="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {exportingPdf ? "Preparing PDF…" : "Export PDF"}
                </button>
                <button
                  type="button"
                  onClick={() => downloadReport(ticketService.exportReportsExcel, "helpdeskpro-reports.xlsx", setExportingExcel)}
                  disabled={exportingExcel || loading}
                  className="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {exportingExcel ? "Preparing Excel…" : "Export Excel"}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {loading ? (
            <>
              <LoadingSkeleton rows={2} />
              <LoadingSkeleton rows={2} />
              <LoadingSkeleton rows={2} />
              <LoadingSkeleton rows={2} />
            </>
          ) : (
            summaryCards.map((card) => (
              <DashboardCard
                key={card.title}
                title={card.title}
                value={card.value}
                detail={card.detail}
                icon={card.icon}
                accent={card.accent}
              />
            ))
          )}
        </div>

        <div className="grid gap-6 xl:grid-cols-2">
          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <FiPieChart className="text-blue-600" />
              Status Breakdown
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

        <div className="grid gap-6 xl:grid-cols-2">
          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <FiTrendingUp className="text-sky-600" />
              Monthly Volume
            </div>
            <MonthlyTicketsChart data={monthlyChartData} loading={loading} emptyMessage="No monthly ticket data is available yet." />
          </div>

          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <FiGrid className="text-violet-600" />
              Category Breakdown
            </div>
            <CategoryChart data={categoryChartData} loading={loading} emptyMessage="No category data is available yet." />
          </div>
        </div>

        {agentRows.length > 0 ? (
          <div className="grid gap-6">
            <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
              <div className="mb-4 flex items-center justify-between">
                <div>
                  <h3 className="text-lg font-semibold text-slate-900">IT Support Workload</h3>
                  <p className="text-sm text-slate-500">Tickets grouped by assigned support agent.</p>
                </div>
              </div>
              {loading ? (
                <LoadingSkeleton variant="table" rows={4} />
              ) : agentRows.length === 0 ? (
                <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No assigned support workload is available yet.</div>
              ) : (
                <DataTable
                  columns={[
                    { label: "IT Support Agent", key: "name" },
                    { label: "Tickets", key: "value" },
                  ]}
                  rows={agentRows}
                  emptyState="No assigned support workload found."
                />
              )}
            </div>
          </div>
        ) : null}

        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold text-slate-900">Recent Tickets</h3>
              <p className="text-sm text-slate-500">The latest ticket records in the current report scope.</p>
            </div>
          </div>
          {loading ? (
            <LoadingSkeleton variant="table" rows={5} />
          ) : recentTickets.length === 0 ? (
            <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No tickets are available for this report scope.</div>
          ) : (
            <DataTable
              columns={[
                { label: "Ticket #", key: "ticketNumber" },
                { label: "Title", key: "title" },
                { label: "Category", key: "category" },
                { label: "Created By", key: "creator" },
                { label: "Assigned To", key: "assignedTo" },
                { label: "Priority", key: "priority" },
                { label: "Status", key: "status" },
              ]}
              rows={recentTickets.map((ticket) => ({
                ...ticket,
                assignedTo: ticket.assignedTo || "Unassigned",
              }))}
              emptyState="No tickets found for this report scope."
            />
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}

export default ReportsPage;
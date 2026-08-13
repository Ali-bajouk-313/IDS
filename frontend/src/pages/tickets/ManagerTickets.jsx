import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { FiFilter, FiSearch } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import DataTable from "../../components/DataTable";
import LoadingSkeleton from "../../components/LoadingSkeleton";
import Pagination from "../../components/Pagination";
import ticketService from "../../services/ticketService";
import { readCollection } from "../../api/axios.js";

const statusOptions = ["", "Open", "Assigned", "In Progress", "Resolved", "Closed"];
const priorityOptions = ["", "Low", "Medium", "High", "Critical"];

const statusStyles = {
  Open: "bg-blue-100 text-blue-700",
  Assigned: "bg-violet-100 text-violet-700",
  "In Progress": "bg-orange-100 text-orange-700",
  Resolved: "bg-emerald-100 text-emerald-700",
  Closed: "bg-slate-100 text-slate-700",
};
const pageSizes = [10, 20, 50];

function ManagerTickets() {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [priority, setPriority] = useState("");
  const [date, setDate] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(pageSizes[0]);

  useEffect(() => {
    async function loadTickets() {
      setLoading(true);
      setError("");
      try {
        const response = await ticketService.getTickets({ status, priority, date });
        setTickets(readCollection(response, "tickets"));
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load tickets. Please refresh.");
      } finally {
        setLoading(false);
      }
    }

    loadTickets();
  }, [status, priority, date]);

  const filteredTickets = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) {
      return tickets;
    }

    return tickets.filter((ticket) => [ticket.ticketNumber, ticket.creator?.fullName].some((value) => String(value || "").toLowerCase().includes(query)));
  }, [search, tickets]);

  useEffect(() => {
    setCurrentPage(1);
  }, [search, status, priority, date]);

  const totalPages = Math.max(1, Math.ceil(filteredTickets.length / pageSize));
  const paginatedTickets = useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return filteredTickets.slice(start, start + pageSize);
  }, [filteredTickets, currentPage, pageSize]);

  const rows = paginatedTickets.map((ticket) => ({
    ticketNumber: ticket.ticketNumber,
    employee: ticket.creator?.fullName || "-",
    priority: (
      <span className="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] bg-slate-100 text-slate-700">
        {ticket.priority}
      </span>
    ),
    status: (
      <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] ${statusStyles[ticket.status] ?? statusStyles.Open}`}>
        {ticket.status}
      </span>
    ),
    actions: (
      <Link
        to={`/tickets/${ticket.id}`}
        className="rounded-2xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
      >
        View
      </Link>
    ),
  }));

  return (
    <DashboardLayout role="Manager" title="My Tickets" subtitle="Review tickets you created.">
      <div className="space-y-6">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-5 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <h3 className="text-lg font-semibold tracking-tight text-slate-900">My tickets</h3>
              <p className="mt-1 text-sm text-slate-500">Review the latest requests in your scope.</p>
            </div>
            <div className="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">{filteredTickets.length} tickets</div>
          </div>

          <div className="mb-5 grid gap-4 lg:grid-cols-[1.4fr_0.9fr_0.9fr_0.8fr]">
            <label className="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500">
              <FiSearch className="h-4 w-4" />
              <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search ticket or employee" className="w-full border-0 bg-transparent outline-none" />
            </label>
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
              <option value="">All statuses</option>
              <option value="Open">Open</option>
              <option value="Assigned">Assigned</option>
              <option value="In Progress">In Progress</option>
              <option value="Resolved">Resolved</option>
              <option value="Closed">Closed</option>
            </select>
            <select value={priority} onChange={(e) => setPriority(e.target.value)} className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
              <option value="">All priorities</option>
              <option value="Low">Low</option>
              <option value="Medium">Medium</option>
              <option value="High">High</option>
              <option value="Critical">Critical</option>
            </select>
            <label className="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500">
              <FiFilter className="h-4 w-4" />
              <input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="w-full border-0 bg-transparent outline-none" />
            </label>
          </div>

          {error ? <div className="mb-4 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div> : null}

          {loading ? (
            <LoadingSkeleton variant="table" rows={6} />
          ) : filteredTickets.length === 0 ? (
            <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No tickets found in your scope.</div>
          ) : (
            <>
              <DataTable columns={[
                { label: "Ticket #", key: "ticketNumber" },
                { label: "Employee", key: "employee" },
                { label: "Priority", key: "priority" },
                { label: "Status", key: "status" },
                { label: "Actions", key: "actions" },
              ]} rows={rows} emptyState="No tickets match your current filters." />
              <Pagination
                currentPage={currentPage}
                totalPages={totalPages}
                onPageChange={setCurrentPage}
                pageSize={pageSize}
                pageSizes={pageSizes}
                onPageSizeChange={(size) => {
                  setPageSize(size);
                  setCurrentPage(1);
                }}
              />
            </>
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}

export default ManagerTickets;

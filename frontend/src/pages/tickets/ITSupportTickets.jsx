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
const pageSizes = [10, 20, 50];

const statusStyles = {
  Open: "bg-blue-100 text-blue-700",
  Assigned: "bg-violet-100 text-violet-700",
  "In Progress": "bg-orange-100 text-orange-700",
  Resolved: "bg-emerald-100 text-emerald-700",
  Closed: "bg-slate-100 text-slate-700",
};

function ITSupportTickets() {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [priority, setPriority] = useState("");
  const [date, setDate] = useState("");
  const [updates, setUpdates] = useState({});
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(pageSizes[0]);

  useEffect(() => {
    async function loadTickets() {
      setLoading(true);
      setError("");
      try {
        const response = await ticketService.getMyAssignedTickets({ status, priority, date });
        setTickets(readCollection(response, "tickets"));
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load tickets. Please refresh.");
      } finally {
        setLoading(false);
      }
    }

    loadTickets();
  }, [status, priority, date]);

  const handleUpdate = async (ticket) => {
    const updatePayload = {
      status: updates[ticket.id]?.status || ticket.status,
      priority: updates[ticket.id]?.priority || ticket.priority,
    };

    try {
      await ticketService.updateTicket(ticket.id, updatePayload);
      setTickets((current) => current.map((item) => (item.id === ticket.id ? { ...item, ...updatePayload } : item)));
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to update ticket.");
    }
  };

  const handleReject = async (ticketId) => {
    try {
      await ticketService.unassignTicket(ticketId);
      setTickets((current) => current.filter((item) => item.id !== ticketId));
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to reject ticket.");
    }
  };

  const filteredTickets = tickets.filter((ticket) => {
    const query = search.trim().toLowerCase();
    if (!query) {
      return true;
    }

    return [ticket.ticketNumber, ticket.title, ticket.creator?.fullName].some((value) => String(value || "").toLowerCase().includes(query));
  });

  useEffect(() => {
    setCurrentPage(1);
  }, [search, status, priority, date]);

  const totalPages = Math.max(1, Math.ceil(filteredTickets.length / pageSize));
  const paginatedTickets = useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return filteredTickets.slice(start, start + pageSize);
  }, [filteredTickets, currentPage, pageSize]);

  const rows = paginatedTickets.map((ticket) => ({
    ticketNumber: ticket.ticketNumber || `TICKET-${ticket.id}`,
    title: ticket.title,
    employee: ticket.creator?.fullName || "-",
    priority: ticket.status === "Closed" ? (
      <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700">{ticket.priority}</span>
    ) : (
      <select value={updates[ticket.id]?.priority || ticket.priority} onChange={(e) => setUpdates((current) => ({ ...current, [ticket.id]: { ...current[ticket.id], priority: e.target.value } }))} className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none">
        {priorityOptions.map((option) => (
          <option key={option} value={option || ticket.priority}>{option || ticket.priority}</option>
        ))}
      </select>
    ),
    status: (
      <div className="flex flex-col gap-2">
        <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] ${statusStyles[ticket.status] ?? statusStyles.Open}`}>
          {ticket.status}
        </span>
        {ticket.status === "Closed" ? null : (
          <select value={updates[ticket.id]?.status || ticket.status} onChange={(e) => setUpdates((current) => ({ ...current, [ticket.id]: { ...current[ticket.id], status: e.target.value } }))} className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none">
            {statusOptions.map((option) => (
              <option key={option} value={option || ticket.status}>{option || ticket.status}</option>
            ))}
          </select>
        )}
      </div>
    ),
    action: (
      <div className="flex flex-wrap items-center gap-2">
        <Link to={`/tickets/${ticket.id}`} className="rounded-2xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
          View
        </Link>
        {ticket.status !== "Closed" ? (
          <button type="button" onClick={() => handleUpdate(ticket)} className="rounded-2xl bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-200">
            Save
          </button>
        ) : null}
        {ticket.status !== "Closed" ? (
          <button type="button" onClick={() => handleReject(ticket.id)} className="rounded-2xl bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-200">
            Reject
          </button>
        ) : null}
      </div>
    ),
  }));

  return (
    <DashboardLayout role="IT Support" title="Assigned Tickets" subtitle="Manage and update the tickets assigned to you.">
      <div className="space-y-6">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-5 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <h3 className="text-lg font-semibold tracking-tight text-slate-900">Assigned tickets</h3>
              <p className="mt-1 text-sm text-slate-500">Track, update, and resolve the tickets routed to you.</p>
            </div>
            <div className="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">{filteredTickets.length} assigned tickets</div>
          </div>

          <div className="mb-5 grid gap-4 lg:grid-cols-[1.4fr_0.9fr_0.9fr_0.8fr]">
            <label className="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500">
              <FiSearch className="h-4 w-4" />
              <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search ticket or requester" className="w-full border-0 bg-transparent outline-none" />
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
            <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No assigned tickets found.</div>
          ) : (
            <>
              <DataTable columns={[
                { label: "Ticket #", key: "ticketNumber" },
                { label: "Title", key: "title" },
                { label: "Employee", key: "employee" },
                { label: "Priority", key: "priority" },
                { label: "Status", key: "status" },
                { label: "Actions", key: "action" },
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

export default ITSupportTickets;

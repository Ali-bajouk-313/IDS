import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import DashboardLayout from "../../components/DashboardLayout";
import DataTable from "../../components/DataTable";
import ticketService from "../../services/ticketService";

const statusOptions = ["", "Open", "Assigned", "In Progress", "Resolved", "Closed"];
const priorityOptions = ["", "Low", "Medium", "High", "Critical"];

const statusStyles = {
  Open: "bg-blue-100 text-blue-700",
  Assigned: "bg-violet-100 text-violet-700",
  "In Progress": "bg-orange-100 text-orange-700",
  Resolved: "bg-emerald-100 text-emerald-700",
  Closed: "bg-slate-100 text-slate-700",
};

function EmployeeTickets() {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [priority, setPriority] = useState("");
  const [date, setDate] = useState("");

  useEffect(() => {
    async function loadTickets() {
      setLoading(true);
      setError("");

      try {
        const response = await ticketService.getTickets({ status, priority, date });
        setTickets(response.data.tickets || []);
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load tickets. Please refresh.");
      } finally {
        setLoading(false);
      }
    }

    loadTickets();
  }, [status, priority, date]);

  const filteredTickets = useMemo(() => {
    if (!search.trim()) {
      return tickets;
    }

    const normalizedSearch = search.toLowerCase();

    return tickets.filter((ticket) => {
      return (
        ticket.ticketNumber.toLowerCase().includes(normalizedSearch) ||
        ticket.title.toLowerCase().includes(normalizedSearch) ||
        ticket.category?.categoryName?.toLowerCase().includes(normalizedSearch)
      );
    });
  }, [search, tickets]);

  const rows = filteredTickets.map((ticket) => ({
    ticketNumber: ticket.ticketNumber,
    title: ticket.title,
    category: ticket.category?.categoryName || "-",
    priority: <span className="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-700 bg-slate-100">{ticket.priority}</span>,
    status: (
      <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] ${statusStyles[ticket.status] ?? statusStyles.Open}`}>
        {ticket.status}
      </span>
    ),
    createdDate: new Date(ticket.createdAt).toLocaleDateString(),
    actions: (
      <div className="flex flex-wrap items-center gap-2">
        <Link
          to={`/tickets/${ticket.id}`}
          className="rounded-2xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
        >
          View
        </Link>
        {ticket.status !== "Closed" && ticket.assignedTo === null ? (
          <Link
            to={`/tickets/${ticket.id}`}
            className="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
          >
            Edit
          </Link>
        ) : (
          <span className="rounded-2xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500">Assigned</span>
        )}
      </div>
    ),
  }));

  return (
    <DashboardLayout role="Employee" title="My Tickets" subtitle="View and filter your open support requests." >
      <div className="space-y-6">
        <div className="grid gap-4 lg:grid-cols-[1.6fr_0.9fr]">
          <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">Ticket search</h2>
            <div className="mt-5 grid gap-4 lg:grid-cols-3">
              <input
                type="search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search by ticket number or title"
                className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
              />
              <select
                value={status}
                onChange={(e) => setStatus(e.target.value)}
                className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
              >
                {statusOptions.map((option) => (
                  <option key={option} value={option}>
                    {option || "All statuses"}
                  </option>
                ))}
              </select>
              <select
                value={priority}
                onChange={(e) => setPriority(e.target.value)}
                className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
              >
                {priorityOptions.map((option) => (
                  <option key={option} value={option}>
                    {option || "All priorities"}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">Date filter</h2>
            <p className="mt-2 text-sm text-slate-500">Filter tickets by creation date.</p>
            <input
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              className="mt-4 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            />
          </div>
        </div>

        {error ? (
          <div className="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div>
        ) : null}

        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h3 className="text-lg font-semibold text-slate-900">My tickets</h3>
              <p className="mt-1 text-sm text-slate-500">{filteredTickets.length} ticket{filteredTickets.length === 1 ? "" : "s"} found.</p>
            </div>
            <Link
              to="/employee-dashboard/tickets/create"
              className="inline-flex items-center justify-center rounded-3xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
            >
              Create Ticket
            </Link>
          </div>

          {loading ? (
            <div className="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading tickets…</div>
          ) : filteredTickets.length === 0 ? (
            <div className="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No tickets found. Adjust the filters or create a new ticket.</div>
          ) : (
            <DataTable
              columns={["Ticket #", "Title", "Category", "Priority", "Status", "Created", "Actions"]}
              rows={rows}
            />
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}

export default EmployeeTickets;

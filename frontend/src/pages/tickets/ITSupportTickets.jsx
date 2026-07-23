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

function ITSupportTickets() {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [status, setStatus] = useState("");
  const [priority, setPriority] = useState("");
  const [date, setDate] = useState("");
  const [updates, setUpdates] = useState({});

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

  const handleUpdate = async (ticket) => {
    const updatePayload = {
      status: updates[ticket.id]?.status || ticket.status,
      priority: updates[ticket.id]?.priority || ticket.priority,
    };

    try {
      await ticketService.updateTicket(ticket.id, updatePayload);
      setTickets((current) =>
        current.map((item) =>
          item.id === ticket.id ? { ...item, ...updatePayload } : item
        )
      );
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to update ticket.");
    }
  };

  const rows = tickets.map((ticket) => ({
    ticketNumber: ticket.ticketNumber,
    title: ticket.title,
    employee: ticket.creator?.fullName || "-",
    priority: (
      <select
        value={updates[ticket.id]?.priority || ticket.priority}
        onChange={(e) => setUpdates((current) => ({
          ...current,
          [ticket.id]: {
            ...current[ticket.id],
            priority: e.target.value,
          },
        }))}
        className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none"
      >
        {priorityOptions.map((option) => (
          <option key={option} value={option || ticket.priority}>
            {option || ticket.priority}
          </option>
        ))}
      </select>
    ),
    status: (
      <div className="flex flex-col gap-2">
        <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] ${statusStyles[ticket.status] ?? statusStyles.Open}`}>
          {ticket.status}
        </span>
        <select
          value={updates[ticket.id]?.status || ticket.status}
          onChange={(e) => setUpdates((current) => ({
            ...current,
            [ticket.id]: {
              ...current[ticket.id],
              status: e.target.value,
            },
          }))}
          className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none"
        >
          {statusOptions.map((option) => (
            <option key={option} value={option || ticket.status}>
              {option || ticket.status}
            </option>
          ))}
        </select>
      </div>
    ),
    action: (
      <div className="flex flex-wrap items-center gap-2">
        <Link
          to={`/tickets/${ticket.id}`}
          className="rounded-2xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
        >
          View
        </Link>
        <button
          type="button"
          onClick={() => handleUpdate(ticket)}
          className="rounded-2xl bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-200"
        >
          Save
        </button>
      </div>
    ),
  }));

  return (
    <DashboardLayout role="IT Support" title="Assigned Tickets" subtitle="Manage and update the tickets assigned to you.">
      <div className="space-y-6">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="mb-5 grid gap-4 lg:grid-cols-4">
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            >
              <option value="">All statuses</option>
              <option value="Open">Open</option>
              <option value="Assigned">Assigned</option>
              <option value="In Progress">In Progress</option>
              <option value="Resolved">Resolved</option>
              <option value="Closed">Closed</option>
            </select>
            <select
              value={priority}
              onChange={(e) => setPriority(e.target.value)}
              className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            >
              <option value="">All priorities</option>
              <option value="Low">Low</option>
              <option value="Medium">Medium</option>
              <option value="High">High</option>
              <option value="Critical">Critical</option>
            </select>
            <input
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            />
            <div className="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">{tickets.length} assigned tickets</div>
          </div>

          {error ? (
            <div className="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div>
          ) : null}

          {loading ? (
            <div className="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading assigned tickets…</div>
          ) : tickets.length === 0 ? (
            <div className="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No assigned tickets found.</div>
          ) : (
            <DataTable
              columns={["Ticket #", "Title", "Employee", "Priority", "Status", "Actions"]}
              rows={rows}
            />
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}

export default ITSupportTickets;

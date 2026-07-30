import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import DashboardLayout from "../../components/DashboardLayout";
import DataTable from "../../components/DataTable";
import StatCard from "../../components/StatCard";
import ticketService from "../../services/ticketService";
import api from "../../api/axios.js";

const statusStyles = {
  Open: "bg-blue-100 text-blue-700",
  Assigned: "bg-violet-100 text-violet-700",
  "In Progress": "bg-orange-100 text-orange-700",
  Resolved: "bg-emerald-100 text-emerald-700",
  Closed: "bg-slate-100 text-slate-700",
};

function AdminTickets() {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [status, setStatus] = useState("");
  const [priority, setPriority] = useState("");
  const [date, setDate] = useState("");
  const [supportAgents, setSupportAgents] = useState([]);
  const [assigningTicketId, setAssigningTicketId] = useState(null);
  const [selectedAgentId, setSelectedAgentId] = useState("");

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

  useEffect(() => {
    async function loadSupportAgents() {
      try {
        const response = await api.get("/users");
        const agents = (response.data.users || []).filter((user) => user.role?.roleName === "IT Support");
        setSupportAgents(agents);
      } catch (err) {
        setSupportAgents([]);
      }
    }

    loadSupportAgents();
  }, []);

  const stats = useMemo(() => {
    const total = tickets.length;
    const open = tickets.filter((ticket) => ticket.status === "Open").length;
    const resolved = tickets.filter((ticket) => ticket.status === "Resolved").length;
    const critical = tickets.filter((ticket) => ticket.priority === "Critical").length;

    return { total, open, resolved, critical };
  }, [tickets]);

  const handleDelete = async (ticketId) => {
    const confirmed = window.confirm("Delete this ticket? This action cannot be undone.");
    if (!confirmed) {
      return;
    }

    try {
      await ticketService.deleteTicket(ticketId);
      setTickets((current) => current.filter((ticket) => ticket.id !== ticketId));
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to delete ticket.");
    }
  };

  const handleAssign = async (ticketId) => {
    if (!selectedAgentId) {
      setError("Choose an IT Support agent before assigning the ticket.");
      return;
    }

    setAssigningTicketId(ticketId);
    setError("");

    try {
      const response = await ticketService.assignTicket(ticketId, selectedAgentId);
      setTickets((current) => current.map((ticket) => ticket.id === ticketId ? response.data.ticket : ticket));
      setSelectedAgentId("");
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to assign ticket.");
    } finally {
      setAssigningTicketId(null);
    }
  };

  const rows = tickets.map((ticket) => ({
    ticketNumber: ticket.ticketNumber,
    title: ticket.title,
    category: ticket.category?.categoryName || "-",
    priority: <span className="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] bg-slate-100 text-slate-700">{ticket.priority}</span>,
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
        {ticket.status !== "Closed" ? (
          <Link
            to={`/tickets/${ticket.id}`}
            className="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
          >
            Edit
          </Link>
        ) : null}
        {ticket.status !== "Closed" && ticket.assignedTo === null ? (
          <div className="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-1">
            <select
              value={selectedAgentId}
              onChange={(e) => setSelectedAgentId(e.target.value)}
              className="rounded-xl border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 outline-none"
            >
              <option value="">Assign to</option>
              {supportAgents.map((agent) => (
                <option key={agent.id} value={agent.id}>{agent.fullName}</option>
              ))}
            </select>
            <button
              type="button"
              onClick={() => handleAssign(ticket.id)}
              disabled={assigningTicketId === ticket.id}
              className="rounded-xl bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400"
            >
              {assigningTicketId === ticket.id ? "Working..." : "Assign"}
            </button>
          </div>
        ) : null}
        {ticket.assignedTo ? (
          <span className="rounded-2xl bg-violet-100 px-3 py-2 text-xs font-semibold text-violet-700">
            Assigned to: {ticket.assignedUser?.fullName || "IT Support"}
          </span>
        ) : null}
        <button
          type="button"
          onClick={() => handleDelete(ticket.id)}
          className="rounded-2xl bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-200"
        >
          Delete
        </button>
      </div>
    ),
  }));

  return (
    <DashboardLayout role="Admin" title="All Tickets" subtitle="Manage tickets, monitor status, and resolve critical issues.">
      <div className="space-y-6">
        <div className="grid gap-4 xl:grid-cols-4">
          <StatCard title="Total tickets" value={stats.total} detail="All ticket statuses" accent="bg-blue-600" />
          <StatCard title="Open tickets" value={stats.open} detail="Pending support" accent="bg-amber-600" />
          <StatCard title="Resolved tickets" value={stats.resolved} detail="Already closed" accent="bg-emerald-600" />
          <StatCard title="Critical tickets" value={stats.critical} detail="High priority" accent="bg-rose-600" />
        </div>

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
            <div className="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Showing {tickets.length} tickets</div>
          </div>

          {error ? (
            <div className="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">{error}</div>
          ) : null}

          {loading ? (
            <div className="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">Loading tickets…</div>
          ) : tickets.length === 0 ? (
            <div className="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">No tickets found. Adjust your filters to see more results.</div>
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

export default AdminTickets;

import { useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import DashboardLayout from "../../components/DashboardLayout";
import ticketService from "../../services/ticketService";

const statusOptions = ["Open", "Assigned", "In Progress", "Resolved", "Closed"];
const priorityOptions = ["Low", "Medium", "High", "Critical"];

const statusStyles = {
  Open: "bg-blue-100 text-blue-700",
  Assigned: "bg-violet-100 text-violet-700",
  "In Progress": "bg-orange-100 text-orange-700",
  Resolved: "bg-emerald-100 text-emerald-700",
  Closed: "bg-slate-100 text-slate-700",
};

function TicketDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [ticket, setTicket] = useState(null);
  const [categories, setCategories] = useState([]);
  const [form, setForm] = useState({
    title: "",
    description: "",
    categoryId: "",
    priority: "",
    status: "",
    comment: "",
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const user = JSON.parse(localStorage.getItem("user") || "{}") || {};
  const role = user.role || "Employee";

  const canEditByEmployee = role === "Employee" && ticket?.creator?.id === user.id && ticket?.assignedTo === null;
  const canSupportUpdate = role === "IT Support" && ticket?.assignedTo === user.id;
  const canAdminEdit = role === "Admin";
  const canEdit = canAdminEdit || canSupportUpdate || canEditByEmployee;

  const showEditPanel = useMemo(() => canEdit && ticket, [canEdit, ticket]);

  const handleFieldChange = (field) => (event) => {
    setForm((current) => ({
      ...current,
      [field]: event.target.value,
    }));
  };

  const handleSave = async () => {
    setSaving(true);
    setError("");
    setSuccess("");

    try {
      const payload = {};

      if (canAdminEdit) {
        payload.title = form.title;
        payload.description = form.description;
        payload.categoryId = form.categoryId;
        payload.priority = form.priority;
        payload.status = form.status;
      } else if (canSupportUpdate) {
        payload.priority = form.priority;
        payload.status = form.status;
      } else if (canEditByEmployee) {
        payload.title = form.title;
        payload.description = form.description;
        payload.categoryId = form.categoryId;
        payload.priority = form.priority;
      }

      if (form.comment.trim()) {
        payload.comment = form.comment.trim();
      }

      await ticketService.updateTicket(id, payload);
      const response = await ticketService.getTicketById(id);
      setTicket(response.data.ticket);
      setSuccess("Ticket updated successfully.");
      setForm((current) => ({ ...current, comment: "" }));
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to update ticket.");
    } finally {
      setSaving(false);
    }
  };

  useEffect(() => {
    async function loadTicket() {
      setLoading(true);
      setError("");

      try {
        const response = await ticketService.getTicketById(id);
        setTicket(response.data.ticket);
        setCategories(response.data.categories || []);
        setForm({
          title: response.data.ticket.title || "",
          description: response.data.ticket.description || "",
          categoryId: response.data.ticket.categoryId || "",
          priority: response.data.ticket.priority || "Medium",
          status: response.data.ticket.status || "Open",
          comment: "",
        });
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load ticket details.");
      } finally {
        setLoading(false);
      }
    }

    loadTicket();
  }, [id]);

  useEffect(() => {
    async function loadCategories() {
      try {
        const response = await ticketService.getCategories();
        setCategories(response.data.categories || []);
      } catch (err) {
        // categories are optional, keep existing state
      }
    }

    loadCategories();
  }, []);

  if (loading) {
    return (
      <DashboardLayout role={role} title="Ticket Details" subtitle="Review your ticket information.">
        <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm text-center text-slate-600">Loading ticket details…</div>
      </DashboardLayout>
    );
  }

  if (error) {
    return (
      <DashboardLayout role={role} title="Ticket Details" subtitle="Review your ticket information.">
        <div className="rounded-3xl border border-rose-200 bg-rose-50 p-8 text-center text-rose-700">{error}</div>
      </DashboardLayout>
    );
  }

  if (!ticket) {
    return (
      <DashboardLayout role={role} title="Ticket Details" subtitle="Review your ticket information.">
        <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm text-center text-slate-600">Ticket not found.</div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout role={role} title={`Ticket ${ticket.ticketNumber}`} subtitle="View full ticket details.">
      <div className="space-y-6">
        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="grid gap-6 lg:grid-cols-2">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Title</p>
              <p className="mt-2 text-xl font-semibold text-slate-900">{ticket.title}</p>
            </div>
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Status</p>
              <span className={`mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold uppercase tracking-[0.16em] ${statusStyles[ticket.status]}`}>
                {ticket.status}
              </span>
            </div>
          </div>

          <div className="mt-6 grid gap-6 lg:grid-cols-2">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Category</p>
              <p className="mt-2 text-sm text-slate-700">{ticket.category?.categoryName || "-"}</p>
            </div>
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Priority</p>
              <p className="mt-2 text-sm text-slate-700">{ticket.priority}</p>
            </div>
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Created by</p>
              <p className="mt-2 text-sm text-slate-700">{ticket.creator?.fullName || "-"}</p>
            </div>
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Assigned to</p>
              <p className="mt-2 text-sm text-slate-700">{ticket.assignedUser?.fullName || "Unassigned"}</p>
            </div>
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-[1.8fr_1fr]">
          <div className="space-y-6">
            <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 className="text-lg font-semibold text-slate-900">Ticket details</h3>
                  <p className="mt-1 text-sm text-slate-500">Review the ticket description, history and current status.</p>
                </div>
                <button
                  type="button"
                  onClick={() => navigate(-1)}
                  className="inline-flex items-center justify-center rounded-3xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                  Back to tickets
                </button>
              </div>
              <p className="mt-4 text-sm leading-7 text-slate-700 whitespace-pre-line">{ticket.description}</p>
            </div>

            {showEditPanel ? (
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <h3 className="text-lg font-semibold text-slate-900">Edit ticket</h3>
                    <p className="mt-1 text-sm text-slate-500">Update fields your role is allowed to change.</p>
                  </div>
                  <span className="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">
                    {role}
                  </span>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  {(canAdminEdit || canEditByEmployee) && (
                    <>
                      <label className="block text-sm text-slate-700">
                        Title
                        <input
                          type="text"
                          value={form.title}
                          onChange={handleFieldChange("title")}
                          className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        />
                      </label>
                      <label className="block text-sm text-slate-700 sm:col-span-2">
                        Description
                        <textarea
                          rows={4}
                          value={form.description}
                          onChange={handleFieldChange("description")}
                          className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        />
                      </label>
                      <label className="block text-sm text-slate-700">
                        Category
                        <select
                          value={form.categoryId}
                          onChange={handleFieldChange("categoryId")}
                          className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                          <option value="">Select category</option>
                          {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                              {category.categoryName}
                            </option>
                          ))}
                        </select>
                      </label>
                      <label className="block text-sm text-slate-700">
                        Priority
                        <select
                          value={form.priority}
                          onChange={handleFieldChange("priority")}
                          className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                          {priorityOptions.map((priority) => (
                            <option key={priority} value={priority}>
                              {priority}
                            </option>
                          ))}
                        </select>
                      </label>
                    </>
                  )}

                  {canSupportUpdate && (
                    <>
                      <label className="block text-sm text-slate-700">
                        Priority
                        <select
                          value={form.priority}
                          onChange={handleFieldChange("priority")}
                          className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                          {priorityOptions.map((priority) => (
                            <option key={priority} value={priority}>
                              {priority}
                            </option>
                          ))}
                        </select>
                      </label>
                      <label className="block text-sm text-slate-700">
                        Status
                        <select
                          value={form.status}
                          onChange={handleFieldChange("status")}
                          className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                          {statusOptions.map((status) => (
                            <option key={status} value={status}>
                              {status}
                            </option>
                          ))}
                        </select>
                      </label>
                    </>
                  )}

                  {canAdminEdit && (
                    <label className="block text-sm text-slate-700 sm:col-span-2">
                      Status
                      <select
                        value={form.status}
                        onChange={handleFieldChange("status")}
                        className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                      >
                        {statusOptions.map((status) => (
                          <option key={status} value={status}>
                            {status}
                          </option>
                        ))}
                      </select>
                    </label>
                  )}

                  <label className="block text-sm text-slate-700 sm:col-span-2">
                    Update note
                    <textarea
                      rows={3}
                      value={form.comment}
                      onChange={handleFieldChange("comment")}
                      placeholder="Add a note for history updates"
                      className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                    />
                  </label>
                </div>

                <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                  <div className="space-y-1">
                    {success ? <p className="text-sm text-emerald-700">{success}</p> : null}
                    {error ? <p className="text-sm text-rose-700">{error}</p> : null}
                  </div>
                  <button
                    type="button"
                    onClick={handleSave}
                    disabled={saving}
                    className="inline-flex items-center justify-center rounded-3xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                  >
                    {saving ? "Saving…" : "Save changes"}
                  </button>
                </div>
              </div>
            ) : null}
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-[1.4fr_0.9fr]">
          <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-5 flex items-center justify-between gap-3">
              <div>
                <h3 className="text-lg font-semibold text-slate-900">Timeline</h3>
                <p className="mt-1 text-sm text-slate-500">Review ticket history and comments in one place.</p>
              </div>
            </div>

            {ticket.history?.length > 0 ? (
              <div className="space-y-4">
                {ticket.history.map((entry) => (
                  <div key={entry.id} className="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-semibold text-slate-900">{entry.userName}</p>
                        <p className="text-sm text-slate-500">{new Date(entry.changedAt).toLocaleString()}</p>
                      </div>
                      <span className="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">
                        {entry.oldStatus} → {entry.newStatus}
                      </span>
                    </div>
                    {entry.comment ? <p className="mt-3 text-sm leading-6 text-slate-700">{entry.comment}</p> : null}
                  </div>
                ))}
              </div>
            ) : (
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">No timeline entries found for this ticket yet.</div>
            )}
          </div>

          <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-5">
              <h3 className="text-lg font-semibold text-slate-900">Comments</h3>
              <p className="mt-1 text-sm text-slate-500">Conversation history for this ticket.</p>
            </div>

            {ticket.comments?.length > 0 ? (
              <div className="space-y-4">
                {ticket.comments.map((comment) => (
                  <div key={comment.id} className="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-semibold text-slate-900">{comment.userName}</p>
                        <p className="text-sm text-slate-500">{new Date(comment.createdAt).toLocaleString()}</p>
                      </div>
                    </div>
                    <p className="mt-3 text-sm leading-6 text-slate-700">{comment.commentText}</p>
                  </div>
                ))}
              </div>
            ) : (
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">No comments have been added for this ticket yet.</div>
            )}
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}

export default TicketDetails;

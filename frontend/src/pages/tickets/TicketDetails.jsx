import { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import DashboardLayout from "../../components/DashboardLayout";
import ticketService from "../../services/ticketService";
import { readCollection, readRecord } from "../../api/axios.js";
import { formatDateTime } from "../../utils/date";

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
  const [history, setHistory] = useState([]);
  const [comments, setComments] = useState([]);
  const [internalNotes, setInternalNotes] = useState([]);
  const [attachments, setAttachments] = useState([]);
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
  const [statusUpdating, setStatusUpdating] = useState(false);
  const [commentSending, setCommentSending] = useState(false);
  const [newCommentText, setNewCommentText] = useState("");
  const [commentError, setCommentError] = useState("");
  const [commentSuccess, setCommentSuccess] = useState("");
  const [internalNoteText, setInternalNoteText] = useState("");
  const [internalNoteSending, setInternalNoteSending] = useState(false);
  const [internalNoteError, setInternalNoteError] = useState("");
  const [internalNoteSuccess, setInternalNoteSuccess] = useState("");
  const [internalNoteDeletingId, setInternalNoteDeletingId] = useState(null);
  const [attachmentUploading, setAttachmentUploading] = useState(false);
  const [attachmentError, setAttachmentError] = useState("");
  const [attachmentSuccess, setAttachmentSuccess] = useState("");
  const [attachmentDeletingId, setAttachmentDeletingId] = useState(null);
  const [selectedAttachmentFile, setSelectedAttachmentFile] = useState(null);
  const [aiActionLoading, setAiActionLoading] = useState("");
  const [aiError, setAiError] = useState("");
  const [aiInsight, setAiInsight] = useState(null);
  const [aiModalOpen, setAiModalOpen] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const storedUser = JSON.parse(localStorage.getItem("user") || "{}") || {};
  const user = {
    ...storedUser,
    id: storedUser.id ?? storedUser.userId,
    role: storedUser.role ?? storedUser.roleName ?? "Employee",
  };
  const role = user.role || "Employee";
  const currentUserId = Number(user.id ?? 0) || null;
  const creatorId = Number(ticket?.creator?.id ?? ticket?.createdBy ?? 0) || null;
  const assignedToId = Number(ticket?.assignedTo ?? ticket?.assignedUser?.id ?? 0) || null;

  const canEditByEmployee = role === "Employee" && creatorId === currentUserId && ticket?.assignedTo === null;
  const canSupportUpdate = role === "IT Support" && assignedToId === currentUserId && ticket?.status !== "In Progress";
  const canAdminEdit = role === "Admin" && ticket?.status !== "In Progress";
  const canEdit = canAdminEdit || canSupportUpdate || canEditByEmployee;
  const canStartWork = role === "IT Support" && assignedToId === currentUserId && ticket?.status === "Assigned";
  const canResolve = role === "IT Support" && assignedToId === currentUserId && ticket?.status === "In Progress";
  const canClose = canAdminEdit && ticket?.status === "Resolved";
  const canEmployeeComment = role === "Employee" && creatorId === currentUserId;
  const canSupportComment = role === "IT Support" && assignedToId === currentUserId;
  const canAdminComment = role === "Admin";
  const canAddComment = canEmployeeComment || canSupportComment || canAdminComment;
  const canViewInternalNotes = role === "Admin" || (role === "IT Support" && assignedToId === currentUserId);
  const canDeleteInternalNotes = role === "Admin";
  const canViewAttachments = role === "Admin" || (role === "IT Support" && assignedToId === currentUserId) || (role === "Employee" && creatorId === currentUserId) || (role === "Manager" && creatorId === currentUserId);
  const canUploadAttachments = role === "Admin" || (role === "IT Support" && assignedToId === currentUserId) || (role === "Employee" && creatorId === currentUserId);
  const canDeleteAttachments = role === "Admin";
  const canRequestTroubleshooting = role === "IT Support" && assignedToId === currentUserId;

  const showEditPanel = useMemo(() => canEdit && ticket, [canEdit, ticket]);

  const closeAiModal = () => {
    setAiModalOpen(false);
    setAiInsight(null);
  };

  const handleAiInsight = async (action) => {
    setAiError("");
    setAiActionLoading(action);

    const actionMap = {
      summary: ticketService.getTicketAiSummary,
      priority: ticketService.getTicketAiPriorityRecommendation,
      troubleshooting: ticketService.getTicketAiTroubleshooting,
    };

    try {
      const response = await actionMap[action](id);
      setAiInsight({ kind: action, ...(response.data?.data || {}) });
      setAiModalOpen(true);
    } catch (err) {
      setAiError(err?.response?.data?.message || "Unable to generate AI insight.");
    } finally {
      setAiActionLoading("");
    }
  };

  const handleApplyPriorityRecommendation = () => {
    if (!aiInsight?.recommendedPriority) {
      return;
    }

    setForm((current) => ({
      ...current,
      priority: aiInsight.recommendedPriority,
    }));
    setSuccess(`Applied AI priority recommendation: ${aiInsight.recommendedPriority}. Save changes to persist it.`);
    closeAiModal();
  };

  const loadTicketDetails = useCallback(async () => {
    const [ticketResponse, commentsResponse, historyResponse] = await Promise.all([
      ticketService.getTicketById(id),
      ticketService.getTicketComments(id),
      ticketService.getTicketHistory(id),
    ]);

    const ticketRecord = readRecord(ticketResponse, "ticket");
    setTicket(ticketRecord);
    setHistory(readCollection(historyResponse, "history"));
    setComments(readCollection(commentsResponse, "comments"));
    setForm({
      title: ticketRecord?.title || "",
      description: ticketRecord?.description || "",
      categoryId: ticketRecord?.categoryId || "",
      priority: ticketRecord?.priority || "Medium",
      status: ticketRecord?.status || "Open",
      comment: "",
    });

    const creatorIdForTicket = Number(ticketRecord?.creator?.id ?? ticketRecord?.createdBy ?? 0) || null;
    const assignedToIdForTicket = Number(ticketRecord?.assignedTo ?? ticketRecord?.assignedUser?.id ?? 0) || null;
    if (role === "Admin" || (role === "IT Support" && assignedToIdForTicket === currentUserId)) {
      const notesResponse = await ticketService.getInternalNotes(id);
      setInternalNotes(readCollection(notesResponse, "notes"));
    } else {
      setInternalNotes([]);
    }

    if (role === "Admin" || (role === "IT Support" && assignedToIdForTicket === currentUserId) || (role === "Employee" && creatorIdForTicket === currentUserId) || (role === "Manager" && creatorIdForTicket === currentUserId)) {
      const attachmentsResponse = await ticketService.getAttachments(id);
      setAttachments(readCollection(attachmentsResponse, "attachments"));
    } else {
      setAttachments([]);
    }
  }, [id, role, currentUserId]);

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
      await loadTicketDetails();
      setSuccess("Ticket updated successfully.");
      setForm((current) => ({ ...current, comment: "" }));
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to update ticket.");
    } finally {
      setSaving(false);
    }
  };

  const handleStatusChange = async (nextStatus) => {
    setStatusUpdating(true);
    setError("");
    setSuccess("");

    try {
      await ticketService.updateTicketStatus(id, { status: nextStatus });
      await loadTicketDetails();
      setSuccess(`Ticket moved to ${nextStatus}.`);
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to update ticket status.");
    } finally {
      setStatusUpdating(false);
    }
  };

  const handleSendComment = async () => {
    setCommentError("");
    setCommentSuccess("");

    const text = newCommentText.trim();

    if (!text) {
      setCommentError("Comment text is required.");
      return;
    }

    setCommentSending(true);

    try {
      await ticketService.addTicketComment(id, text);
      await loadTicketDetails();
      setNewCommentText("");
      setCommentSuccess("Comment sent successfully.");
    } catch (err) {
      setCommentError(err?.response?.data?.message || "Unable to send comment.");
    } finally {
      setCommentSending(false);
    }
  };

  const handleAddInternalNote = async () => {
    setInternalNoteError("");
    setInternalNoteSuccess("");

    const text = internalNoteText.trim();

    if (!text) {
      setInternalNoteError("Internal note text is required.");
      return;
    }

    setInternalNoteSending(true);

    try {
      await ticketService.addInternalNote(id, text);
      await loadTicketDetails();
      setInternalNoteText("");
      setInternalNoteSuccess("Internal note added successfully.");
    } catch (err) {
      setInternalNoteError(err?.response?.data?.message || "Unable to add internal note.");
    } finally {
      setInternalNoteSending(false);
    }
  };

  const handleDeleteInternalNote = async (noteId) => {
    setInternalNoteDeletingId(noteId);
    setInternalNoteError("");
    setInternalNoteSuccess("");

    try {
      await ticketService.deleteInternalNote(noteId);
      await loadTicketDetails();
      setInternalNoteSuccess("Internal note deleted successfully.");
    } catch (err) {
      setInternalNoteError(err?.response?.data?.message || "Unable to delete internal note.");
    } finally {
      setInternalNoteDeletingId(null);
    }
  };

  const handleUploadAttachment = async () => {
    if (!selectedAttachmentFile) {
      setAttachmentError("Please choose a file to upload.");
      return;
    }

    const allowedTypes = ["image/jpeg", "image/png", "image/jpg", "application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"];
    const allowedExtensions = [".jpg", ".jpeg", ".png", ".pdf", ".doc", ".docx"];
    const maxSizeBytes = 10 * 1024 * 1024;
    const fileName = selectedAttachmentFile.name?.toLowerCase() || "";
    const fileType = selectedAttachmentFile.type || "";

    const hasAllowedExtension = allowedExtensions.some((extension) => fileName.endsWith(extension));
    const hasAllowedMimeType = allowedTypes.includes(fileType);

    if (!hasAllowedExtension || !hasAllowedMimeType) {
      setAttachmentError("File type not supported. Allowed: JPG, PNG, PDF, DOC, DOCX");
      return;
    }

    if (selectedAttachmentFile.size > maxSizeBytes) {
      setAttachmentError("File size exceeds 10MB limit");
      return;
    }

    setAttachmentUploading(true);
    setAttachmentError("");
    setAttachmentSuccess("");

    try {
      await ticketService.uploadAttachment(id, selectedAttachmentFile);
      await loadTicketDetails();
      setSelectedAttachmentFile(null);
      setAttachmentSuccess("Attachment uploaded successfully.");
    } catch (err) {
      setAttachmentError(err?.response?.data?.message || "Unable to upload attachment.");
    } finally {
      setAttachmentUploading(false);
    }
  };

  const handleDeleteAttachment = async (attachmentId) => {
    setAttachmentDeletingId(attachmentId);
    setAttachmentError("");
    setAttachmentSuccess("");

    try {
      await ticketService.deleteAttachment(attachmentId);
      await loadTicketDetails();
      setAttachmentSuccess("Attachment deleted successfully.");
    } catch (err) {
      setAttachmentError(err?.response?.data?.message || "Unable to delete attachment.");
    } finally {
      setAttachmentDeletingId(null);
    }
  };

  const handleDownloadAttachment = async (attachment) => {
    try {
      const response = await ticketService.downloadAttachment(attachment.id);
      const blob = new Blob([response.data], { type: response.headers?.["content-type"] || "application/octet-stream" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = attachment.fileName || "attachment";
      link.click();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setAttachmentError(err?.response?.data?.message || "Unable to download attachment.");
    }
  };

  useEffect(() => {
    async function loadTicket() {
      setLoading(true);
      setError("");

      try {
        await loadTicketDetails();
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load ticket details.");
      } finally {
        setLoading(false);
      }
    }

    loadTicket();
  }, [loadTicketDetails]);

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
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-8 shadow-[0_12px_32px_rgba(15,23,42,0.06)] text-center text-slate-600">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full border-2 border-slate-200 border-t-blue-600 animate-spin" />
          <p className="mt-4 text-sm font-medium">Loading ticket details…</p>
        </div>
      </DashboardLayout>
    );
  }

  if (error) {
    return (
      <DashboardLayout role={role} title="Ticket Details" subtitle="Review your ticket information.">
        <div className="rounded-[1.5rem] border border-rose-200 bg-rose-50 p-8 text-center text-rose-700 shadow-sm">{error}</div>
      </DashboardLayout>
    );
  }

  if (!ticket) {
    return (
      <DashboardLayout role={role} title="Ticket Details" subtitle="Review your ticket information.">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-8 shadow-[0_12px_32px_rgba(15,23,42,0.06)] text-center text-slate-600">Ticket not found.</div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout role={role} title={`Ticket ${ticket.ticketNumber}`} subtitle="View full ticket details.">
      <div className="space-y-6">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
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
              <p className="mt-2 text-sm text-slate-700">{ticket.assignedSupportName || ticket.assignedUser?.fullName || "Unassigned"}</p>
            </div>
          </div>
        </div>

        <div className="rounded-[1.5rem] border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-indigo-700">AI Assistance</p>
              <h3 className="mt-2 text-lg font-semibold text-slate-900">Generate guidance from the live ticket record</h3>
              <p className="mt-1 max-w-3xl text-sm text-slate-500">
                Summary and priority recommendations are available to the current viewer. Troubleshooting suggestions are shown for the assigned IT Support technician.
              </p>
            </div>
            <div className="flex flex-wrap gap-2">
              <button
                type="button"
                onClick={() => handleAiInsight("summary")}
                disabled={aiActionLoading !== ""}
                className="rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                {aiActionLoading === "summary" ? "Generating summary…" : "AI Summary"}
              </button>
              <button
                type="button"
                onClick={() => handleAiInsight("priority")}
                disabled={aiActionLoading !== ""}
                className="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                {aiActionLoading === "priority" ? "Analyzing priority…" : "AI Priority Recommendation"}
              </button>
              {canRequestTroubleshooting ? (
                <button
                  type="button"
                  onClick={() => handleAiInsight("troubleshooting")}
                  disabled={aiActionLoading !== ""}
                  className="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                >
                  {aiActionLoading === "troubleshooting" ? "Preparing steps…" : "AI Troubleshooting"}
                </button>
              ) : null}
            </div>
          </div>
          {aiError ? <p className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{aiError}</p> : null}
        </div>

        <div className="grid gap-6 lg:grid-cols-[1.8fr_1fr]">
          <div className="space-y-6">
            <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 className="text-lg font-semibold text-slate-900">Ticket details</h3>
                  <p className="mt-1 text-sm text-slate-500">Review the ticket description, history and current status.</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  {(canStartWork || canResolve || canClose) && (
                    <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                      {canStartWork ? (
                        <button
                          type="button"
                          onClick={() => handleStatusChange("In Progress")}
                          disabled={statusUpdating}
                          className="rounded-2xl bg-amber-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                        >
                          {statusUpdating ? "Working…" : "Start Work"}
                        </button>
                      ) : null}
                      {canResolve ? (
                        <button
                          type="button"
                          onClick={() => handleStatusChange("Resolved")}
                          disabled={statusUpdating}
                          className="rounded-2xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                        >
                          {statusUpdating ? "Working…" : "Resolve"}
                        </button>
                      ) : null}
                      {canClose ? (
                        <button
                          type="button"
                          onClick={() => handleStatusChange("Closed")}
                          disabled={statusUpdating}
                          className="rounded-2xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                        >
                          {statusUpdating ? "Working…" : "Close Ticket"}
                        </button>
                      ) : null}
                    </div>
                  )}
                  <button
                    type="button"
                    onClick={() => navigate(-1)}
                    className="inline-flex items-center justify-center rounded-3xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                  >
                    Back to tickets
                  </button>
                </div>
              </div>
              <p className="mt-4 text-sm leading-7 text-slate-700 whitespace-pre-line">{ticket.description}</p>
            </div>

            {showEditPanel ? (
              <div className="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-6 shadow-sm">
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
                <h3 className="text-lg font-semibold text-slate-900">History</h3>
                <p className="mt-1 text-sm text-slate-500">Newest events first. Full audit trail of ticket actions.</p>
              </div>
            </div>

            {history.length > 0 ? (
              <div className="space-y-0">
                {history.map((entry, index) => (
                  <div key={entry.id} className="relative pl-8 pb-6">
                    {index < history.length - 1 ? <span className="absolute left-[11px] top-5 h-full w-px bg-slate-300" /> : null}
                    <span className="absolute left-0 top-1.5 h-6 w-6 rounded-full border-2 border-slate-300 bg-white" />

                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <p className="text-sm font-semibold text-slate-900">{entry.comment || "Ticket updated"}</p>
                          <p className="text-sm text-slate-600">{entry.changedBy?.fullName || entry.userName || "Unknown user"}</p>
                          <p className="text-sm text-slate-500">{new Date(entry.changedAt).toLocaleString()}</p>
                        </div>
                        {entry.oldStatus || entry.newStatus ? (
                          <span className="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">
                            {(entry.oldStatus || "-") + " → " + (entry.newStatus || "-")}
                          </span>
                        ) : null}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">No history entries found for this ticket yet.</div>
            )}
          </div>

          <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-5">
              <h3 className="text-lg font-semibold text-slate-900">Comments</h3>
              <p className="mt-1 text-sm text-slate-500">Conversation history for this ticket.</p>
            </div>

            {comments.length > 0 ? (
              <div className="space-y-4">
                {comments.map((comment) => (
                  <div key={comment.id} className="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-semibold text-slate-900">{comment.user?.fullName || comment.userName || "Unknown user"}</p>
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

            <div className="mt-5 border-t border-slate-200 pt-5">
              {canAddComment ? (
                <>
                  <label className="block text-sm text-slate-700">
                    Write a comment
                    <textarea
                      rows={4}
                      value={newCommentText}
                      onChange={(event) => setNewCommentText(event.target.value)}
                      maxLength={2000}
                      placeholder="Share an update on this ticket"
                      className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                    />
                  </label>

                  <div className="mt-4 flex items-center justify-between gap-3">
                    <div className="space-y-1">
                      {commentSuccess ? <p className="text-sm text-emerald-700">{commentSuccess}</p> : null}
                      {commentError ? <p className="text-sm text-rose-700">{commentError}</p> : null}
                    </div>
                    <button
                      type="button"
                      onClick={handleSendComment}
                      disabled={commentSending}
                      className="inline-flex items-center justify-center rounded-3xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                    >
                      {commentSending ? "Sending…" : "Send Comment"}
                    </button>
                  </div>
                </>
              ) : (
                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">You can view comments for this ticket, but your role cannot add new comments.</div>
              )}
            </div>
          </div>
        </div>

        {canViewInternalNotes ? (
          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="mb-5">
              <h3 className="text-lg font-semibold text-slate-900">Internal Notes</h3>
              <p className="mt-1 text-sm text-slate-500">Private notes visible only to Admin and the assigned IT Support technician.</p>
            </div>

            {internalNotes.length > 0 ? (
              <div className="space-y-4">
                {internalNotes.map((note) => (
                  <div key={note.id} className="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-semibold text-slate-900">{note.user?.fullName || "Unknown user"}</p>
                        <p className="text-sm text-slate-500">{new Date(note.createdAt).toLocaleString()}</p>
                      </div>
                      {canDeleteInternalNotes ? (
                        <button
                          type="button"
                          onClick={() => handleDeleteInternalNote(note.id)}
                          disabled={internalNoteDeletingId === note.id}
                          className="rounded-2xl bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-200 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                        >
                          {internalNoteDeletingId === note.id ? "Deleting..." : "Delete"}
                        </button>
                      ) : null}
                    </div>
                    <p className="mt-3 text-sm leading-6 text-slate-700 whitespace-pre-line">{note.note}</p>
                  </div>
                ))}
              </div>
            ) : (
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">No internal notes have been added for this ticket yet.</div>
            )}

            <div className="mt-5 border-t border-slate-200 pt-5">
              <label className="block text-sm text-slate-700">
                Add internal note
                <textarea
                  rows={4}
                  value={internalNoteText}
                  onChange={(event) => setInternalNoteText(event.target.value)}
                  maxLength={5000}
                  placeholder="Write a private internal note"
                  className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                />
              </label>

              <div className="mt-4 flex items-center justify-between gap-3">
                <div className="space-y-1">
                  {internalNoteSuccess ? <p className="text-sm text-emerald-700">{internalNoteSuccess}</p> : null}
                  {internalNoteError ? <p className="text-sm text-rose-700">{internalNoteError}</p> : null}
                </div>
                <button
                  type="button"
                  onClick={handleAddInternalNote}
                  disabled={internalNoteSending}
                  className="inline-flex items-center justify-center rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                >
                  {internalNoteSending ? "Adding..." : "Add Note"}
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {canViewAttachments ? (
          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h3 className="text-lg font-semibold text-slate-900">Attachments</h3>
                <p className="mt-1 text-sm text-slate-500">Upload and review supporting files for this ticket.</p>
              </div>
            </div>

            {attachments.length > 0 ? (
              <div className="space-y-3">
                {attachments.map((attachment) => (
                  <div key={attachment.id} className="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                      <div className="grid h-12 w-12 place-items-center rounded-3xl bg-slate-100 text-slate-700">
                        <span className="text-sm font-semibold uppercase">{attachment.mimeType?.split("/")[1] || "FILE"}</span>
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-slate-900">{attachment.fileName}</p>
                        <p className="mt-1 text-sm text-slate-500">
                          {attachment.user?.fullName || "Unknown user"} • {formatDateTime(attachment.uploadedAt)} • {(attachment.sizeBytes / 1024).toFixed(1)} KB
                        </p>
                      </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <button
                        type="button"
                        onClick={() => handleDownloadAttachment(attachment)}
                        className="rounded-2xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                      >
                        Download
                      </button>
                      {canDeleteAttachments ? (
                        <button
                          type="button"
                          onClick={() => handleDeleteAttachment(attachment.id)}
                          disabled={attachmentDeletingId === attachment.id}
                          className="rounded-2xl bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-200 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                        >
                          {attachmentDeletingId === attachment.id ? "Deleting..." : "Delete"}
                        </button>
                      ) : null}
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">No files attached to this ticket.</div>
            )}

            {canUploadAttachments ? (
              <div className="mt-5 border-t border-slate-200 pt-5">
                <label className="block text-sm text-slate-700">
                  Upload file
                  <input
                    type="file"
                    onChange={(event) => setSelectedAttachmentFile(event.target.files?.[0] || null)}
                    className="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                  />
                </label>

                <div className="mt-4 flex items-center justify-between gap-3">
                  <div className="space-y-1">
                    {attachmentSuccess ? <p className="text-sm text-emerald-700">{attachmentSuccess}</p> : null}
                    {attachmentError ? <p className="text-sm text-rose-700">{attachmentError}</p> : null}
                  </div>
                  <button
                    type="button"
                    onClick={handleUploadAttachment}
                    disabled={attachmentUploading}
                    className="inline-flex items-center justify-center rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                  >
                    {attachmentUploading ? "Uploading..." : "Upload Attachment"}
                  </button>
                </div>
              </div>
            ) : null}
          </div>
        ) : null}

        {aiModalOpen && aiInsight ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm">
            <div className="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.28)]">
              <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div>
                  <p className="text-sm font-semibold uppercase tracking-[0.22em] text-indigo-700">AI Result</p>
                  <h3 className="mt-2 text-2xl font-semibold text-slate-900">
                    {aiInsight.kind === "summary"
                      ? "Ticket Summary"
                      : aiInsight.kind === "priority"
                        ? "Priority Recommendation"
                        : "Troubleshooting Suggestions"}
                  </h3>
                  <p className="mt-1 text-sm text-slate-500">
                    Ticket {aiInsight.ticketNumber} • {aiInsight.provider || "AI"}{aiInsight.model ? ` • ${aiInsight.model}` : ""}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={closeAiModal}
                  className="rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                >
                  Close
                </button>
              </div>

              <div className="max-h-[calc(90vh-120px)] overflow-y-auto px-6 py-6">
                <div className="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                  <div className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    {aiInsight.kind === "summary" ? (
                      <>
                        <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Summary</p>
                        <p className="mt-3 text-sm leading-7 text-slate-700 whitespace-pre-line">{aiInsight.summary}</p>
                        {Array.isArray(aiInsight.highlights) && aiInsight.highlights.length > 0 ? (
                          <div className="mt-5">
                            <p className="text-sm font-semibold text-slate-900">Highlights</p>
                            <ul className="mt-3 space-y-2">
                              {aiInsight.highlights.map((highlight, index) => (
                                <li key={`${highlight}-${index}`} className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                                  {highlight}
                                </li>
                              ))}
                            </ul>
                          </div>
                        ) : null}
                      </>
                    ) : null}

                    {aiInsight.kind === "priority" ? (
                      <>
                        <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Recommendation</p>
                        <div className="mt-3 inline-flex rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                          {aiInsight.recommendedPriority || "Medium"}
                        </div>
                        <p className="mt-4 text-sm leading-7 text-slate-700 whitespace-pre-line">{aiInsight.explanation}</p>
                      </>
                    ) : null}

                    {aiInsight.kind === "troubleshooting" ? (
                      <>
                        <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Practical guidance</p>
                        {aiInsight.summary ? <p className="mt-3 text-sm leading-7 text-slate-700 whitespace-pre-line">{aiInsight.summary}</p> : null}
                        {Array.isArray(aiInsight.suggestions) && aiInsight.suggestions.length > 0 ? (
                          <ol className="mt-5 space-y-3">
                            {aiInsight.suggestions.map((suggestion, index) => (
                              <li key={`${suggestion}-${index}`} className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                                <span className="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                                  {index + 1}
                                </span>
                                {suggestion}
                              </li>
                            ))}
                          </ol>
                        ) : null}
                      </>
                    ) : null}
                  </div>

                  <div className="space-y-4">
                    <div className="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                      <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Live ticket context</p>
                      <dl className="mt-4 space-y-3 text-sm text-slate-700">
                        <div className="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                          <dt className="text-slate-500">Title</dt>
                          <dd className="text-right font-medium text-slate-900">{ticket.title}</dd>
                        </div>
                        <div className="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                          <dt className="text-slate-500">Category</dt>
                          <dd className="text-right font-medium text-slate-900">{ticket.category?.categoryName || "-"}</dd>
                        </div>
                        <div className="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                          <dt className="text-slate-500">Priority</dt>
                          <dd className="text-right font-medium text-slate-900">{ticket.priority}</dd>
                        </div>
                        <div className="flex items-start justify-between gap-4">
                          <dt className="text-slate-500">Status</dt>
                          <dd className="text-right font-medium text-slate-900">{ticket.status}</dd>
                        </div>
                      </dl>
                    </div>

                    {aiInsight.kind === "priority" && canEdit ? (
                      <button
                        type="button"
                        onClick={handleApplyPriorityRecommendation}
                        className="w-full rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
                      >
                        Apply Recommendation
                      </button>
                    ) : null}
                  </div>
                </div>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </DashboardLayout>
  );
}

export default TicketDetails;

import { useEffect, useMemo, useState } from "react";
import DashboardLayout from "../../components/DashboardLayout";
import DataTable from "../../components/DataTable";
import api, { readCollection } from "../../api/axios";

function UsersPage() {
  const localUser = JSON.parse(localStorage.getItem("user") || "{}") || {};
  const currentUserId = Number(localUser?.id || 0) || null;

  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [selectedUser, setSelectedUser] = useState(null);
  const [editingUser, setEditingUser] = useState(null);
  const [editForm, setEditForm] = useState({
    fullName: "",
    email: "",
    phone: "",
    roleId: "",
    status: "Active",
  });
  const [saving, setSaving] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});

  const roleChoices = useMemo(() => {
    const map = new Map();

    users.forEach((user) => {
      const roleId = user?.role?.id;
      const roleName = user?.role?.roleName;
      if (roleId && roleName && !map.has(roleId)) {
        map.set(roleId, roleName);
      }
    });

    return Array.from(map.entries())
      .map(([id, name]) => ({ id, name }))
      .sort((a, b) => a.name.localeCompare(b.name));
  }, [users]);

  useEffect(() => {
    const loadUsers = async () => {
      setLoading(true);
      setError("");

      try {
        const response = await api.get("/users", { cache: false });
        setUsers(readCollection(response, "users"));
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load users.");
      } finally {
        setLoading(false);
      }
    };

    loadUsers();
  }, []);

  const roleOptions = useMemo(() => {
    return [...new Set(users.map((user) => user?.role?.roleName).filter(Boolean))].sort();
  }, [users]);

  const filteredUsers = useMemo(() => {
    const query = search.trim().toLowerCase();

    return users.filter((user) => {
      const matchesSearch = !query || [user.fullName, user.email, user.phone, user.role?.roleName]
        .some((value) => String(value || "").toLowerCase().includes(query));

      const matchesRole = !roleFilter || user?.role?.roleName === roleFilter;
      const matchesStatus = !statusFilter || user?.status === statusFilter;

      return matchesSearch && matchesRole && matchesStatus;
    });
  }, [users, search, roleFilter, statusFilter]);

  const closeModals = () => {
    setSelectedUser(null);
    setEditingUser(null);
    setFieldErrors({});
  };

  const openView = async (id) => {
    setError("");

    try {
      const response = await api.get(`/users/${id}`, { cache: false });
      setSelectedUser(response?.data?.user || null);
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to load user details.");
    }
  };

  const openEdit = async (id) => {
    setError("");
    setFieldErrors({});

    try {
      const response = await api.get(`/users/${id}`, { cache: false });
      const user = response?.data?.user;

      if (!user) {
        return;
      }

      setEditingUser(user);
      setEditForm({
        fullName: user.fullName || "",
        email: user.email || "",
        phone: user.phone || "",
        roleId: user?.role?.id ? String(user.role.id) : "",
        status: user.status || "Active",
      });
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to load user for editing.");
    }
  };

  const handleEditChange = (event) => {
    const { name, value } = event.target;
    setEditForm((current) => ({ ...current, [name]: value }));
  };

  const saveEdit = async (event) => {
    event.preventDefault();

    if (!editingUser) {
      return;
    }

    setSaving(true);
    setFieldErrors({});
    setError("");

    try {
      const response = await api.put(`/users/${editingUser.id}`, {
        fullName: editForm.fullName,
        email: editForm.email,
        phone: editForm.phone,
        roleId: Number(editForm.roleId),
        status: editForm.status,
      });

      const updated = response?.data?.user;

      if (updated) {
        setUsers((current) => current.map((user) => (user.id === updated.id ? updated : user)));
      }

      setEditingUser(null);
    } catch (err) {
      const errors = err?.response?.data?.errors;
      if (errors && typeof errors === "object") {
        setFieldErrors(errors);
      }
      setError(err?.response?.data?.message || "Unable to update user.");
    } finally {
      setSaving(false);
    }
  };

  const deleteUser = async (user) => {
    if (!user) {
      return;
    }

    if (!window.confirm(`Delete user ${user.fullName}? This action cannot be undone.`)) {
      return;
    }

    setDeletingId(user.id);
    setError("");

    try {
      await api.delete(`/users/${user.id}`);
      setUsers((current) => current.filter((item) => item.id !== user.id));

      if (selectedUser?.id === user.id) {
        setSelectedUser(null);
      }

      if (editingUser?.id === user.id) {
        setEditingUser(null);
      }
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to delete user.");
    } finally {
      setDeletingId(null);
    }
  };

  const rows = filteredUsers.map((user, index) => ({
    id: user.id,
    no: <span className="text-xs font-semibold text-slate-500">{index + 1}</span>,
    fullName: (
      <div>
        <p className="font-semibold text-slate-900">{user.fullName}</p>
        <p className="text-xs text-slate-500">ID #{user.id}</p>
      </div>
    ),
    email: user.email,
    phone: user.phone || "-",
    role: user?.role?.roleName || "-",
    status: (
      <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] ${user.status === "Active" ? "bg-emerald-100 text-emerald-700" : "bg-rose-100 text-rose-700"}`}>
        {user.status || "Unknown"}
      </span>
    ),
    createdAt: user.createdAt ? new Date(user.createdAt).toLocaleDateString() : "-",
    actions: (
      <div className="flex flex-wrap gap-2">
        <button type="button" onClick={() => openView(user.id)} className="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
          View
        </button>
        <button type="button" onClick={() => openEdit(user.id)} className="rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
          Edit
        </button>
        <button
          type="button"
          onClick={() => deleteUser(user)}
          disabled={deletingId === user.id || user.id === currentUserId}
          className="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {deletingId === user.id ? "Deleting..." : "Delete"}
        </button>
      </div>
    ),
  }));

  return (
    <DashboardLayout role="Admin" title="Users" subtitle="View and monitor registered user accounts across the service desk.">
      <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h2 className="text-lg font-semibold text-slate-900">Users Directory</h2>
            <p className="mt-1 text-sm text-slate-500">Search and filter user accounts by role and status.</p>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <div className="relative">
              <input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search users..."
                className="w-64 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
              />
            </div>

            <select value={roleFilter} onChange={(event) => setRoleFilter(event.target.value)} className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100">
              <option value="">All roles</option>
              {roleOptions.map((role) => (
                <option key={role} value={role}>{role}</option>
              ))}
            </select>

            <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)} className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100">
              <option value="">All statuses</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Total users</p>
            <p className="mt-2 text-2xl font-semibold text-slate-900">{users.length}</p>
          </div>
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Filtered users</p>
            <p className="mt-2 text-2xl font-semibold text-slate-900">{filteredUsers.length}</p>
          </div>
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Active users</p>
            <p className="mt-2 text-2xl font-semibold text-slate-900">{users.filter((item) => item.status === "Active").length}</p>
          </div>
        </div>

        {error ? <div className="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div> : null}

        {loading ? (
          <div className="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">Loading users...</div>
        ) : (
          <div className="mt-5">
            <DataTable
              columns={[
                { label: "#", key: "no" },
                { label: "Full Name", key: "fullName" },
                { label: "Email", key: "email" },
                { label: "Phone", key: "phone" },
                { label: "Role", key: "role" },
                { label: "Status", key: "status" },
                { label: "Created", key: "createdAt" },
                { label: "Actions", key: "actions" },
              ]}
              rows={rows}
              emptyState="No users match your current filters."
            />
          </div>
        )}

        {selectedUser ? (
          <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/35 p-4" onClick={closeModals}>
            <div className="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl" onClick={(event) => event.stopPropagation()}>
              <div className="flex items-start justify-between gap-4">
                <div>
                  <h3 className="text-lg font-semibold text-slate-900">User Details</h3>
                  <p className="text-sm text-slate-500">Read-only information for the selected account.</p>
                </div>
                <button type="button" onClick={closeModals} className="rounded-xl border border-slate-200 px-3 py-1 text-sm text-slate-600 hover:bg-slate-50">Close</button>
              </div>

              <dl className="mt-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div><dt className="text-slate-500">ID</dt><dd className="mt-1 font-medium text-slate-900">{selectedUser.id}</dd></div>
                <div><dt className="text-slate-500">Role</dt><dd className="mt-1 font-medium text-slate-900">{selectedUser?.role?.roleName || "-"}</dd></div>
                <div><dt className="text-slate-500">Full Name</dt><dd className="mt-1 font-medium text-slate-900">{selectedUser.fullName}</dd></div>
                <div><dt className="text-slate-500">Status</dt><dd className="mt-1 font-medium text-slate-900">{selectedUser.status || "-"}</dd></div>
                <div><dt className="text-slate-500">Email</dt><dd className="mt-1 font-medium text-slate-900 break-all">{selectedUser.email}</dd></div>
                <div><dt className="text-slate-500">Phone</dt><dd className="mt-1 font-medium text-slate-900">{selectedUser.phone || "-"}</dd></div>
              </dl>
            </div>
          </div>
        ) : null}

        {editingUser ? (
          <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/35 p-4" onClick={closeModals}>
            <form className="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl" onClick={(event) => event.stopPropagation()} onSubmit={saveEdit}>
              <div className="flex items-start justify-between gap-4">
                <div>
                  <h3 className="text-lg font-semibold text-slate-900">Edit User</h3>
                  <p className="text-sm text-slate-500">Update account details, role, and status.</p>
                </div>
                <button type="button" onClick={closeModals} className="rounded-xl border border-slate-200 px-3 py-1 text-sm text-slate-600 hover:bg-slate-50">Close</button>
              </div>

              <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label className="text-sm font-medium text-slate-700">Full Name</label>
                  <input name="fullName" value={editForm.fullName} onChange={handleEditChange} className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100" />
                  {fieldErrors?.fullName?.[0] ? <p className="mt-1 text-xs text-rose-600">{fieldErrors.fullName[0]}</p> : null}
                </div>
                <div>
                  <label className="text-sm font-medium text-slate-700">Email</label>
                  <input name="email" value={editForm.email} onChange={handleEditChange} className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100" />
                  {fieldErrors?.email?.[0] ? <p className="mt-1 text-xs text-rose-600">{fieldErrors.email[0]}</p> : null}
                </div>
                <div>
                  <label className="text-sm font-medium text-slate-700">Phone</label>
                  <input name="phone" value={editForm.phone} onChange={handleEditChange} className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100" />
                  {fieldErrors?.phone?.[0] ? <p className="mt-1 text-xs text-rose-600">{fieldErrors.phone[0]}</p> : null}
                </div>
                <div>
                  <label className="text-sm font-medium text-slate-700">Role</label>
                  <select name="roleId" value={editForm.roleId} onChange={handleEditChange} className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <option value="">Select role</option>
                    {roleChoices.map((roleChoice) => (
                      <option key={roleChoice.id} value={roleChoice.id}>{roleChoice.name}</option>
                    ))}
                  </select>
                  {fieldErrors?.roleId?.[0] ? <p className="mt-1 text-xs text-rose-600">{fieldErrors.roleId[0]}</p> : null}
                </div>
                <div>
                  <label className="text-sm font-medium text-slate-700">Status</label>
                  <select name="status" value={editForm.status} onChange={handleEditChange} className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                  {fieldErrors?.status?.[0] ? <p className="mt-1 text-xs text-rose-600">{fieldErrors.status[0]}</p> : null}
                </div>
              </div>

              <div className="mt-6 flex justify-end gap-3">
                <button type="button" onClick={closeModals} className="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" disabled={saving} className="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400">{saving ? "Saving..." : "Save Changes"}</button>
              </div>
            </form>
          </div>
        ) : null}
      </div>
    </DashboardLayout>
  );
}

export default UsersPage;
import { useEffect, useMemo, useState } from "react";
import { FiCheckCircle, FiKey, FiMail, FiPhone, FiSave, FiShield, FiUser } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import profileService from "../../services/profileService";

function formatDate(value) {
  if (!value) {
    return "-";
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return "-";
  }

  return date.toLocaleDateString(undefined, {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function ProfilePage() {
  const storedUser = JSON.parse(localStorage.getItem("user") || "{}") || {};
  const role = storedUser.role || "Employee";

  const [loading, setLoading] = useState(true);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);
  const [error, setError] = useState("");
  const [profileMessage, setProfileMessage] = useState("");
  const [passwordMessage, setPasswordMessage] = useState("");
  const [profile, setProfile] = useState(null);

  const [personalForm, setPersonalForm] = useState({
    fullName: "",
    phone: "",
  });

  const [passwordForm, setPasswordForm] = useState({
    currentPassword: "",
    newPassword: "",
    confirmPassword: "",
  });

  const [fieldErrors, setFieldErrors] = useState({});

  useEffect(() => {
    const loadProfile = async () => {
      setLoading(true);
      setError("");

      try {
        const userProfile = await profileService.getProfile();
        setProfile(userProfile);
        setPersonalForm({
          fullName: userProfile?.fullName || "",
          phone: userProfile?.phone || "",
        });
      } catch (err) {
        setError(err?.response?.data?.message || "Unable to load your profile.");
      } finally {
        setLoading(false);
      }
    };

    loadProfile();
  }, []);

  const initials = useMemo(() => {
    const name = (profile?.fullName || "User").trim();
    const parts = name.split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
      return "U";
    }

    const first = parts[0]?.charAt(0) || "";
    const second = parts.length > 1 ? parts[1]?.charAt(0) || "" : "";
    return `${first}${second}`.toUpperCase();
  }, [profile?.fullName]);

  const handlePersonalChange = (event) => {
    const { name, value } = event.target;
    setPersonalForm((current) => ({ ...current, [name]: value }));
  };

  const handlePasswordChange = (event) => {
    const { name, value } = event.target;
    setPasswordForm((current) => ({ ...current, [name]: value }));
  };

  const saveProfile = async (event) => {
    event.preventDefault();
    setSavingProfile(true);
    setFieldErrors({});
    setProfileMessage("");
    setPasswordMessage("");
    setError("");

    try {
      const updatedProfile = await profileService.updateProfile({
        fullName: personalForm.fullName,
        phone: personalForm.phone,
      });

      setProfile(updatedProfile);
      setPersonalForm({
        fullName: updatedProfile?.fullName || "",
        phone: updatedProfile?.phone || "",
      });
      setProfileMessage("Profile updated successfully.");

      const localUser = JSON.parse(localStorage.getItem("user") || "{}") || {};
      localStorage.setItem(
        "user",
        JSON.stringify({
          ...localUser,
          fullName: updatedProfile?.fullName || localUser.fullName,
          email: updatedProfile?.email || localUser.email,
          role: updatedProfile?.role?.name || localUser.role,
        })
      );
    } catch (err) {
      const validationErrors = err?.response?.data?.errors;
      if (validationErrors && typeof validationErrors === "object") {
        setFieldErrors(validationErrors);
      }
      setError(err?.response?.data?.message || "Unable to update profile.");
    } finally {
      setSavingProfile(false);
    }
  };

  const changePassword = async (event) => {
    event.preventDefault();
    setSavingPassword(true);
    setFieldErrors({});
    setProfileMessage("");
    setPasswordMessage("");
    setError("");

    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      setFieldErrors({ newPassword: ["New password confirmation does not match."] });
      setSavingPassword(false);
      return;
    }

    try {
      await profileService.changePassword({
        currentPassword: passwordForm.currentPassword,
        newPassword: passwordForm.newPassword,
        newPassword_confirmation: passwordForm.confirmPassword,
      });

      setPasswordForm({
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
      });
      setPasswordMessage("Password changed successfully.");
    } catch (err) {
      const validationErrors = err?.response?.data?.errors;
      if (validationErrors && typeof validationErrors === "object") {
        setFieldErrors(validationErrors);
      }
      setError(err?.response?.data?.message || "Unable to change password.");
    } finally {
      setSavingPassword(false);
    }
  };

  const renderError = (name) => {
    const errors = fieldErrors?.[name];
    if (!Array.isArray(errors) || errors.length === 0) {
      return null;
    }

    return <p className="mt-1 text-xs text-rose-600">{errors[0]}</p>;
  };

  if (loading) {
    return (
      <DashboardLayout role={role} title="Profile" subtitle="Manage your personal information and account security.">
        <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-8 text-center text-sm text-slate-600 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
          Loading profile...
        </div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout role={role} title="Profile" subtitle="Manage your personal information and account security.">
      <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <div className="space-y-6">
          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-2xl font-semibold text-white">
              {initials}
            </div>
            <h2 className="mt-4 text-center text-xl font-semibold text-slate-900">{profile?.fullName || "User"}</h2>
            <p className="mt-1 text-center text-sm font-medium text-slate-500">{profile?.role?.name || role}</p>
            <div className="mt-5 flex items-center justify-center">
              <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] ${profile?.status === "Active" ? "bg-emerald-100 text-emerald-700" : "bg-rose-100 text-rose-700"}`}>
                {profile?.status || "Unknown"}
              </span>
            </div>

            <div className="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
              <div className="flex items-center gap-2">
                <FiMail className="text-slate-500" />
                <span>{profile?.email || "-"}</span>
              </div>
              <div className="flex items-center gap-2">
                <FiPhone className="text-slate-500" />
                <span>{profile?.phone || "-"}</span>
              </div>
              <div className="flex items-center gap-2">
                <FiShield className="text-slate-500" />
                <span>{profile?.status || "Unknown status"}</span>
              </div>
              <div className="flex items-center gap-2">
                <FiCheckCircle className="text-slate-500" />
                <span>{profile?.emailVerifiedAt ? "Email verified" : "Email not verified"}</span>
              </div>
            </div>
          </div>

          <div className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <h3 className="text-base font-semibold text-slate-900">Account Information</h3>
            <dl className="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt className="text-slate-500">Role</dt>
                <dd className="mt-1 font-medium text-slate-800">{profile?.role?.name || "-"}</dd>
              </div>
              <div>
                <dt className="text-slate-500">Account status</dt>
                <dd className="mt-1 font-medium text-slate-800">{profile?.status || "-"}</dd>
              </div>
              <div>
                <dt className="text-slate-500">Created on</dt>
                <dd className="mt-1 font-medium text-slate-800">{formatDate(profile?.createdAt)}</dd>
              </div>
            </dl>
          </div>
        </div>

        <div className="space-y-6">
          <form onSubmit={saveProfile} className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="mb-4 flex items-center gap-2">
              <FiUser className="text-blue-600" />
              <h3 className="text-lg font-semibold text-slate-900">Personal Information</h3>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <label className="text-sm font-medium text-slate-700">Full Name</label>
                <input
                  name="fullName"
                  value={personalForm.fullName}
                  onChange={handlePersonalChange}
                  className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
                  placeholder="Your full name"
                />
                {renderError("fullName")}
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">Email</label>
                <input
                  value={profile?.email || ""}
                  readOnly
                  className="mt-1 w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600"
                />
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">Phone</label>
                <input
                  name="phone"
                  value={personalForm.phone}
                  onChange={handlePersonalChange}
                  className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
                  placeholder="03xxxxxx"
                />
                {renderError("phone")}
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">Role</label>
                <input
                  value={profile?.role?.name || ""}
                  readOnly
                  className="mt-1 w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600"
                />
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">Account Status</label>
                <input
                  value={profile?.status || ""}
                  readOnly
                  className="mt-1 w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600"
                />
              </div>
            </div>

            <div className="mt-5 flex justify-end">
              <button
                type="submit"
                disabled={savingProfile}
                className="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                <FiSave />
                {savingProfile ? "Saving..." : "Save Changes"}
              </button>
            </div>
          </form>

          <form onSubmit={changePassword} className="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
            <div className="mb-4 flex items-center gap-2">
              <FiKey className="text-indigo-600" />
              <h3 className="text-lg font-semibold text-slate-900">Security</h3>
            </div>

            <div className="grid grid-cols-1 gap-4">
              <div>
                <label className="text-sm font-medium text-slate-700">Current Password</label>
                <input
                  type="password"
                  name="currentPassword"
                  value={passwordForm.currentPassword}
                  onChange={handlePasswordChange}
                  className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                />
                {renderError("currentPassword")}
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">New Password</label>
                <input
                  type="password"
                  name="newPassword"
                  value={passwordForm.newPassword}
                  onChange={handlePasswordChange}
                  className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                />
                {renderError("newPassword")}
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">Confirm New Password</label>
                <input
                  type="password"
                  name="confirmPassword"
                  value={passwordForm.confirmPassword}
                  onChange={handlePasswordChange}
                  className="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                />
              </div>
            </div>

            <div className="mt-5 flex justify-end">
              <button
                type="submit"
                disabled={savingPassword}
                className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                <FiKey />
                {savingPassword ? "Updating..." : "Change Password"}
              </button>
            </div>
          </form>
        </div>
      </div>

      {error ? <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div> : null}
      {profileMessage ? <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{profileMessage}</div> : null}
      {passwordMessage ? <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{passwordMessage}</div> : null}
    </DashboardLayout>
  );
}

export default ProfilePage;
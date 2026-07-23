import { useEffect, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { FiEye, FiEyeOff, FiShield } from "react-icons/fi";
import authService from "../services/authService";
import backgroundImage from "../assets/it-ops-bg.svg";

function ResetPassword() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    password: "",
    password_confirmation: "",
  });
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [email, setEmail] = useState("");
  const [token, setToken] = useState("");

  useEffect(() => {
    setEmail(searchParams.get("email") || "");
    setToken(searchParams.get("token") || "");
  }, [searchParams]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (!email || !token) {
      setError("The password reset link is invalid or missing required parameters.");
      return;
    }

    if (formData.password.length < 8) {
      setError("Password must be at least 8 characters long.");
      return;
    }

    if (formData.password !== formData.password_confirmation) {
      setError("Passwords do not match.");
      return;
    }

    setLoading(true);

    try {
      const response = await authService.resetPassword({
        email,
        token,
        password: formData.password,
        password_confirmation: formData.password_confirmation,
      });

      setSuccess(response?.data?.message || "Password reset successfully");
      setFormData({ password: "", password_confirmation: "" });

      setTimeout(() => {
        navigate("/");
      }, 1500);
    } catch (err) {
      const serverError =
        err?.response?.data?.message ||
        "Unable to reset your password. Please try again.";
      setError(serverError);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <div className="flex min-h-screen flex-col lg:flex-row">
        <section className="relative flex min-h-[320px] flex-1 items-end overflow-hidden bg-slate-950 lg:min-h-screen lg:w-[45%]">
          <img src={backgroundImage} alt="IT operations background" className="absolute inset-0 h-full w-full object-cover" />
          <div className="absolute inset-0 bg-slate-950/70" />
          <div className="relative z-10 p-8 sm:p-10 lg:p-12 xl:p-16">
            <div className="max-w-md">
              <p className="text-sm font-semibold uppercase tracking-[0.35em] text-blue-300">Control Center</p>
              <h1 className="mt-4 text-3xl font-semibold leading-tight text-white sm:text-4xl">
                Manage IT requests efficiently and keep your organization connected.
              </h1>
              <div className="mt-5 h-1.5 w-20 rounded-full bg-blue-500" />
            </div>
          </div>
        </section>

        <section className="flex flex-1 items-center justify-center bg-slate-50 px-4 py-10 sm:px-6 lg:px-8">
          <div className="w-full max-w-[440px] rounded-3xl border border-slate-200 bg-white p-8 shadow-[0_20px_70px_-30px_rgba(15,23,42,0.35)] sm:p-10">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <FiShield className="h-6 w-6" />
              </div>
              <div>
                <h2 className="text-2xl font-semibold text-slate-900">HelpDesk Pro</h2>
                <p className="text-sm text-slate-500">Set a new password</p>
              </div>
            </div>

            <div className="mt-8">
              <h3 className="text-2xl font-semibold text-slate-900">Reset password</h3>
              <p className="mt-2 text-sm leading-6 text-slate-500">
                Enter your new password below to complete the reset process.
              </p>
            </div>

            <form className="mt-8 space-y-4" onSubmit={handleSubmit}>
              <div>
                <label htmlFor="password" className="mb-2 block text-sm font-medium text-slate-700">New Password</label>
                <div className="relative">
                  <input
                    id="password"
                    name="password"
                    type={showPassword ? "text" : "password"}
                    value={formData.password}
                    onChange={handleChange}
                    placeholder="Enter a new password"
                    className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword((prev) => !prev)}
                    className="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-600"
                    aria-label="Toggle password visibility"
                  >
                    {showPassword ? <FiEyeOff className="h-5 w-5" /> : <FiEye className="h-5 w-5" />}
                  </button>
                </div>
              </div>

              <div>
                <label htmlFor="password_confirmation" className="mb-2 block text-sm font-medium text-slate-700">Confirm Password</label>
                <div className="relative">
                  <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type={showConfirmPassword ? "text" : "password"}
                    value={formData.password_confirmation}
                    onChange={handleChange}
                    placeholder="Confirm your new password"
                    className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                  />
                  <button
                    type="button"
                    onClick={() => setShowConfirmPassword((prev) => !prev)}
                    className="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-600"
                    aria-label="Toggle confirmation password visibility"
                  >
                    {showConfirmPassword ? <FiEyeOff className="h-5 w-5" /> : <FiEye className="h-5 w-5" />}
                  </button>
                </div>
              </div>

              {error && <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}
              {success && <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{success}</div>}

              <button
                type="submit"
                disabled={loading}
                className="flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {loading ? "Resetting password..." : "Reset Password"}
              </button>
            </form>

            <div className="mt-8 text-center text-sm text-slate-500">
              <Link to="/" className="font-semibold text-blue-600 transition hover:text-blue-700">
                Back to Login
              </Link>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}

export default ResetPassword;

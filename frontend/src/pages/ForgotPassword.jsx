import { useState } from "react";
import { Link } from "react-router-dom";
import { FiShield } from "react-icons/fi";
import authService from "../services/authService";
import backgroundImage from "../assets/it-ops-bg.svg";

function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (!email.trim()) {
      setError("Please enter your email address.");
      return;
    }

    setLoading(true);

    try {
      const response = await authService.forgotPassword({ email });
      setSuccess(response?.data?.message || "Password reset link sent successfully.");
      setEmail("");
    } catch (err) {
      const serverError =
        err?.response?.data?.message ||
        "Unable to send reset link. Please try again.";
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
                <p className="text-sm text-slate-500">Reset your password</p>
              </div>
            </div>

            <div className="mt-8">
              <h3 className="text-2xl font-semibold text-slate-900">Forgot password?</h3>
              <p className="mt-2 text-sm leading-6 text-slate-500">
                Enter your email address and we&apos;ll send you a secure reset link.
              </p>
            </div>

            <form className="mt-8 space-y-4" onSubmit={handleSubmit}>
              <div>
                <label htmlFor="email" className="mb-2 block text-sm font-medium text-slate-700">Email Address</label>
                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Enter your email address"
                  className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                />
              </div>

              {error && <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}
              {success && <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{success}</div>}

              <button
                type="submit"
                disabled={loading}
                className="flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {loading ? "Sending link..." : "Send Reset Link"}
              </button>
            </form>

            <div className="mt-8 text-center text-sm text-slate-500">
              Remember your password?{" "}
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

export default ForgotPassword;

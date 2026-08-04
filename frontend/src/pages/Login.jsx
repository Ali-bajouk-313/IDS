import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { FiEye, FiEyeOff, FiShield } from "react-icons/fi";
import api from "../api/axios";
import backgroundImage from "../assets/it-ops-bg.svg";

function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const response = await api.post("/login", {
        email,
        password,
      });

      const { token, user } = response.data;

      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(user));

      if (user.role === "Admin") {
        navigate("/admin-dashboard");
      } else if (user.role === "Manager") {
        navigate("/manager-dashboard");
      } else if (user.role === "IT Support") {
        navigate("/it-dashboard");
      } else if (user.role === "Employee") {
        navigate("/employee-dashboard");
      } else {
        navigate("/");
      }
    } catch (err) {
      const message =
        err?.response?.data?.message ||
        "Unable to sign in. Please check your credentials and try again.";

      if (message === "Please verify your email before logging in") {
        navigate(`/verify-email?email=${encodeURIComponent(email)}`);
        return;
      }

      setError(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <div className="flex min-h-screen flex-col lg:flex-row">
        <section className="relative flex min-h-[320px] flex-1 items-end overflow-hidden bg-slate-950 lg:min-h-screen lg:w-[45%]">
          <img
            src={backgroundImage}
            alt="IT operations background"
            className="absolute inset-0 h-full w-full object-cover"
          />
          <div className="absolute inset-0 bg-slate-950/70" />
          <div className="relative z-10 p-8 sm:p-10 lg:p-12 xl:p-16">
            <div className="max-w-md">
              <p className="text-sm font-semibold uppercase tracking-[0.35em] text-blue-300">
                Control Center
              </p>
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
                <p className="text-sm text-slate-500">Secure IT service operations</p>
              </div>
            </div>

            <div className="mt-8">
              <h3 className="text-2xl font-semibold text-slate-900">Sign in</h3>
              <p className="mt-2 text-sm leading-6 text-slate-500">
                Sign in to access your HelpDesk Pro account and manage service requests.
              </p>
            </div>

            <form className="mt-8 space-y-5" onSubmit={handleLogin}>
              <div>
                <label htmlFor="email" className="mb-2 block text-sm font-medium text-slate-700">
                  Email Address
                </label>
                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Enter your email address"
                  required
                  className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                />
              </div>

              <div>
                <div className="mb-2 flex items-center justify-between">
                  <label htmlFor="password" className="text-sm font-medium text-slate-700">
                    Password
                  </label>
                  <Link
                    to="/forgot-password"
                    className="text-sm font-medium text-blue-600 transition hover:text-blue-700"
                  >
                    Forgot password?
                  </Link>
                </div>
                <div className="relative">
                  <input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Enter your password"
                    required
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

              {error && (
                <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {error}
                </div>
              )}

              <button
                type="submit"
                disabled={loading}
                className="flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {loading ? "Signing in..." : "Sign In"}
              </button>
            </form>

            <div className="mt-8 text-center text-sm text-slate-500">
              Don&apos;t have an account?{" "}
              <Link to="/register" className="font-semibold text-blue-600 transition hover:text-blue-700">
                Create Account
              </Link>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}

export default Login;
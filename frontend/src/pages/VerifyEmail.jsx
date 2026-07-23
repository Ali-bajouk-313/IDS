import { useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { FiCheckCircle, FiShield } from "react-icons/fi";
import axios from "axios";
import backgroundImage from "../assets/it-ops-bg.svg";

function VerifyEmail() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState("idle");
  const [message, setMessage] = useState("");

  const email = searchParams.get("email") || "";
  const token = searchParams.get("token") || "";
  const hasToken = Boolean(token);
  const hasEmail = Boolean(email);

  const handleVerify = async () => {
    if (!email || !token) {
      setStatus("error");
      setMessage(
        !email
          ? "Unable to verify email because no email address was provided. Please open the verification link from your email."
          : "A verification token is not available. Please click the Verify Email button from the email message you received."
      );
      return;
    }

    setLoading(true);
    setStatus("loading");
    setMessage("");

    try {
      const response = await axios.get("/api/verify-email", {
        params: { email, token },
      });

      setStatus("success");
      setMessage(response?.data?.message || "Email verified successfully.");
    } catch (err) {
      setStatus("error");
      const serverMessage = err?.response?.data?.message;
      const fallbackMessage = err?.message === "Network Error"
        ? "Unable to reach the Laravel backend. Please make sure the backend server is running."
        : "Unable to verify your email right now.";
      setMessage(serverMessage || fallbackMessage);
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
                <p className="text-sm text-slate-500">Verify your email</p>
              </div>
            </div>

            {status === "success" ? (
              <div className="mt-8 space-y-5">
                <div className="flex justify-center">
                  <div className="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <FiCheckCircle className="h-8 w-8" />
                  </div>
                </div>

                <div className="text-center">
                  <h3 className="text-2xl font-semibold text-slate-900">Email verified</h3>
                  <p className="mt-2 text-sm leading-6 text-slate-500">
                    Your account is now active. You can sign in and start managing help desk requests.
                  </p>
                </div>

                <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                  {message}
                </div>

                <button
                  type="button"
                  onClick={() => navigate("/")}
                  className="flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700"
                >
                  Go to Login
                </button>
              </div>
            ) : (
              <>
                <div className="mt-8">
                  <h3 className="text-2xl font-semibold text-slate-900">Confirm your account</h3>
                  <p className="mt-2 text-sm leading-6 text-slate-500">
                    Click the button below to verify your email address and activate your HelpDesk Pro account.
                  </p>
                </div>

                <div className="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                  {email ? <p><span className="font-semibold text-slate-800">Email:</span> {email}</p> : <p>No email was provided in the verification link.</p>}
                </div>

                {message && (
                  <div className={`mt-4 rounded-2xl border px-4 py-3 text-sm ${status === "success" ? "border-emerald-200 bg-emerald-50 text-emerald-700" : "border-red-200 bg-red-50 text-red-700"}`}>
                    {message}
                  </div>
                )}

                <button
                  type="button"
                  onClick={handleVerify}
                  disabled={loading}
                  className="mt-6 flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                >
                  {loading ? "Verifying..." : hasToken ? "Verify Email" : "Resend verification link"}
                </button>
                {!hasToken && (
                  <p className="mt-3 text-center text-sm text-slate-500">
                    If you landed here without a token, please use the verification link in your email.
                  </p>
                )}
              </>
            )}

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

export default VerifyEmail;

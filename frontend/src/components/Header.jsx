import { FiMenu, FiSearch } from "react-icons/fi";
import NotificationBell from "./NotificationBell";

function Header({ title, subtitle, userName, onLogout, onMenuClick }) {
  return (
    <header className="flex min-w-0 flex-col gap-4 rounded-[1.5rem] border border-slate-200 bg-white/90 p-4 shadow-[0_12px_32px_rgba(15,23,42,0.06)] backdrop-blur sm:p-6 lg:flex-row lg:items-center lg:justify-between">
      <div className="flex min-w-0 items-start gap-3">
        <button type="button" aria-label="Open navigation" onClick={onMenuClick} className="mt-0.5 rounded-xl border border-slate-200 p-2 text-slate-700 transition hover:bg-slate-100 lg:hidden">
          <FiMenu className="h-5 w-5" />
        </button>
        <div className="min-w-0">
        <p className="text-sm font-semibold uppercase tracking-[0.24em] text-blue-600">Operations Center</p>
        <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{title}</h1>
        <p className="mt-1 break-words text-sm text-slate-500">{subtitle}</p>
        </div>
      </div>

      <div className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center lg:shrink-0">
        <div className="flex items-center gap-3">
          <label className="flex min-w-0 flex-1 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 sm:flex-none">
            <FiSearch className="h-4 w-4" />
            <input type="text" placeholder="Search" className="w-full min-w-0 border-0 bg-transparent outline-none sm:w-40" />
          </label>

          <NotificationBell />
        </div>

        <div className="flex min-w-0 items-center gap-3 rounded-2xl bg-slate-900 px-3 py-2 text-white shadow-lg shadow-slate-900/20">
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 font-semibold">
            {userName?.charAt(0) || "U"}
          </div>
          <div>
            <p className="max-w-[12rem] truncate text-sm font-semibold">{userName || "User"}</p>
            <p className="text-xs text-slate-400">Active</p>
          </div>
          <button
            type="button"
            onClick={onLogout}
            className="rounded-2xl border border-slate-700 bg-slate-800 px-3 py-1 text-xs font-semibold text-white transition hover:bg-slate-700"
          >
            Logout
          </button>
        </div>
      </div>
    </header>
  );
}

export default Header;

import { NavLink } from "react-router-dom";
import { FiBarChart2, FiHome, FiLayers, FiLifeBuoy, FiMessageSquare, FiSettings, FiShield, FiUsers, FiUserCheck, FiBell, FiClipboard, FiBriefcase, FiGrid, FiLogOut } from "react-icons/fi";

function Sidebar({ role, menus }) {
  const roleLabel = role === "Admin" ? "Administrator" : role === "Manager" ? "Manager" : role === "IT Support" ? "Support" : "Employee";

  return (
    <aside className="fixed inset-y-0 left-0 z-30 flex w-72 flex-col border-r border-slate-800 bg-slate-950 px-5 py-6 text-slate-200 shadow-2xl lg:translate-x-0">
      <div className="flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/80 px-3 py-3 shadow-lg shadow-slate-950/30">
        <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white">
          <FiShield className="h-5 w-5" />
        </div>
        <div>
          <p className="text-sm font-semibold text-white">HelpDesk Pro</p>
          <p className="text-xs text-slate-400">{roleLabel}</p>
        </div>
      </div>

      <nav className="mt-8 space-y-1">
        {menus.map((item) => {
          const Icon = item.icon;
          return (
            <NavLink
              key={item.name}
              to={item.path}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition ${isActive ? "bg-blue-600 text-white shadow-lg shadow-blue-600/20" : "text-slate-300 hover:bg-slate-800 hover:text-white"}`
              }
            >
              <Icon className="h-4 w-4" />
              <span>{item.name}</span>
            </NavLink>
          );
        })}
      </nav>

      <div className="mt-auto space-y-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
        <p className="text-sm font-semibold text-white">Service uptime</p>
        <p className="mt-1 text-xs text-slate-400">All systems operational</p>
        <div className="mt-3 h-2 rounded-full bg-slate-800">
          <div className="h-2 w-4/5 rounded-full bg-emerald-500" />
        </div>
        <button
          type="button"
          onClick={() => {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            window.location.href = "/";
          }}
          className="mt-4 flex w-full items-center gap-3 rounded-2xl bg-slate-800 px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-slate-700 hover:text-white"
        >
          <FiLogOut className="h-4 w-4" />
          <span>Logout</span>
        </button>
      </div>
    </aside>
  );
}

export default Sidebar;

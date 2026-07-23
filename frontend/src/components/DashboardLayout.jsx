import { useMemo } from "react";
import { useLocation } from "react-router-dom";
import Sidebar from "./Sidebar";
import Header from "./Header";
import { FiBarChart2, FiBell, FiBriefcase, FiClipboard, FiGrid, FiHome, FiLayers, FiLifeBuoy, FiMessageSquare, FiSettings, FiShield, FiUserCheck, FiUsers } from "react-icons/fi";

function DashboardLayout({ children, role, title, subtitle }) {
  const location = useLocation();

  const menus = useMemo(() => {
    if (role === "Admin") {
      return [
        { name: "Dashboard", path: "/admin-dashboard", icon: FiHome },
        { name: "Users", path: "/admin-dashboard/users", icon: FiUsers },
        { name: "Roles", path: "/admin-dashboard/roles", icon: FiShield },
        { name: "Departments", path: "/admin-dashboard/departments", icon: FiGrid },
        { name: "All Tickets", path: "/admin-dashboard/tickets", icon: FiMessageSquare },
        { name: "Categories", path: "/admin-dashboard/categories", icon: FiLayers },
        { name: "SLA Rules", path: "/admin-dashboard/sla", icon: FiClipboard },
        { name: "Reports", path: "/admin-dashboard/reports", icon: FiBarChart2 },
        { name: "Settings", path: "/admin-dashboard/settings", icon: FiSettings },
      ];
    }

    if (role === "Manager") {
      return [
        { name: "Dashboard", path: "/manager-dashboard", icon: FiHome },
        { name: "Team Overview", path: "/manager-dashboard/team", icon: FiUsers },
        { name: "Department Tickets", path: "/manager-dashboard/tickets", icon: FiMessageSquare },
        { name: "Reports", path: "/manager-dashboard/reports", icon: FiBarChart2 },
        { name: "Performance", path: "/manager-dashboard/performance", icon: FiBriefcase },
        { name: "Notifications", path: "/manager-dashboard/notifications", icon: FiBell },
      ];
    }

    if (role === "IT Support") {
      return [
        { name: "Dashboard", path: "/it-dashboard", icon: FiHome },
        { name: "Assigned Tickets", path: "/it-dashboard/tickets", icon: FiMessageSquare },
        { name: "Knowledge Base", path: "/it-dashboard/knowledge", icon: FiLayers },
        { name: "Reports", path: "/it-dashboard/reports", icon: FiBarChart2 },
      ];
    }

    return [
      { name: "Dashboard", path: "/employee-dashboard", icon: FiHome },
      { name: "My Tickets", path: "/employee-dashboard/tickets", icon: FiMessageSquare },
      { name: "Create Ticket", path: "/employee-dashboard/tickets/create", icon: FiClipboard },
      { name: "Notifications", path: "/employee-dashboard/notifications", icon: FiBell },
      { name: "Profile", path: "/employee-dashboard/profile", icon: FiUserCheck },
    ];
  }, [role]);

  const user = JSON.parse(localStorage.getItem("user") || "{}") || {};
  const userName = user.fullName || "Operations User";

  const handleLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    window.location.href = "/";
  };

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <Sidebar role={role} menus={menus} />
      <div className="ml-0 lg:ml-72">
        <div className="p-4 sm:p-6 lg:p-8">
          <Header title={title} subtitle={subtitle} userName={userName} onLogout={handleLogout} />
          <div className="mt-6">{children}</div>
        </div>
      </div>
    </div>
  );
}

export default DashboardLayout;

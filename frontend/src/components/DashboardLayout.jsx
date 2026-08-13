import { useMemo } from "react";
import Sidebar from "./Sidebar";
import Header from "./Header";
import { FiActivity, FiBarChart2, FiBell, FiClipboard, FiHome, FiLayers, FiMessageCircle, FiMessageSquare, FiUserCheck, FiUsers } from "react-icons/fi";

function DashboardLayout({ children, role, title, subtitle }) {
  const menus = useMemo(() => {
    if (role === "Admin") {
      return [
        { name: "Dashboard", path: "/admin-dashboard", icon: FiHome },
        { name: "Users", path: "/admin-dashboard/users", icon: FiUsers },
        { name: "All Tickets", path: "/admin-dashboard/tickets", icon: FiMessageSquare },
        { name: "Activity Logs", path: "/admin-dashboard/activity-logs", icon: FiActivity },
        { name: "Reports", path: "/admin-dashboard/reports", icon: FiBarChart2 },
        { name: "Notifications", path: "/admin-dashboard/notifications", icon: FiBell },
        { name: "AI Chatbot", path: "/ai-chat", icon: FiMessageCircle },
        { name: "Profile", path: "/profile", icon: FiUserCheck },
      ];
    }

    if (role === "Manager") {
      return [
        { name: "Dashboard", path: "/manager-dashboard", icon: FiHome },
        { name: "My Tickets", path: "/manager-dashboard/tickets", icon: FiMessageSquare },
        { name: "Reports", path: "/manager-dashboard/reports", icon: FiBarChart2 },
        { name: "AI Chatbot", path: "/ai-chat", icon: FiMessageCircle },
        { name: "Notifications", path: "/manager-dashboard/notifications", icon: FiBell },
        { name: "Profile", path: "/profile", icon: FiUserCheck },
      ];
    }

    if (role === "IT Support") {
      return [
        { name: "Dashboard", path: "/it-dashboard", icon: FiHome },
        { name: "Assigned Tickets", path: "/it-dashboard/tickets", icon: FiMessageSquare },
        { name: "Activity Logs", path: "/it-dashboard/activity-logs", icon: FiActivity },
        { name: "Knowledge Base", path: "/it-dashboard/knowledge", icon: FiLayers },
        { name: "Reports", path: "/it-dashboard/reports", icon: FiBarChart2 },
        { name: "Notifications", path: "/it-dashboard/notifications", icon: FiBell },
        { name: "AI Chatbot", path: "/ai-chat", icon: FiMessageCircle },
        { name: "Profile", path: "/profile", icon: FiUserCheck },
      ];
    }

    return [
      { name: "Dashboard", path: "/employee-dashboard", icon: FiHome },
      { name: "My Tickets", path: "/employee-dashboard/tickets", icon: FiMessageSquare },
      { name: "Create Ticket", path: "/employee-dashboard/tickets/create", icon: FiClipboard },
      { name: "Reports", path: "/employee-dashboard/reports", icon: FiBarChart2 },
      { name: "AI Chatbot", path: "/ai-chat", icon: FiMessageCircle },
      { name: "Notifications", path: "/employee-dashboard/notifications", icon: FiBell },
      { name: "Profile", path: "/profile", icon: FiUserCheck },
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
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.10),_transparent_28%),linear-gradient(135deg,_#f8fafc_0%,_#f1f5f9_100%)] text-slate-800">
      <Sidebar role={role} menus={menus} />
      <div className="ml-0 lg:ml-72">
        <div className="p-4 sm:p-6 lg:p-8">
          <Header title={title} subtitle={subtitle} userName={userName} onLogout={handleLogout} />
          <div className="mt-6 space-y-6">{children}</div>
        </div>
      </div>
    </div>
  );
}

export default DashboardLayout;

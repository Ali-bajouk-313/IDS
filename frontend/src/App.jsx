import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";

import Login from "./pages/Login";
import Register from "./pages/Register";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import VerifyEmail from "./pages/VerifyEmail";

import AdminDashboard from "./pages/dashboards/AdminDashboard";
import ManagerDashboard from "./pages/dashboards/ManagerDashboard";
import ITSupportDashboard from "./pages/dashboards/ITSupportDashboard";
import EmployeeDashboard from "./pages/dashboards/EmployeeDashboard";
import CreateTicket from "./pages/tickets/CreateTicket";
import EmployeeTickets from "./pages/tickets/EmployeeTickets";
import AdminTickets from "./pages/tickets/AdminTickets";
import ITSupportTickets from "./pages/tickets/ITSupportTickets";
import ManagerTickets from "./pages/tickets/ManagerTickets";
import TicketDetails from "./pages/tickets/TicketDetails";
import ActivityLogs from "./pages/admin/ActivityLogs";
import UsersPage from "./pages/admin/UsersPage";
import NotificationsPage from "./pages/notifications/NotificationsPage";
import ReportsPage from "./pages/reports/ReportsPage";
import KnowledgeBaseAssistant from "./pages/it-support/KnowledgeBaseAssistant";
import ChatbotAssistant from "./pages/ai/ChatbotAssistant";
import ProfilePage from "./pages/profile/ProfilePage";
import ToastContainer from "./components/ToastContainer";

import ProtectedRoute from "./routes/ProtectedRoute";

function App() {
  return (
    <BrowserRouter>
      <ToastContainer />
      <Routes>
        <Route path="/" element={<Login />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/verify-email" element={<VerifyEmail />} />

        <Route
          path="/admin-dashboard"
          element={
            <ProtectedRoute allowedRoles={["Admin"]}>
              <AdminDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin-dashboard/tickets"
          element={
            <ProtectedRoute allowedRoles={["Admin"]}>
              <AdminTickets />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin-dashboard/users"
          element={
            <ProtectedRoute allowedRoles={["Admin"]}>
              <UsersPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin-dashboard/activity-logs"
          element={
            <ProtectedRoute allowedRoles={["Admin"]}>
              <ActivityLogs />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin-dashboard/notifications"
          element={
            <ProtectedRoute allowedRoles={["Admin"]}>
              <NotificationsPage role="Admin" title="Admin Notifications" subtitle="Track system-wide ticket and workflow updates." />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin-dashboard/reports"
          element={
            <ProtectedRoute allowedRoles={["Admin"]}>
              <ReportsPage role="Admin" title="Reports" subtitle="Live system reports and ticket analytics." />
            </ProtectedRoute>
          }
        />

        <Route
          path="/manager-dashboard"
          element={
            <ProtectedRoute allowedRoles={["Manager"]}>
              <ManagerDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/manager-dashboard/tickets"
          element={
            <ProtectedRoute allowedRoles={["Manager"]}>
              <ManagerTickets />
            </ProtectedRoute>
          }
        />
        <Route
          path="/manager-dashboard/notifications"
          element={
            <ProtectedRoute allowedRoles={["Manager"]}>
              <NotificationsPage role="Manager" title="Manager Notifications" subtitle="Review your ticket activity and updates." />
            </ProtectedRoute>
          }
        />
        <Route
          path="/manager-dashboard/reports"
          element={
            <ProtectedRoute allowedRoles={["Manager"]}>
              <ReportsPage role="Manager" title="Reports" subtitle="Your ticket analytics." />
            </ProtectedRoute>
          }
        />

        <Route
          path="/it-dashboard"
          element={
            <ProtectedRoute allowedRoles={["IT Support"]}>
              <ITSupportDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/it-dashboard/tickets"
          element={
            <ProtectedRoute allowedRoles={["IT Support"]}>
              <ITSupportTickets />
            </ProtectedRoute>
          }
        />
        <Route
          path="/it-dashboard/activity-logs"
          element={
            <ProtectedRoute allowedRoles={["IT Support"]}>
              <ActivityLogs role="IT Support" />
            </ProtectedRoute>
          }
        />
        <Route
          path="/it-dashboard/reports"
          element={
            <ProtectedRoute allowedRoles={["IT Support"]}>
              <ReportsPage role="IT Support" title="Reports" subtitle="Track your assigned ticket workload and resolution trends." />
            </ProtectedRoute>
          }
        />
        <Route
          path="/it-dashboard/notifications"
          element={
            <ProtectedRoute allowedRoles={["IT Support"]}>
              <NotificationsPage role="IT Support" title="Support Notifications" subtitle="Stay updated on ticket assignments and status changes." />
            </ProtectedRoute>
          }
        />
        <Route
          path="/it-dashboard/knowledge"
          element={
            <ProtectedRoute allowedRoles={["IT Support"]}>
              <KnowledgeBaseAssistant />
            </ProtectedRoute>
          }
        />

        <Route
          path="/ai-chat"
          element={
            <ProtectedRoute allowedRoles={["Admin", "Manager", "IT Support", "Employee"]}>
              <ChatbotAssistant />
            </ProtectedRoute>
          }
        />
        <Route
          path="/profile"
          element={
            <ProtectedRoute allowedRoles={["Admin", "Manager", "IT Support", "Employee"]}>
              <ProfilePage />
            </ProtectedRoute>
          }
        />

        <Route
          path="/employee-dashboard"
          element={
            <ProtectedRoute allowedRoles={["Employee"]}>
              <EmployeeDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/employee-dashboard/tickets"
          element={
            <ProtectedRoute allowedRoles={["Employee"]}>
              <EmployeeTickets />
            </ProtectedRoute>
          }
        />
        <Route
          path="/employee-dashboard/tickets/create"
          element={
            <ProtectedRoute allowedRoles={["Employee"]}>
              <CreateTicket />
            </ProtectedRoute>
          }
        />
        <Route
          path="/employee-dashboard/notifications"
          element={
            <ProtectedRoute allowedRoles={["Employee"]}>
              <NotificationsPage role="Employee" title="My Notifications" subtitle="Stay on top of your ticket updates." />
            </ProtectedRoute>
          }
        />
        <Route
          path="/employee-dashboard/reports"
          element={
            <ProtectedRoute allowedRoles={["Employee"]}>
              <ReportsPage role="Employee" title="Reports" subtitle="See your own ticket activity and resolution trends." />
            </ProtectedRoute>
          }
        />

        <Route
          path="/tickets/:id"
          element={
            <ProtectedRoute allowedRoles={["Admin", "Manager", "IT Support", "Employee"]}>
              <TicketDetails />
            </ProtectedRoute>
          }
        />

        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
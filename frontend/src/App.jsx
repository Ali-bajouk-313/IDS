import { BrowserRouter, Routes, Route } from "react-router-dom";

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
import ToastContainer from "./components/ToastContainer";

import ProtectedRoute from "./routes/ProtectedRoute";

function App() {
  return (
    <BrowserRouter>
      <ToastContainer />
      <Routes>
        <Route path="/" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
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
          path="/admin-dashboard/activity-logs"
          element={
            <ProtectedRoute allowedRoles={["Admin"]}>
              <ActivityLogs />
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
          path="/tickets/:id"
          element={
            <ProtectedRoute allowedRoles={["Admin", "Manager", "IT Support", "Employee"]}>
              <TicketDetails />
            </ProtectedRoute>
          }
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
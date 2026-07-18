import { BrowserRouter, Routes, Route } from "react-router-dom";

import Login from "./pages/Login";

import AdminDashboard from "./pages/AdminDashboard";
import ManagerDashboard from "./pages/ManagerDashboard";
import SupportDashboard from "./pages/SupportDashboard";
import EmployeeDashboard from "./pages/EmployeeDashboard";

import ProtectedRoute from "./routes/ProtectedRoute";


function App() {


return (

<BrowserRouter>

<Routes>


<Route path="/" element={<Login />} />


<Route 
path="/admin"
element={
<ProtectedRoute allowedRoles={["Admin"]}>
<AdminDashboard />
</ProtectedRoute>
}
/>


<Route 
path="/manager"
element={
<ProtectedRoute allowedRoles={["Manager"]}>
<ManagerDashboard />
</ProtectedRoute>
}
/>


<Route 
path="/support"
element={
<ProtectedRoute allowedRoles={["IT Support"]}>
<SupportDashboard />
</ProtectedRoute>
}
/>


<Route 
path="/employee"
element={
<ProtectedRoute allowedRoles={["Employee"]}>
<EmployeeDashboard />
</ProtectedRoute>
}
/>


</Routes>

</BrowserRouter>

);

}


export default App;
import { FiAlertTriangle, FiDatabase, FiServer, FiUsers, FiMessageSquare, FiShield, FiActivity } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import ActivityFeed from "../../components/ActivityFeed";

function AdminDashboard() {
  return (
    <DashboardLayout role="Admin" title="System Overview" subtitle="Monitor platform health, security, and service delivery.">
      <div className="grid gap-4 lg:grid-cols-4">
        <StatCard title="Total Users" value="1,284" detail="+12% this month" icon={FiUsers} accent="bg-blue-600" />
        <StatCard title="Total Tickets" value="348" detail="24 unresolved" icon={FiMessageSquare} accent="bg-emerald-600" />
        <StatCard title="System Status" value="Healthy" detail="All services online" icon={FiServer} accent="bg-sky-600" />
        <StatCard title="Security Alerts" value="7" detail="2 require action" icon={FiAlertTriangle} accent="bg-rose-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-3">
        <ChartCard title="Platform Activity">
          <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
            {[40, 60, 55, 80, 70, 95].map((height, index) => (
              <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-blue-700 to-blue-400" style={{ height: `${height}%` }} />
            ))}
          </div>
        </ChartCard>

        <ChartCard title="Tickets by Department">
          <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
            {[70, 55, 90, 45].map((height, index) => (
              <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-emerald-600 to-emerald-400" style={{ height: `${height}%` }} />
            ))}
          </div>
        </ChartCard>

        <ChartCard title="User Distribution">
          <div className="flex h-48 flex-col justify-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
            <div className="flex items-center justify-between"><span>Admins</span><span className="font-semibold">12</span></div>
            <div className="flex items-center justify-between"><span>Managers</span><span className="font-semibold">18</span></div>
            <div className="flex items-center justify-between"><span>Support Staff</span><span className="font-semibold">72</span></div>
            <div className="flex items-center justify-between"><span>Employees</span><span className="font-semibold">1182</span></div>
          </div>
        </ChartCard>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-lg font-semibold text-slate-900">System Health</h3>
          <div className="mt-4 space-y-3">
            <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
              <div className="flex items-center gap-3"><FiDatabase className="text-blue-600" /><span>Database</span></div>
              <span className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">Operational</span>
            </div>
            <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
              <div className="flex items-center gap-3"><FiServer className="text-blue-600" /><span>API Services</span></div>
              <span className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">Operational</span>
            </div>
            <div className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
              <div className="flex items-center gap-3"><FiShield className="text-blue-600" /><span>Email Service</span></div>
              <span className="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">Monitoring</span>
            </div>
          </div>
        </div>

        <ActivityFeed items={[
          { title: "Security policy updated", time: "10 mins ago" },
          { title: "New department created", time: "32 mins ago" },
          { title: "SLA rules adjusted", time: "1 hr ago" },
        ]} />
      </div>
    </DashboardLayout>
  );
}

export default AdminDashboard;

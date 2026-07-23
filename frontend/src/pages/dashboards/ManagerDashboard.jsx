import { FiBarChart2, FiClock, FiUsers, FiMessageSquare, FiTrendingUp } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import DataTable from "../../components/DataTable";
import ActivityFeed from "../../components/ActivityFeed";

function ManagerDashboard() {
  const tickets = [
    { id: "#1034", title: "VPN access issue", createdBy: "A. Hassan", assignedTo: "R. Saleh", priority: "High", status: "Pending" },
    { id: "#1037", title: "Printer driver failure", createdBy: "K. Nabil", assignedTo: "M. Samir", priority: "Medium", status: "In Progress" },
    { id: "#1040", title: "Account lockout", createdBy: "D. Rami", assignedTo: "L. Omar", priority: "High", status: "Resolved" },
  ];

  return (
    <DashboardLayout role="Manager" title="Department Performance" subtitle="Monitor your team, service health, and operational load.">
      <div className="grid gap-4 lg:grid-cols-4">
        <StatCard title="Department Tickets" value="86" detail="+8 this week" icon={FiMessageSquare} accent="bg-blue-600" />
        <StatCard title="Pending Tickets" value="21" detail="3 urgent" icon={FiClock} accent="bg-amber-600" />
        <StatCard title="Resolution Rate" value="92%" detail="Above target" icon={FiTrendingUp} accent="bg-emerald-600" />
        <StatCard title="Active Team" value="14" detail="2 on leave" icon={FiUsers} accent="bg-sky-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <ChartCard title="Ticket Status Distribution">
          <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
            {[65, 45, 80, 55].map((height, index) => (
              <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-amber-600 to-orange-400" style={{ height: `${height}%` }} />
            ))}
          </div>
        </ChartCard>

        <ChartCard title="Team Performance">
          <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
            {[85, 78, 90, 74].map((height, index) => (
              <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-emerald-600 to-lime-400" style={{ height: `${height}%` }} />
            ))}
          </div>
        </ChartCard>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
        <div>
          <h3 className="mb-3 text-lg font-semibold text-slate-900">Recent Tickets</h3>
          <DataTable columns={["ID", "Title", "Created By", "Assigned To", "Priority", "Status"]} rows={tickets} />
        </div>
        <ActivityFeed items={[
          { title: "Team review scheduled", time: "15 mins ago" },
          { title: "Escalation handled", time: "1 hr ago" },
          { title: "Weekly SLA update shared", time: "2 hrs ago" },
        ]} />
      </div>
    </DashboardLayout>
  );
}

export default ManagerDashboard;

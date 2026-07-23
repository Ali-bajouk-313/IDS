import { FiAlertTriangle, FiCheckCircle, FiClock, FiMessageSquare, FiTrendingUp, FiUserCheck } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import DataTable from "../../components/DataTable";
import ActivityFeed from "../../components/ActivityFeed";

function ITSupportDashboard() {
  const assignedTickets = [
    { id: "#2011", title: "Email sync issue", user: "N. Khaled", priority: "High", status: "In Progress", action: "Open" },
    { id: "#2014", title: "Laptop setup", user: "S. Lina", priority: "Medium", status: "Pending", action: "Review" },
    { id: "#2020", title: "Access request", user: "A. Omar", priority: "Low", status: "Resolved", action: "View" },
  ];

  return (
    <DashboardLayout role="IT Support" title="Support Operations" subtitle="Address incoming issues and keep service levels high.">
      <div className="grid gap-4 lg:grid-cols-5">
        <StatCard title="Open Tickets" value="34" detail="Needs attention" icon={FiMessageSquare} accent="bg-blue-600" />
        <StatCard title="Assigned Tickets" value="12" detail="3 high priority" icon={FiUserCheck} accent="bg-sky-600" />
        <StatCard title="High Priority" value="5" detail="Escalate quickly" icon={FiAlertTriangle} accent="bg-rose-600" />
        <StatCard title="Resolved Today" value="18" detail="Good pace" icon={FiCheckCircle} accent="bg-emerald-600" />
        <StatCard title="Avg. Response" value="14m" detail="Within SLA" icon={FiClock} accent="bg-amber-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <ChartCard title="Ticket Volume Trend">
          <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
            {[50, 65, 48, 72, 84].map((height, index) => (
              <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-sky-700 to-cyan-400" style={{ height: `${height}%` }} />
            ))}
          </div>
        </ChartCard>

        <ChartCard title="Priority Distribution">
          <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
            {[80, 55, 40].map((height, index) => (
              <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-rose-600 to-orange-400" style={{ height: `${height}%` }} />
            ))}
          </div>
        </ChartCard>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
        <div>
          <h3 className="mb-3 text-lg font-semibold text-slate-900">Assigned Tickets</h3>
          <DataTable columns={["Ticket ID", "Title", "User", "Priority", "Status", "Action"]} rows={assignedTickets} />
        </div>
        <ActivityFeed items={[
          { title: "New escalation received", time: "12 mins ago" },
          { title: "Resolved printer request", time: "48 mins ago" },
          { title: "Knowledge base updated", time: "1 hr ago" },
        ]} />
      </div>
    </DashboardLayout>
  );
}

export default ITSupportDashboard;

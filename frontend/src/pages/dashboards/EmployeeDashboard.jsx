import { FiClipboard, FiClock, FiFileText, FiMessageSquare, FiPackage, FiPlusCircle, FiServer, FiTrendingUp } from "react-icons/fi";
import DashboardLayout from "../../components/DashboardLayout";
import StatCard from "../../components/StatCard";
import ChartCard from "../../components/ChartCard";
import DataTable from "../../components/DataTable";
import ActivityFeed from "../../components/ActivityFeed";

function EmployeeDashboard() {
  const tickets = [
    { id: "#5001", title: "Laptop not charging", category: "Hardware", priority: "High", status: "Open", created: "Today" },
    { id: "#5003", title: "Email access issue", category: "Software", priority: "Medium", status: "In Progress", created: "Yesterday" },
    { id: "#5004", title: "VPN access request", category: "Network", priority: "Low", status: "Resolved", created: "2 days ago" },
  ];

  return (
    <DashboardLayout role="Employee" title="My Requests" subtitle="Track your tickets and request support quickly.">
      <div className="grid gap-4 lg:grid-cols-4">
        <StatCard title="Open Tickets" value="4" detail="Needs follow-up" icon={FiMessageSquare} accent="bg-blue-600" />
        <StatCard title="In Progress" value="2" detail="Being reviewed" icon={FiClock} accent="bg-amber-600" />
        <StatCard title="Resolved" value="8" detail="This month" icon={FiFileText} accent="bg-emerald-600" />
        <StatCard title="Pending" value="1" detail="Awaiting reply" icon={FiPackage} accent="bg-sky-600" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <ChartCard title="Ticket Status">
          <div className="flex h-48 items-end gap-3 rounded-2xl bg-slate-50 p-4">
            {[70, 40, 90].map((height, index) => (
              <div key={index} className="flex-1 rounded-t-2xl bg-gradient-to-t from-blue-700 to-cyan-400" style={{ height: `${height}%` }} />
            ))}
          </div>
        </ChartCard>

        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-lg font-semibold text-slate-900">Quick Actions</h3>
          <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
            <button className="flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
              <FiPlusCircle className="h-4 w-4" /> Create Ticket
            </button>
            <button className="flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
              <FiServer className="h-4 w-4" /> View Knowledge Base
            </button>
          </div>
        </div>
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
        <div>
          <h3 className="mb-3 text-lg font-semibold text-slate-900">My Tickets</h3>
          <DataTable columns={["ID", "Title", "Category", "Priority", "Status", "Created Date"]} rows={tickets} />
        </div>
        <ActivityFeed items={[
          { title: "Ticket update received", time: "25 mins ago" },
          { title: "Support response added", time: "1 hr ago" },
          { title: "Request marked resolved", time: "2 hrs ago" },
        ]} />
      </div>
    </DashboardLayout>
  );
}

export default EmployeeDashboard;

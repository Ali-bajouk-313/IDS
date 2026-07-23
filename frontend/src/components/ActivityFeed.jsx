function ActivityFeed({ items }) {
  return (
    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <h3 className="text-lg font-semibold text-slate-900">Recent Activity</h3>
      <div className="mt-4 space-y-4">
        {items.map((item, index) => (
          <div key={index} className="flex gap-3">
            <div className="mt-1 h-2.5 w-2.5 rounded-full bg-blue-600" />
            <div>
              <p className="text-sm font-medium text-slate-700">{item.title}</p>
              <p className="text-sm text-slate-500">{item.time}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default ActivityFeed;

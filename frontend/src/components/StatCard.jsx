function StatCard({ title, value, detail, icon: Icon, accent }) {
  return (
    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-slate-500">{title}</p>
          <p className="mt-2 text-3xl font-semibold text-slate-900">{value}</p>
          <p className="mt-2 text-sm text-slate-500">{detail}</p>
        </div>
        <div className={`rounded-2xl p-3 ${accent}`}>
          {Icon ? <Icon className="h-5 w-5 text-white" /> : null}
        </div>
      </div>
    </div>
  );
}

export default StatCard;

function StatusBadge({ status }) {
  const styles = {
    Active: "bg-emerald-100 text-emerald-700",
    Pending: "bg-amber-100 text-amber-700",
    High: "bg-rose-100 text-rose-700",
    Medium: "bg-sky-100 text-sky-700",
    Low: "bg-slate-100 text-slate-700",
  };

  return <span className={`rounded-full px-3 py-1 text-xs font-semibold ${styles[status] || styles.Low}`}>{status}</span>;
}

export default StatusBadge;

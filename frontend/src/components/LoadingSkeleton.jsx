function LoadingSkeleton({ rows = 3, className = "", variant = "card" }) {
  if (variant === "table") {
    return (
      <div className={`animate-pulse rounded-3xl border border-slate-200 bg-white p-4 shadow-sm ${className}`}>
        <div className="mb-4 flex gap-3">
          <div className="h-3 w-24 rounded-full bg-slate-200" />
          <div className="h-3 w-28 rounded-full bg-slate-200" />
          <div className="h-3 w-20 rounded-full bg-slate-200" />
        </div>
        <div className="space-y-3">
          {Array.from({ length: rows }).map((_, index) => (
            <div key={index} className="grid grid-cols-5 gap-3">
              <div className="h-3 rounded-full bg-slate-200" />
              <div className="h-3 rounded-full bg-slate-200" />
              <div className="h-3 rounded-full bg-slate-200" />
              <div className="h-3 rounded-full bg-slate-200" />
              <div className="h-3 rounded-full bg-slate-200" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className={`animate-pulse rounded-3xl border border-slate-200 bg-white p-5 shadow-sm ${className}`}>
      <div className="h-4 w-28 rounded-full bg-slate-200" />
      <div className="mt-4 h-8 w-3/4 rounded-2xl bg-slate-200" />
      <div className="mt-3 space-y-2">
        {Array.from({ length: rows }).map((_, index) => (
          <div key={index} className="h-3 rounded-full bg-slate-100" />
        ))}
      </div>
    </div>
  );
}

export default LoadingSkeleton;

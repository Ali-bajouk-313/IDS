import { FiArrowDown, FiArrowUp } from "react-icons/fi";

function DataTable({ columns, rows, emptyState, sortConfig, onSort }) {
  const getLabel = (column) => (typeof column === "string" ? column : column.label);
  const getKey = (column) => (typeof column === "string" ? column : column.key);
  const isSortable = (column) => typeof column !== "string" && Boolean(column.sortable);

  return (
    <div className="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full text-left text-sm text-slate-600">
          <thead className="bg-slate-50/90 text-[11px] uppercase tracking-[0.22em] text-slate-500">
            <tr>
              {columns.map((column, index) => {
                const label = getLabel(column);
                const key = getKey(column);
                const sortable = isSortable(column);

                return (
                  <th key={key || label || index} className="px-4 py-3 font-semibold">
                    {sortable ? (
                      <button
                        type="button"
                        onClick={() => onSort?.(key)}
                        className="flex items-center gap-2 text-left transition hover:text-slate-900"
                      >
                        <span>{label}</span>
                        {sortConfig?.key === key ? (
                          sortConfig.direction === "asc" ? <FiArrowUp className="h-3.5 w-3.5" /> : <FiArrowDown className="h-3.5 w-3.5" />
                        ) : null}
                      </button>
                    ) : (
                      <span>{label}</span>
                    )}
                  </th>
                );
              })}
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 ? (
              <tr>
                <td colSpan={columns.length} className="px-4 py-10 text-center">
                  <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-600">
                    {emptyState || "No records found."}
                  </div>
                </td>
              </tr>
            ) : (
              rows.map((row, index) => (
                <tr key={row.id || index} className="border-t border-slate-200 bg-white transition hover:bg-slate-50/80">
                  {columns.map((column, columnIndex) => {
                    const key = getKey(column);
                    const value = key ? row[key] : Object.values(row)[columnIndex];

                    return (
                      <td key={`${key || "cell"}-${index}`} className="px-4 py-3 align-top">
                        {value}
                      </td>
                    );
                  })}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default DataTable;

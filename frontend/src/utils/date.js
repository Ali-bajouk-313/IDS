export function formatDateTime(value) {
  if (!value) {
    return "-";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    const fallback = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(fallback.getTime())) {
      return String(value);
    }

    return fallback.toLocaleString();
  }

  return date.toLocaleString();
}

const listeners = new Set();

export function showToast(payload) {
  listeners.forEach((listener) => listener(payload));
}

export function subscribeToToasts(listener) {
  listeners.add(listener);

  return () => {
    listeners.delete(listener);
  };
}

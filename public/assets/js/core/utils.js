export const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
export const uid = (prefix = 'mentoris') => `${prefix}-${crypto.randomUUID?.() ?? Math.random().toString(36).slice(2)}`;
export const debounce = (callback, wait = 160) => {
  let timeout;
  return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => callback(...args), wait); };
};
export const focusable = (root) => [...root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];

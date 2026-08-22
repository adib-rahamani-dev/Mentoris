import { $$ } from '../core/dom.js?v=2.0.0';

export const initReveal = () => {
  const items = $$('[data-reveal]');
  if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) {
    items.forEach((item) => item.classList.add('is-visible')); return null;
  }
  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
    if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
  }), { threshold: 0.12, rootMargin: '0px 0px -40px' });
  items.forEach((item) => observer.observe(item));
  return observer;
};

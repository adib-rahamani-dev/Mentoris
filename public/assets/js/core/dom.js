export const $ = (selector, context = document) => context.querySelector(selector);
export const $$ = (selector, context = document) => [...context.querySelectorAll(selector)];
export const on = (target, event, handler, options) => target.addEventListener(event, handler, options);
export const delegate = (root, event, selector, handler) => {
  root.addEventListener(event, (e) => {
    const match = e.target.closest(selector);
    if (match && root.contains(match)) handler(e, match);
  });
};
export const ready = (callback) => document.readyState === 'loading'
  ? document.addEventListener('DOMContentLoaded', callback, { once: true })
  : callback();

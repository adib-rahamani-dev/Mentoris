import { $ } from '../core/dom.js?v=2.0.0';

export class Toast {
  static show(message, { title = 'Mentoris', type = 'success', duration = 4000 } = {}) {
    let region = $('.toast-region');
    if (!region) {
      region = document.createElement('div'); region.className = 'toast-region'; region.setAttribute('aria-live', 'polite'); document.body.append(region);
    }
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `<div class="toast__body"><strong class="toast__title"></strong><div class="toast__message"></div></div><button class="toast__close" aria-label="بستن">×</button>`;
    toast.querySelector('.toast__title').textContent = title;
    toast.querySelector('.toast__message').textContent = message;
    const close = () => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 180); };
    toast.querySelector('button').addEventListener('click', close);
    region.append(toast); setTimeout(close, duration);
    return toast;
  }
}

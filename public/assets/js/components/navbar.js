import { $ } from '../core/dom.js?v=2.0.0';

export class Navbar {
  constructor(element) {
    this.element = element;
    this.toggle = $('[data-navbar-toggle]');
    this.toggle?.addEventListener('click', () => this.setOpen(!this.element.classList.contains('is-open')));
    this.element.addEventListener('click', (event) => { if (event.target.closest('a') && !event.target.closest('.dropdown')) this.setOpen(false); });
    window.addEventListener('keydown', (event) => { if (event.key === 'Escape') this.setOpen(false); });
  }
  setOpen(open) {
    this.element.classList.toggle('is-open', open);
    this.toggle?.setAttribute('aria-expanded', String(open));
  }
  static init() { const element = $('[data-navbar]'); return element ? new Navbar(element) : null; }
}

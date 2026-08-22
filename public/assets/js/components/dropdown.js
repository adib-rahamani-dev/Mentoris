import { $$ } from '../core/dom.js?v=2.0.0';

export class Dropdown {
  constructor(element) {
    this.element = element;
    this.trigger = element.querySelector('.dropdown__trigger');
    this.trigger?.addEventListener('click', (event) => { event.stopPropagation(); this.toggle(); });
  }
  open() { Dropdown.closeAll(this); this.element.classList.add('is-open'); this.trigger?.setAttribute('aria-expanded', 'true'); }
  close() { this.element.classList.remove('is-open'); this.trigger?.setAttribute('aria-expanded', 'false'); }
  toggle() { this.element.classList.contains('is-open') ? this.close() : this.open(); }
  static closeAll(except = null) { this.instances?.forEach((item) => { if (item !== except) item.close(); }); }
  static init() {
    this.instances = $$('.dropdown').map((el) => new Dropdown(el));
    document.addEventListener('click', () => this.closeAll());
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') this.closeAll(); });
    return this.instances;
  }
}

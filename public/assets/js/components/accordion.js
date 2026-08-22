import { $$ } from '../core/dom.js?v=2.0.0';

export class Accordion {
  constructor(element) {
    this.element = element;
    this.single = element.dataset.accordion !== 'multiple';
    this.triggers = $$('.accordion__trigger', element);
    this.triggers.forEach((trigger) => trigger.addEventListener('click', () => this.toggle(trigger)));
  }
  toggle(trigger) {
    const panel = document.getElementById(trigger.getAttribute('aria-controls'));
    const opening = trigger.getAttribute('aria-expanded') !== 'true';
    if (this.single) this.triggers.forEach((item) => item !== trigger && this.set(item, false));
    this.set(trigger, opening, panel);
  }
  set(trigger, open, panel = document.getElementById(trigger.getAttribute('aria-controls'))) {
    trigger.setAttribute('aria-expanded', String(open));
    panel?.classList.toggle('is-open', open);
    panel?.setAttribute('aria-hidden', String(!open));
  }
  static init() { return $$('.accordion').map((el) => new Accordion(el)); }
}

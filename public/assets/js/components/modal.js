import { $$, delegate } from '../core/dom.js?v=2.0.0';
import { focusable } from '../core/utils.js?v=2.0.0';

export class Modal {
  constructor(element) {
    this.element = element;
    this.lastFocus = null;
    this.onKeydown = this.onKeydown.bind(this);
  }
  open(trigger = document.activeElement) {
    this.lastFocus = trigger;
    this.element.classList.add('is-open');
    this.element.setAttribute('aria-hidden', 'false');
    document.body.classList.add('has-modal');
    document.addEventListener('keydown', this.onKeydown);
    requestAnimationFrame(() => focusable(this.element)[0]?.focus());
    this.element.dispatchEvent(new CustomEvent('modal:open'));
  }
  close() {
    this.element.classList.remove('is-open');
    this.element.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('has-modal');
    document.removeEventListener('keydown', this.onKeydown);
    this.lastFocus?.focus?.();
    this.element.dispatchEvent(new CustomEvent('modal:close'));
  }
  onKeydown(event) {
    if (event.key === 'Escape') return this.close();
    if (event.key !== 'Tab') return;
    const items = focusable(this.element);
    if (!items.length) return;
    const first = items[0], last = items.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  }
  static init() {
    const instances = new Map($$('.modal').map((el) => [el.id, new Modal(el)]));
    delegate(document, 'click', '[data-modal-open]', (event, trigger) => {
      event.preventDefault(); instances.get(trigger.dataset.modalOpen)?.open(trigger);
    });
    delegate(document, 'click', '[data-modal-close]', (event, trigger) => {
      event.preventDefault(); instances.get(trigger.closest('.modal')?.id)?.close();
    });
    return instances;
  }
}

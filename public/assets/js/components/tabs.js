import { $$ } from '../core/dom.js?v=2.0.0';

export class Tabs {
  constructor(element) {
    this.element = element;
    this.tabs = $$('[role="tab"]', element);
    this.panels = $$('[role="tabpanel"]', element);
    this.tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => this.select(index));
      tab.addEventListener('keydown', (event) => this.onKeydown(event, index));
    });
  }
  select(index, focus = false) {
    this.tabs.forEach((tab, i) => {
      const selected = i === index;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      if (focus && selected) tab.focus();
    });
    this.panels.forEach((panel, i) => { panel.hidden = i !== index; });
  }
  onKeydown(event, index) {
    let next = index;
    if (['ArrowLeft', 'ArrowDown'].includes(event.key)) next = (index + 1) % this.tabs.length;
    else if (['ArrowRight', 'ArrowUp'].includes(event.key)) next = (index - 1 + this.tabs.length) % this.tabs.length;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = this.tabs.length - 1;
    else return;
    event.preventDefault(); this.select(next, true);
  }
  static init() { return $$('.tabs').map((el) => new Tabs(el)); }
}

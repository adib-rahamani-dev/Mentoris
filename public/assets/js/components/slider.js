import { $$ } from '../core/dom.js?v=2.0.0';
import { clamp, debounce } from '../core/utils.js?v=2.0.0';

export class Slider {
  constructor(element) {
    this.element = element;
    this.track = element.querySelector('.slider__track');
    this.slides = $$('.slider__slide', element);
    this.index = 0;
    element.querySelector('[data-slider-next]')?.addEventListener('click', () => this.go(this.index + 1));
    element.querySelector('[data-slider-prev]')?.addEventListener('click', () => this.go(this.index - 1));
    window.addEventListener('resize', debounce(() => this.go(this.index), 120));
  }
  visibleCount() { return innerWidth <= 600 ? 1 : innerWidth <= 900 ? 2 : 3; }
  go(index) {
    this.index = clamp(index, 0, Math.max(0, this.slides.length - this.visibleCount()));
    const width = this.slides[0]?.getBoundingClientRect().width ?? 0;
    const gap = parseFloat(getComputedStyle(this.track).gap) || 0;
    this.track.style.transform = `translateX(-${this.index * (width + gap)}px)`;
  }
  static init() { return $$('.slider').map((el) => new Slider(el)); }
}

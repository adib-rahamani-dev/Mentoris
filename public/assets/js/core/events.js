class EventBus {
  #target = new EventTarget();
  on(name, handler) { this.#target.addEventListener(name, handler); return () => this.off(name, handler); }
  off(name, handler) { this.#target.removeEventListener(name, handler); }
  emit(name, detail = {}) { this.#target.dispatchEvent(new CustomEvent(name, { detail })); }
}
export const events = new EventBus();

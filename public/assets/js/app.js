import { ready, delegate } from './core/dom.js?v=2.0.0';
import { Modal } from './components/modal.js?v=2.0.0';
import { Dropdown } from './components/dropdown.js?v=2.0.0';
import { Tabs } from './components/tabs.js?v=2.0.0';
import { Accordion } from './components/accordion.js?v=2.0.0';
import { Slider } from './components/slider.js?v=2.0.0';
import { Toast } from './components/toast.js?v=2.0.0';
import { Navbar } from './components/navbar.js?v=2.0.0';
import { initReveal } from './components/reveal.js?v=2.0.0';

ready(() => {
  Modal.init(); Dropdown.init(); Tabs.init(); Accordion.init(); Slider.init(); Navbar.init(); initReveal();
  delegate(document, 'click', '[data-toast]', (_, trigger) => Toast.show(trigger.dataset.toast, { title: trigger.dataset.toastTitle ?? 'Mentoris', type: trigger.dataset.toastType ?? 'success' }));
  window.Mentoris = { Modal, Toast };
});

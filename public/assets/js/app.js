import { ready, delegate } from './core/dom.js?v=2.0.0';
import { Modal } from './components/modal.js?v=2.0.0';
import { Dropdown } from './components/dropdown.js?v=2.0.0';
import { Tabs } from './components/tabs.js?v=2.0.0';
import { Accordion } from './components/accordion.js?v=2.0.0';
import { Slider } from './components/slider.js?v=2.0.0';
import { Toast } from './components/toast.js?v=2.0.0';
import { Navbar } from './components/navbar.js?v=2.0.0';
import { initReveal } from './components/reveal.js?v=2.0.0';
import { initProgramFilters, initCourseFilters } from './pages/courses.js?v=6.0.0';
import { initEventFilters } from './pages/events.js?v=5.0.0';
import { Theme } from './components/theme.js?v=10.0.0';

ready(() => {
  Theme.init(); Modal.init(); Dropdown.init(); Tabs.init(); Accordion.init(); Slider.init(); Navbar.init(); initReveal(); initProgramFilters(); initCourseFilters(); initEventFilters();
  delegate(document, 'click', '[data-toast]', (_, trigger) => Toast.show(trigger.dataset.toast, { title: trigger.dataset.toastTitle ?? 'Mentoris', type: trigger.dataset.toastType ?? 'success' }));
  window.Mentoris = { Modal, Toast, Theme };
});

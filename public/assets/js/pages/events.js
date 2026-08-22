export function initEventFilters() {
  const grid = document.querySelector('[data-event-grid]');
  if (!grid) return;

  const items = [...grid.querySelectorAll('[data-event-item]')];
  const search = document.querySelector('[data-event-search]');
  const mode = document.querySelector('[data-event-mode]');
  const statusButtons = [...document.querySelectorAll('[data-event-status]')];
  const count = document.querySelector('[data-event-count]');
  const empty = document.querySelector('[data-event-empty]');
  let activeStatus = 'all';

  const apply = () => {
    const query = (search?.value ?? '').trim().toLocaleLowerCase('fa');
    const activeMode = mode?.value ?? 'all';
    let visible = 0;
    items.forEach((item) => {
      const matchesSearch = !query || (item.dataset.eventText ?? '').toLocaleLowerCase('fa').includes(query);
      const matchesStatus = activeStatus === 'all' || item.dataset.eventStatusValue === activeStatus;
      const matchesMode = activeMode === 'all' || item.dataset.eventModeValue === activeMode;
      item.hidden = !(matchesSearch && matchesStatus && matchesMode);
      if (!item.hidden) visible += 1;
    });
    if (count) count.textContent = String(visible);
    if (empty) empty.hidden = visible !== 0;
  };

  search?.addEventListener('input', apply);
  mode?.addEventListener('change', apply);
  statusButtons.forEach((button) => button.addEventListener('click', () => {
    activeStatus = button.dataset.eventStatus ?? 'all';
    statusButtons.forEach((item) => item.classList.toggle('is-active', item === button));
    apply();
  }));
}

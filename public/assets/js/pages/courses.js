import { $, $$ } from '../core/dom.js?v=2.0.0';

export const initProgramFilters = () => {
  const grid = $('[data-program-grid]');
  if (!grid) return null;

  const search = $('[data-program-search]');
  const items = $$('[data-program-item]', grid);
  const chips = $$('[data-program-filter]');
  const count = $('[data-program-count]');
  const empty = $('[data-program-empty]');
  let activeCategory = 'all';

  const apply = () => {
    const term = search.value.trim().toLocaleLowerCase('fa');
    let visible = 0;
    items.forEach((item) => {
      const matchesText = item.dataset.programText.toLocaleLowerCase('fa').includes(term);
      const matchesCategory = activeCategory === 'all' || item.dataset.programCategory === activeCategory;
      item.hidden = !(matchesText && matchesCategory);
      if (!item.hidden) visible++;
    });
    count.textContent = String(visible);
    empty.hidden = visible !== 0;
  };

  search.addEventListener('input', apply);
  chips.forEach((chip) => chip.addEventListener('click', () => {
    activeCategory = chip.dataset.programFilter;
    chips.forEach((item) => item.classList.toggle('is-active', item === chip));
    apply();
  }));

  return { apply };
};

export const initCourseFilters = () => {
  const grid = $('[data-course-grid]');
  if (!grid) return null;

  const search = $('[data-course-search]');
  const status = $('[data-course-status]');
  const type = $('[data-course-type]');
  const reset = $('[data-course-reset]');
  const items = $$('[data-course-item]', grid);
  const chips = $$('[data-course-category]');
  const count = $('[data-course-count]');
  const empty = $('[data-course-empty]');
  let activeCategory = 'all';

  const apply = () => {
    const term = search.value.trim().toLocaleLowerCase('fa');
    let visible = 0;
    items.forEach((item) => {
      const matches = (!term || item.dataset.courseText.toLocaleLowerCase('fa').includes(term))
        && (activeCategory === 'all' || item.dataset.courseCategoryValue === activeCategory)
        && (status.value === 'all' || item.dataset.courseStatusValue === status.value)
        && (type.value === 'all' || item.dataset.courseTypeValue === type.value);
      item.hidden = !matches;
      if (matches) visible++;
    });
    count.textContent = String(visible);
    empty.hidden = visible !== 0;
  };

  search.addEventListener('input', apply);
  status.addEventListener('change', apply);
  type.addEventListener('change', apply);
  chips.forEach((chip) => chip.addEventListener('click', () => {
    activeCategory = chip.dataset.courseCategory;
    chips.forEach((item) => item.classList.toggle('is-active', item === chip));
    apply();
  }));
  reset.addEventListener('click', () => {
    search.value = ''; status.value = 'all'; type.value = 'all'; activeCategory = 'all';
    chips.forEach((item) => item.classList.toggle('is-active', item.dataset.courseCategory === 'all'));
    apply();
  });
  return { apply };
};

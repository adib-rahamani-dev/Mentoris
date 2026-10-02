export function initMemberProfile() {
  document.querySelectorAll('[data-member-form]').forEach(form => {
    const value = name => form.elements.namedItem(name)?.value || '';
    const sync = () => {
      form.querySelectorAll('[data-member-condition]').forEach(section => {
        const condition = section.dataset.memberCondition;
        const shown = condition === 'therapist' ? value('member_type') === 'therapist' : condition === 'graduate' ? value('education_status') === 'graduate' : value('member_type') === 'therapist' && value('practice_status') === 'active';
        section.hidden = !shown;
        section.querySelectorAll('input,select,textarea').forEach(input => { input.disabled = !shown || !!form.querySelector('fieldset.member-fieldset[disabled]'); });
      });
    };
    form.addEventListener('change', sync); sync();
    form.addEventListener('invalid', event => {
      let section = event.target.closest('details');
      while (section) { section.open = true; section = section.parentElement.closest('details'); }
    }, true);
    const list = form.querySelector('[data-course-list]');
    let nextIndex = list.children.length;
    const update = () => {
      const count = list.children.length;
      form.querySelector('[data-course-status]').textContent = `${count} دوره ثبت شده؛ حداکثر ۳۰ دوره.`;
      const add = form.querySelector('[data-add-course]');
      if (add) add.disabled = count >= 30;
    };
    form.querySelector('[data-add-course]')?.addEventListener('click', () => {
      if (list.children.length >= 30) return;
      const fragment = form.querySelector('[data-course-template]').content.cloneNode(true);
      fragment.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace('__INDEX__', String(nextIndex)); });
      nextIndex++; list.append(fragment); update(); list.lastElementChild.querySelector('input').focus();
    });
    list.addEventListener('click', event => {
      const button = event.target.closest('[data-remove-course]');
      if (button) { button.closest('[data-course-row]').remove(); update(); }
    });
    form.addEventListener('submit', event => {
      form.querySelectorAll('[name="field_of_study"],[name="university"]').forEach(input => { input.required = event.submitter?.value === 'complete' && ['student','therapist'].includes(value('member_type')); });
      if (!form.reportValidity()) event.preventDefault();
    });
    update();
  });
}

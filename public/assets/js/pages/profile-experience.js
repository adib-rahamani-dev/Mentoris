const storage = {
  read(key) { try { return JSON.parse(sessionStorage.getItem(key) || 'null'); } catch { return null; } },
  write(key, value) { try { sessionStorage.setItem(key, JSON.stringify(value)); } catch {} }
};
async function send(form, data) {
  const response = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
  if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('نشست شما منقضی شده یا سرور پاسخ نداد؛ اطلاعات فرم حفظ شده است. در یک زبانهٔ دیگر وارد حساب شوید و دوباره ذخیره کنید.');
  const result = await response.json().catch(() => ({ message: 'ذخیره انجام نشد؛ اطلاعات فرم حفظ شده است. دوباره تلاش کنید.' }));
  if (!response.ok) throw Object.assign(new Error(result.message || 'عملیات انجام نشد.'), { errors: result.errors });
  return result;
}
export function initProfileExperience() {
  document.querySelector('[data-print-resource]')?.addEventListener('click', () => {
    document.querySelectorAll('[data-resource-answer]').forEach(input => {
      let printed = input.nextElementSibling;
      if (!printed?.matches('.resource-print-answer')) { printed = document.createElement('div'); printed.className = 'resource-print-answer'; input.after(printed); }
      printed.textContent = input.value || '................................................................';
    });
    window.print();
  });
  document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const input = button.parentElement.querySelector('input'); const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password'; button.setAttribute('aria-pressed', String(visible)); button.setAttribute('aria-label', visible ? 'پنهان‌کردن رمز' : 'نمایش رمز');
  }));
  document.querySelector('[data-quick-register]')?.addEventListener('submit', event => {
    if (event.target.reportValidity()) { const button = event.target.querySelector('[type="submit"]'); button.disabled = true; button.textContent = 'در حال ساخت حساب…'; }
  });
  document.querySelectorAll('[data-member-form],[data-async-save],[data-survey-form]').forEach(form => {
    let busy = false;
    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (event.defaultPrevented && !form.reportValidity()) return;
      if (busy) return; busy = true;
      const status = form.querySelector('[data-save-status]');
      const buttons = [...form.querySelectorAll('button[type="submit"]')];
      const data = new FormData(form); if (event.submitter?.name) data.set(event.submitter.name, event.submitter.value);
      form.querySelectorAll('[aria-invalid="true"]').forEach(input => input.removeAttribute('aria-invalid'));
      buttons.forEach(button => { button.disabled = true; }); if (status) status.textContent = 'در حال ذخیره…';
      try {
        const result = await send(form, data); if (status) status.textContent = result.message;
        if(form.matches('[data-async-save]') && result.phone_verification) document.dispatchEvent(new CustomEvent('profile:account-saved',{detail:result}));
        if(form.matches('[data-member-form]')) {
          const badge=form.closest('.member-profile')?.querySelector('header .badge');
          if(badge) { badge.textContent=result.complete ? 'تکمیل‌شده' : 'در حال تکمیل'; badge.classList.toggle('badge--success',!!result.complete); }
        }
        form.querySelector('input[type="file"]')?.value && (form.querySelector('input[type="file"]').value = '');
        if (form.matches('[data-survey-form]') && result.recorded) {
          form.replaceChildren(); const title = document.createElement('h2'); title.textContent = 'نظر شما ثبت شد. سپاس از همراهی‌تان.';
          const link = document.createElement('a'); link.href = '/resources'; link.className = 'btn btn--primary'; link.textContent = 'دریافت ابزارهای رایگان'; form.append(title, link);
        }
      } catch (error) {
        if (status) status.textContent = error.message;
        if (error.errors) {
          let first;
          Object.entries(error.errors).forEach(([name, messages]) => {
            const input = form.elements.namedItem(name); if (input?.setAttribute) { input.setAttribute('aria-invalid', 'true'); input.closest('details') && (input.closest('details').open = true); first ||= input; }
          });
          form.dispatchEvent(new CustomEvent('profile:invalid', { detail: { input: first } }));
          first?.focus({ preventScroll: true });
          if (status) status.textContent = Object.values(error.errors).flat().join(' ');
        }
      } finally { busy = false; buttons.forEach(button => { button.disabled = form.dataset.surveyClosed==='true'; }); }
    });
  });
  const community = document.querySelector('[data-community-toggle]');
  if (community) {
    const checkbox = community.querySelector('[name="requested"]'); const status = community.querySelector('[data-community-status]');
    checkbox.addEventListener('change', async () => {
      const wanted = checkbox.checked; const data = new FormData(community); data.set('requested', wanted ? '1' : '0');
      checkbox.disabled = true; status.textContent = 'در حال ثبت انتخاب…';
      try { const result = await send(community, data); checkbox.checked = result.requested; status.textContent = result.message; }
      catch (error) { checkbox.checked = !wanted; status.textContent = error.message; }
      finally { checkbox.disabled = false; }
    });
  }
  const profile = document.querySelector('[data-member-form]');
  const key = `mentoris-view:${location.pathname}:${profile?.dataset.stateKey || ''}`;
  const saved = storage.read(key); const details = [...document.querySelectorAll('.member-section,.account-details')];
  if (saved?.open) details.forEach((item, index) => { if (typeof saved.open[index] === 'boolean') item.open = saved.open[index]; });
  const persist = () => storage.write(key, { scroll: scrollY, open: details.map(item => item.open) });
  window.addEventListener('pagehide', persist);
  details.forEach(item => item.addEventListener('toggle', persist));
  if (saved && performance.getEntriesByType('navigation')[0]?.type === 'reload' && !location.hash) {
    Promise.resolve(document.fonts?.ready).then(() => requestAnimationFrame(() => window.scrollTo({ top: saved.scroll || 0, behavior: 'instant' })));
  }
}

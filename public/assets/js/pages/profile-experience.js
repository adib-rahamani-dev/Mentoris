import { sendProfile as send, simpleProfileSave } from './profile-transport.js?v=1.0.0';
import { storeProfileDraft, clearProfileDraft } from './profile-draft.js?v=1.0.0';
const storage = {
  read(key) { try { return JSON.parse(sessionStorage.getItem(key) || 'null'); } catch { return null; } },
  write(key, value) { try { sessionStorage.setItem(key, JSON.stringify(value)); } catch {} }
};
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
  const registerForm = document.querySelector('[data-quick-register]');
  const sendCode = registerForm?.querySelector('[data-registration-send]');
  if (sendCode) {
    const phone = registerForm.elements.phone;
    const status = registerForm.querySelector('[data-registration-status]');
    const code = registerForm.elements.phone_code;
    let busy = false, retryAt = 0, lastPhone = '';
    const normalizePhone = value => value.replace(/[۰-۹٠-٩]/g, char => '۰۱۲۳۴۵۶۷۸۹'.includes(char) ? String('۰۱۲۳۴۵۶۷۸۹'.indexOf(char)) : String('٠١٢٣٤٥٦٧٨٩'.indexOf(char))).replace(/[\s().\-\u200e\u200f]+/g, '').replace(/^(?:\+98|0098|98)(?=9\d{9}$)/, '0');
    const update = () => {
      const remaining = Math.max(0, Math.ceil((retryAt - Date.now()) / 1000));
      sendCode.disabled = busy || remaining > 0;
      sendCode.textContent = busy ? 'در حال ارسال…' : remaining ? `ارسال مجدد در ${remaining} ثانیه` : 'دریافت کد پیامکی';
    };
    phone.addEventListener('input', () => {
      if (lastPhone && normalizePhone(phone.value) !== lastPhone) { code.value = ''; status.textContent = 'شماره تغییر کرد؛ برای شمارهٔ جدید کد بگیرید.'; }
    });
    sendCode.addEventListener('click', async () => {
      if (busy || Date.now() < retryAt) return;
      if (!/^09\d{9}$/.test(normalizePhone(phone.value))) { status.textContent = 'شماره موبایل معتبر وارد کنید؛ با ۰۹ یا +۹۸.'; phone.focus(); return; }
      busy = true; update(); status.textContent = 'در حال درخواست کد…';
      const data = new FormData(); data.set('phone', phone.value); data.set('_token', registerForm.querySelector('[name="_token"]').value);
      const destination = normalizePhone(phone.value);
      try {
        const response = await fetch('/register/phone/send', { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('پاسخ سرور معتبر نیست؛ دوباره تلاش کنید.');
        const result = await response.json();
        retryAt = Date.now() + Math.max(0, Math.min(86400, Number(result.retry_after || response.headers.get('Retry-After')) || 0)) * 1000;
        status.textContent = result.message || 'درخواست انجام نشد؛ دوباره تلاش کنید.';
        if (response.ok) {
          lastPhone = destination;
          if (normalizePhone(phone.value) === destination) { code.focus(); }
          else { code.value = ''; status.textContent = 'شماره هنگام ارسال تغییر کرد؛ کد برای شمارهٔ قبلی ارسال شد.'; }
        }
      } catch (error) { status.textContent = error.message || 'ارتباط برقرار نشد؛ دوباره تلاش کنید.'; }
      finally { busy = false; update(); }
    });
    // The browser pauses/resumes this timer across its back/forward cache.
    setInterval(update, 1000);
  }
  registerForm?.addEventListener('submit', event => {
    if (event.target.reportValidity()) { const button = event.target.querySelector('[type="submit"]'); button.disabled = true; button.textContent = 'در حال ساخت حساب…'; }
  });
  document.querySelectorAll('[data-member-form],[data-async-save],[data-survey-form]').forEach(form => {
    let busy = false;
    form.addEventListener('input',()=>storeProfileDraft(form));form.addEventListener('change',()=>storeProfileDraft(form));
    if(form.dataset.draftRestored==='true'){const status=form.querySelector('[data-save-status]');if(status)status.textContent='پاسخ‌های ذخیره‌نشده برگشت. برای ثبت نهایی، ذخیره کنید.';}
    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (event.defaultPrevented && !form.reportValidity()) return;
      if (busy) return; busy = true;
      const status = form.querySelector('[data-save-status]');
      const buttons = [...form.querySelectorAll('button[type="submit"]')];
      const data = new FormData(form); if (event.submitter?.name) data.set(event.submitter.name, event.submitter.value);
      storeProfileDraft(form);
      const labels=buttons.map(button=>button.textContent);
      const controls=[...form.querySelectorAll('input:not([type=hidden]),select,textarea')].map(input=>({input,disabled:input.disabled}));
      form.querySelectorAll('[aria-invalid="true"]').forEach(input => input.removeAttribute('aria-invalid'));
      form.querySelectorAll('[data-profile-error]').forEach(node=>node.remove());
      buttons.forEach(button => { button.disabled = true; });if(event.submitter)event.submitter.textContent='در حال ذخیره…'; if (status){status.textContent='در حال ذخیره…';status.dataset.state='saving';}
      controls.forEach(({input})=>input.disabled=true);
      try {
        const result = await send(form, data);clearProfileDraft(form); if (status){status.textContent=result.message;status.dataset.state='success';}
        if(form.matches('[data-async-save]') && result.phone_verification) document.dispatchEvent(new CustomEvent('profile:account-saved',{detail:result}));
        if(form.matches('[data-member-form]')) {
          const badge=form.closest('.member-profile')?.querySelector('header .badge');
          if(badge) { badge.textContent=result.complete ? 'تکمیل‌شده' : 'در حال تکمیل'; badge.classList.toggle('badge--success',!!result.complete); }
          form.dispatchEvent(new CustomEvent('profile:saved',{detail:result}));
        }
        form.querySelector('input[type="file"]')?.value && (form.querySelector('input[type="file"]').value = '');
        if (form.matches('[data-survey-form]') && result.recorded) {
          form.replaceChildren(); const title = document.createElement('h2'); title.textContent = 'نظر شما ثبت شد. سپاس از همراهی‌تان.';
          const link = document.createElement('a'); link.href = '/resources'; link.className = 'btn btn--primary'; link.textContent = 'دریافت ابزارهای رایگان'; form.append(title, link);
        }
        if(typeof result.redirect==='string' && result.redirect.startsWith('/') && !result.redirect.startsWith('//') && new URL(result.redirect,location.origin).origin===location.origin)location.assign(result.redirect);
      } catch (error) {
        controls.forEach(({input,disabled})=>input.disabled=disabled);
        if (status){status.textContent=error.message;status.dataset.state='error';}
        if(error.simpleSave && status && form.matches('[data-member-form],[data-async-save]')){
          const fallback=document.createElement('button');fallback.type='button';fallback.className='btn btn--secondary btn--sm';fallback.textContent='ذخیره به روش ساده';fallback.addEventListener('click',()=>simpleProfileSave(form,data.get('mode')));status.append(fallback);
        }
        if (error.errors) {
          let first;
          Object.entries(error.errors).forEach(([name, messages]) => {
            const input = form.elements.namedItem(name) || (name==='training_courses'?form.querySelector('[data-course-name]'):null); if (input?.setAttribute) { input.setAttribute('aria-invalid', 'true'); input.closest('details') && (input.closest('details').open = true); first ||= input;const hint=document.createElement('small');hint.className='form-error';hint.dataset.profileError='';hint.textContent=Array.isArray(messages)?messages[0]:messages;input.closest('.form-group')?.append(hint); }
          });
          form.dispatchEvent(new CustomEvent('profile:invalid', { detail: { input: first } }));
          first?.focus({ preventScroll: true });
          if (status) status.textContent = 'فیلدهای مشخص‌شده را بررسی کنید. '+Object.values(error.errors).flat()[0];
        }
      } finally { busy = false; controls.forEach(({input,disabled})=>input.disabled=disabled);buttons.forEach((button,index) => { button.disabled = form.dataset.surveyClosed==='true';button.textContent=labels[index]; }); }
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
  const key = `mentoris-view.v2:${location.pathname}:${profile?.dataset.stateKey || ''}`;
  const saved = storage.read(key); const details = [...document.querySelectorAll('.member-extras,.member-section,.account-details')];
  if (saved?.open) details.forEach((item, index) => { if (typeof saved.open[index] === 'boolean') item.open = saved.open[index]; });
  const persist = () => storage.write(key, { scroll: scrollY, open: details.map(item => item.open) });
  window.addEventListener('pagehide', persist);
  details.forEach(item => item.addEventListener('toggle', persist));
  if (saved && performance.getEntriesByType('navigation')[0]?.type === 'reload' && !location.hash) {
    Promise.resolve(document.fonts?.ready).then(() => requestAnimationFrame(() => window.scrollTo({ top: saved.scroll || 0, behavior: 'instant' })));
  }
}

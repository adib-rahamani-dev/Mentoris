export function initPhoneVerification() {
  const card = document.querySelector('[data-phone-verification]');
  if (!card) return;
  const send = card.querySelector('[data-phone-send]');
  const confirm = card.querySelector('[data-phone-confirm]');
  const status = card.querySelector('[data-phone-status]');
  let busy = false;
  let deadline = Date.now() + Number(card.dataset.retry || 0) * 1000;
  const tick = () => {
    const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
    send.querySelector('button').disabled = busy || card.dataset.ready !== 'true' || remaining > 0;
    card.querySelector('[data-phone-countdown]').textContent = remaining ? `ارسال دوباره پس از ${remaining.toLocaleString('fa-IR')} ثانیه` : '';
  };
  const update = state => {
    if (!state) return;
    card.dataset.ready = String(state.ready); card.dataset.verified = String(state.verified);
    deadline = Date.now() + (state.retry_after || 0) * 1000;
    card.querySelector('[data-phone-number]').textContent = state.phone;
    card.querySelector('[data-phone-badge]').textContent = state.verified ? 'تأییدشده' : 'اختیاری';
    card.querySelector('[data-phone-actions]').hidden = state.verified;
    confirm.hidden = !state.pending;
    tick();
  };
  for (const form of [send, confirm]) form.addEventListener('submit', async event => {
    event.preventDefault(); if (busy || !form.reportValidity()) return;
    busy = true; tick(); confirm.querySelector('button').disabled = true;
    status.textContent = form === send ? 'در حال ارسال کد…' : 'در حال بررسی کد…';
    try {
      const response = await fetch(form.action, { method: 'POST', credentials: 'same-origin', body: new FormData(form), headers: { Accept: 'application/json' } });
      const result = await response.json(); update(result.phone_verification);
      const retry = Number(response.headers.get('Retry-After') || 0);
      if (retry > 0) deadline = Math.max(deadline, Date.now() + retry * 1000);
      status.textContent = result.message || 'عملیات انجام نشد؛ دوباره تلاش کنید.';
      if (response.ok && form === send && result.phone_verification?.pending) confirm.elements.code.focus({ preventScroll: true });
      if (response.ok && form === confirm) confirm.reset();
    } catch { status.textContent = 'پاسخ سرور دریافت نشد. کمی بعد صفحه را تازه کنید؛ درخواست پیاپی نفرستید.'; deadline = Math.max(deadline, Date.now() + 90000); }
    finally { busy = false; confirm.querySelector('button').disabled = false; tick(); }
  });
  document.addEventListener('profile:account-saved', event => { update(event.detail?.phone_verification); status.textContent = card.dataset.verified === 'true' ? 'شمارهٔ موبایل شما تأیید شده است.' : 'شمارهٔ فعلی ذخیره شد. می‌توانید کد تأیید بگیرید.'; });
  document.querySelector('a[href="#account-contact"]')?.addEventListener('click', () => { document.querySelector('#account-contact').open = true; });
  let timer;
  const resume = () => { clearInterval(timer); tick(); timer = setInterval(tick, 1000); };
  resume(); window.addEventListener('pagehide', () => clearInterval(timer)); window.addEventListener('pageshow', resume);
}

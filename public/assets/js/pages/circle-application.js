export function initCircleApplication() {
  document.querySelectorAll('[data-circle-application]').forEach(form => {
    const dialog = document.createElement('dialog');
    dialog.className = 'circle-review';
    dialog.setAttribute('aria-labelledby', 'circle-review-title');
    dialog.innerHTML = `<div class="circle-review__body"><span class="eyebrow">یک نگاه پیش از ارسال</span><h2 id="circle-review-title">اطلاعات شما درست است؟</h2><p data-review-event></p><dl data-review-fields></dl><p class="circle-review__note">پس از ثبت درخواست وارد صفحه پرداخت می‌شوید. پذیرش بررسی می‌شود و در صورت عدم تأیید، مبلغ واریزی بازگردانده خواهد شد.</p><div class="circle-review__actions"><button type="button" class="btn btn--primary" data-review-confirm>تأیید و ارسال درخواست</button><button type="button" class="btn btn--secondary" data-review-edit>ویرایش اطلاعات</button></div></div>`;
    document.body.append(dialog);
    const fileInput = form.elements.namedItem('resume');
    const fields = {name:'نام و نام خانوادگی',phone:'شماره موبایل',city:'شهر',education:'مقطع تحصیلی',university:'دانشگاه',specialization:'رشته و گرایش',experience:'سابقه بالینی',approach:'رویکرد درمانی',practice_areas:'حوزه فعالیت',professional_number:'شماره نظام / دانشجویی',motivation:'توضیحات'};
    let confirmed = false;
    const submitButton=form.querySelector('button[type=submit],button:not([type])'),submitText=submitButton?.textContent;
    window.addEventListener('pageshow',()=>{confirmed=false;form.removeAttribute('aria-busy');if(submitButton){submitButton.disabled=false;submitButton.textContent=submitText;}});
    const close = () => { if(typeof dialog.close==='function') dialog.close(); else dialog.removeAttribute('open'); };
    dialog.querySelector('[data-review-edit]').addEventListener('click', close);
    dialog.querySelector('[data-review-confirm]').addEventListener('click', () => {
      confirmed = true; close();
      form.requestSubmit();
    });
    fileInput.addEventListener('change', () => {
      fileInput.setCustomValidity(fileInput.files[0]?.size > 8388608 ? 'حجم رزومه باید حداکثر ۸ مگابایت باشد.' : '');
    });
    form.addEventListener('submit', event => {
      if(confirmed){confirmed=false;form.setAttribute('aria-busy','true');const button=form.querySelector('button[type=submit],button:not([type])');if(button){button.disabled=true;button.textContent='در حال ثبت درخواست…';}return;}
      event.preventDefault();
      const list=dialog.querySelector('[data-review-fields]'); list.replaceChildren();
      const add=(label,value)=>{const row=document.createElement('div');const term=document.createElement('dt');term.textContent=label;const detail=document.createElement('dd');detail.textContent=value||'ثبت نشده';row.append(term,detail);list.append(row);};
      Object.entries(fields).forEach(([name,label])=>add(label,form.elements.namedItem(name)?.value.trim()));
      const saved=form.querySelector('[data-saved-resume]');
      add('رزومه',fileInput.files[0]?.name || (saved?.checked ? saved.dataset.savedResume : 'بدون رزومه (اختیاری)'));
      dialog.querySelector('[data-review-event]').textContent=form.dataset.eventTitle;
      if(typeof dialog.showModal==='function')dialog.showModal();else dialog.setAttribute('open','');
      const title=dialog.querySelector('h2');title.tabIndex=-1;title.focus({preventScroll:true});dialog.scrollTop=0;
    });
  });
}

const remember = (key, value) => { try { sessionStorage.setItem(key, value); } catch {} };
const recall = key => { try { return sessionStorage.getItem(key); } catch { return null; } };
export function initProfileWizard() {
  document.querySelectorAll('[data-profile-wizard]').forEach(form => {
    const media = matchMedia('(max-width: 760px)');
    const fieldset = form.querySelector('.member-fieldset');
    const key = `mentoris-profile-step:${form.dataset.stateKey}`;
    const panel = document.createElement('div'); panel.className = 'profile-stepper'; panel.hidden = true;
    panel.innerHTML = '<div class="profile-stepper__head"><span data-step-section></span><strong data-step-count></strong></div><progress value="0" max="1" aria-label="پیشرفت اطلاعات تکمیلی"></progress><p data-step-title aria-live="polite"></p>';
    fieldset.prepend(panel);
    const controls = document.createElement('div'); controls.className = 'profile-stepper__controls'; controls.hidden = true;
    const arrow='<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>';
    controls.innerHTML = `<button class="btn btn--ghost" type="button" data-step-prev>${arrow} قبلی</button><button class="btn btn--ghost" type="button" data-step-skip>فعلاً رد می‌کنم</button><button class="btn btn--primary" type="button" data-step-next>بعدی <span class="step-arrow-next">${arrow}</span></button>`;
    fieldset.querySelector('.member-form__actions').before(controls);
    const review = document.createElement('div'); review.className = 'profile-wizard-review'; review.dataset.wizardReview = ''; review.hidden = true;
    review.innerHTML = '<span class="profile-wizard-review__mark"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="24" cy="24" r="21" stroke="currentColor" stroke-width="2"/><path d="m14 24 7 7 14-14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span><h3>برای امروز کافی است.</h3><p>اطلاعاتی را که وارد کردید ذخیره کنید. باقی را هر زمان خواستید تکمیل کنید.</p>';
    controls.before(review);
    const courses = form.querySelector('[data-course-list]');
    const saveButton=form.querySelector('.member-form__actions button[type=submit]');
    const saveLabel=saveButton?.textContent;
    const manager = document.createElement('div'); manager.className = 'profile-course-manager'; manager.dataset.courseManager = '';
    manager.innerHTML = '<h3>دوره‌ای گذرانده‌اید؟</h3><p>می‌توانید دوره اضافه کنید یا این مرحله را رد کنید.</p><div data-course-shortcuts></div>';
    courses.before(manager);
    const add = form.querySelector('[data-add-course]'); if(add) manager.append(add);
    const courseStatus = form.querySelector('[data-course-status]'); if(courseStatus) manager.append(courseStatus);
    let steps = [], index = 0, pendingName = recall(key), advancing = false;
    const nameOf = node => node.querySelector('[name]')?.name || (node === manager ? 'courses' : 'review');
    const eligible = node => !node.closest('[hidden]') && (!node.querySelector('[name]') || !node.querySelector('[name]').disabled);
    function rebuild(focus = false) {
      if(!media.matches) return;
      form.querySelectorAll('.member-section').forEach(section => { section.open = true; });
      // Hidden conditional sections remain disabled by the existing profile form logic.
      const selected = steps[index];
      steps = [...fieldset.querySelectorAll('.form-group,.member-consent,[data-course-manager],[data-wizard-review]')].filter(node => node===review || eligible(node));
      if(pendingName) { const found=steps.findIndex(node=>nameOf(node)===pendingName); if(found>=0) index=found; pendingName=null; }
      else if(selected && steps.includes(selected)) index=steps.indexOf(selected);
      index=Math.max(0,Math.min(index,steps.length-1)); render(focus);
    }
    function render(focus=false) {
      form.querySelectorAll('.is-wizard-active,.is-wizard-section,.is-wizard-course').forEach(node=>node.classList.remove('is-wizard-active','is-wizard-section','is-wizard-course'));
      const current=steps[index]; if(!current) return;
      form.classList.toggle('is-wizard-managing-courses',current===manager);
      current.classList.add('is-wizard-active'); review.hidden=current!==review;
      const section=current.closest('.member-section'); section?.classList.add('is-wizard-section');
      const course=current.closest('[data-course-row]'); course?.classList.add('is-wizard-course');
      if(current===manager) { courses.closest('.member-section')?.classList.add('is-wizard-section'); courses.querySelectorAll('[data-course-row]').forEach(node=>node.classList.add('is-wizard-course')); }
      const summary=section?.querySelector('summary')?.cloneNode(true); summary?.querySelectorAll('small,span').forEach(node=>node.remove());
      panel.querySelector('[data-step-section]').textContent=current===review ? 'مرور و ذخیره' : (summary?.textContent.trim() || 'نوع عضویت');
      panel.querySelector('[data-step-count]').textContent=`${(index+1).toLocaleString('fa-IR')} از ${steps.length.toLocaleString('fa-IR')}`;
      panel.querySelector('progress').max=steps.length; panel.querySelector('progress').value=index+1;
      panel.querySelector('[data-step-title]').textContent=current.querySelector('.form-label')?.textContent || (current===manager ? 'سوابق یادگیری' : current===review ? 'اطلاعات آمادهٔ ذخیره است' : 'انتخاب اختیاری');
      controls.querySelector('[data-step-prev]').disabled=index===0;
      controls.querySelector('[data-step-next]').hidden=index===steps.length-1;
      controls.querySelector('[data-step-skip]').hidden=index===steps.length-1 || !!current.querySelector('[required]');
      if(saveButton) saveButton.textContent=current===review ? 'ذخیره پروفایل' : 'ذخیره تا همین‌جا';
      remember(key,nameOf(current));
      if(!matchMedia('(prefers-reduced-motion: reduce)').matches) current.animate([{opacity:0,transform:'translateY(8px)'},{opacity:1,transform:'none'}],{duration:220,easing:'ease-out'});
      if(focus) current.querySelector('input:not([type=file]),select,textarea')?.focus({preventScroll:true});
    }
    function next(skip=false) {
      const current=steps[index];
      if(!skip && current) { const input=current.querySelector('input,select,textarea'); if(input && !input.reportValidity()) return; }
      if(index<steps.length-1) { index++; render(true); }
    }
    controls.querySelector('[data-step-next]').addEventListener('click',()=>next());
    controls.querySelector('[data-step-skip]').addEventListener('click',()=>next(true));
    controls.querySelector('[data-step-prev]').addEventListener('click',()=>{ if(index>0) { index--; render(true); } });
    form.addEventListener('keydown',event=>{ if(media.matches && event.key==='Enter' && event.target.matches('input:not([type=file]):not([type=checkbox])')) { event.preventDefault(); next(); } });
    form.addEventListener('change',event=>{
      if(!media.matches) return;
      rebuild();
      if(event.target.tagName==='SELECT' && event.target.value && steps[index]?.contains(event.target) && !advancing) {
        advancing=true; setTimeout(()=>{ next(); advancing=false; },160);
      }
    });
    form.addEventListener('profile:invalid',event=>{
      if(!media.matches) return;
      const target=event.detail?.input; const step=target?.closest('.form-group'); const found=steps.indexOf(step);
      if(found>=0) { index=found; render(true); }
    });
    let showingInvalid=false;
    form.addEventListener('invalid',event=>{
      if(!media.matches) return;
      if(showingInvalid) return;
      const found=steps.indexOf(event.target.closest('.form-group'));
      if(found>=0) { showingInvalid=true; queueMicrotask(()=>{ showingInvalid=false; }); index=found; render(); }
    },true);
    let previousNodes=new Set(courses.querySelectorAll('.form-group'));
    function refreshCourses() {
      const shortcuts=manager.querySelector('[data-course-shortcuts]'); shortcuts.replaceChildren();
      courses.querySelectorAll('[data-course-row]').forEach(row=>{
        const first=row.querySelector('.form-group'); const button=document.createElement('button'); button.type='button'; button.className='btn btn--ghost btn--sm'; button.textContent=row.querySelector('[data-course-name]')?.value || 'ویرایش دوره';
        button.addEventListener('click',()=>{ const found=steps.indexOf(first); if(found>=0) { index=found; render(true); } }); shortcuts.append(button);
      });
    }
    refreshCourses();
    new MutationObserver(()=>{
      const nodes=[...courses.querySelectorAll('.form-group')]; const fresh=nodes.find(node=>!previousNodes.has(node)); previousNodes=new Set(nodes);
      refreshCourses();
      if(fresh) pendingName=nameOf(fresh); rebuild(!!fresh);
    }).observe(courses,{childList:true,subtree:true});
    function activate() {
      form.classList.toggle('wizard-mode',media.matches); panel.hidden=controls.hidden=!media.matches;
      review.hidden=true;
      if(media.matches) rebuild();
      else { if(saveButton) saveButton.textContent=saveLabel; form.classList.remove('is-wizard-managing-courses'); form.querySelectorAll('.is-wizard-active,.is-wizard-section,.is-wizard-course').forEach(node=>node.classList.remove('is-wizard-active','is-wizard-section','is-wizard-course')); }
    }
    media.addEventListener('change',activate); activate();
  });
}

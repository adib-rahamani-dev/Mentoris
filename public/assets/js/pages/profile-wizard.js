export function initProfileWizard(){
  document.querySelectorAll('[data-profile-wizard]').forEach(form=>{
    const media=matchMedia('(max-width:760px)'),fieldset=form.querySelector('.member-fieldset'),extras=form.querySelector('[data-profile-extras]');
    const key=`mentoris.profile-step.v2:${form.dataset.stateKey}`;let saved;
    try{saved=JSON.parse(sessionStorage.getItem(key)||'null');}catch{}
    const panel=document.createElement('div');panel.className='profile-stepper';panel.hidden=true;
    panel.innerHTML='<div class="profile-stepper__head"><span data-step-section>اطلاعات ضروری</span><strong data-step-count></strong></div><progress value="0" max="3" aria-label="پیشرفت پروفایل"></progress><p data-step-title aria-live="polite"></p>';fieldset.prepend(panel);
    const controls=document.createElement('div');controls.className='profile-stepper__controls';controls.hidden=true;
    controls.innerHTML='<button class="btn btn--ghost" type="button" data-step-prev>قبلی</button><button class="btn btn--ghost" type="button" data-step-skip>فعلاً رد می‌کنم</button><button class="btn btn--primary" type="button" data-step-next>ادامه</button>';
    fieldset.querySelector('.member-form__actions').before(controls);
    const review=document.createElement('div');review.className='profile-wizard-review';review.hidden=true;
    review.innerHTML='<h3>اطلاعات ضروری آماده است</h3><p>ذخیره کنید؛ لازم نیست بخش‌های اختیاری را همین حالا پر کنید.</p><button class="btn btn--secondary btn--sm" type="button" data-open-extras>افزودن اطلاعات اختیاری</button>';controls.before(review);
    const list=form.querySelector('[data-course-list]'),manager=document.createElement('div');manager.className='profile-course-manager';manager.dataset.profileCourseManager='';manager.innerHTML='<h3>دوره‌های گذرانده‌شده</h3><p>ثبت دوره اختیاری است.</p><div data-course-shortcuts></div>';list.before(manager);const add=form.querySelector('[data-add-course]');if(add)manager.append(add);const courseStatus=form.querySelector('[data-course-status]');if(courseStatus)manager.append(courseStatus);
    let mode=saved?.mode==='extras'?'extras':'core',steps=[],index=0,pending=saved?.name,initialized=false,invalid=false;
    const nameOf=node=>node===review?'review':node===manager?'courses':node.querySelector('[name]')?.name || 'extra';
    const eligible=node=>!node.closest('[hidden]') && !node.querySelector('[name]')?.disabled;
    function core(){const groups=[...form.querySelectorAll('.member-essentials>.form-group')];return form.elements.member_type.value==='other'?groups.slice(0,1):groups;}
    function rebuild(){
      if(!media.matches)return;
      const current=steps[index];
      if(mode==='core')steps=[...core(),review];
      else{
        extras.open=true;form.querySelectorAll('.member-section').forEach(section=>section.open=true);
        steps=[...form.querySelectorAll('.member-section .form-group,.member-consent,[data-profile-course-manager]')].filter(eligible);steps.push(review);
      }
      if(pending){const found=steps.findIndex(step=>nameOf(step)===pending);if(found>=0)index=found;pending=null;}
      else if(!initialized && mode==='core'){const missing=core().findIndex(group=>!group.querySelector('[name]').value.trim());index=missing>=0?missing:steps.length-1;}
      else if(current && steps.includes(current))index=steps.indexOf(current);
      initialized=true;index=Math.max(0,Math.min(index,steps.length-1));render();
    }
    function render(focus=false){
      form.querySelectorAll('.is-wizard-active,.is-wizard-section,.is-wizard-course').forEach(node=>node.classList.remove('is-wizard-active','is-wizard-section','is-wizard-course'));
      const current=steps[index];if(!current)return;
      form.querySelector('.member-essentials').hidden=current===review || mode==='extras';
      form.dataset.wizardMode=mode;extras.hidden=mode==='core';current.classList.add('is-wizard-active');current.closest('.member-section')?.classList.add('is-wizard-section');current.closest('[data-course-row]')?.classList.add('is-wizard-course');review.hidden=current!==review;
      const count=mode==='core'?core().length:steps.length;panel.querySelector('[data-step-count]').textContent=`${Math.min(index+1,count).toLocaleString('fa-IR')} از ${count.toLocaleString('fa-IR')}`;
      panel.querySelector('[data-step-section]').textContent=mode==='core'?'اطلاعات ضروری':'اطلاعات اختیاری';panel.querySelector('progress').max=count;panel.querySelector('progress').value=Math.min(index+1,count);
      panel.querySelector('[data-step-title]').textContent=current===review?'مرور و ذخیره':current.querySelector('.form-label')?.textContent || 'انتخاب اختیاری';
      controls.querySelector('[data-step-prev]').disabled=index===0;controls.querySelector('[data-step-next]').hidden=current===review;controls.querySelector('[data-step-skip]').hidden=mode==='core' || current===review;
      review.querySelector('[data-open-extras]').textContent=mode==='core'?'افزودن اطلاعات اختیاری':'بازگشت به اطلاعات ضروری';
      try{sessionStorage.setItem(key,JSON.stringify({mode,name:nameOf(current)}));}catch{}
      if(!matchMedia('(prefers-reduced-motion: reduce)').matches)current.animate([{opacity:0,transform:'translateY(6px)'},{opacity:1,transform:'none'}],{duration:180,easing:'ease-out'});
      if(focus)current.querySelector('input:not([type=file]),select,textarea')?.focus({preventScroll:true});
    }
    function next(skip=false){
      const input=steps[index]?.querySelector('input,select,textarea');
      if(input && !skip){if(mode==='core' && !input.value.trim()){input.setCustomValidity('برای ادامه این مورد را تکمیل کنید.');input.reportValidity();return;}input.setCustomValidity('');if(!input.reportValidity())return;}
      if(index<steps.length-1){index++;render(true);}
    }
    controls.querySelector('[data-step-next]').addEventListener('click',()=>next());controls.querySelector('[data-step-skip]').addEventListener('click',()=>next(true));controls.querySelector('[data-step-prev]').addEventListener('click',()=>{if(index>0){index--;render(true);}});
    review.querySelector('[data-open-extras]').addEventListener('click',()=>{mode=mode==='core'?'extras':'core';index=0;pending=null;rebuild();});
    form.addEventListener('input',event=>event.target.setCustomValidity?.(''));
    form.addEventListener('keydown',event=>{if(media.matches && event.key==='Enter' && event.target.matches('input:not([type=file]):not([type=checkbox])')){event.preventDefault();next();}});
    form.addEventListener('change',()=>{if(media.matches)rebuild();});
    function showInvalid(input){
      if(!media.matches || !input)return;
      const group=input.closest('.form-group');if(!group)return;
      mode=group.closest('.member-essentials')?'core':'extras';pending=nameOf(group);rebuild();render(true);
    }
    form.addEventListener('profile:invalid',event=>showInvalid(event.detail?.input));
    form.addEventListener('invalid',event=>{if(invalid)return;invalid=true;showInvalid(event.target);queueMicrotask(()=>invalid=false);},true);
    form.addEventListener('profile:saved',event=>{if(event.detail.complete){review.querySelector('h3').textContent='پروفایل شما ذخیره شد';review.querySelector('p').textContent='هر زمان خواستید سوابق بیشتر را اضافه کنید.';}});
    let previous=new Set(list.querySelectorAll('.form-group'));
    function shortcuts(){const holder=manager.querySelector('[data-course-shortcuts]');holder.replaceChildren();list.querySelectorAll('[data-course-row]').forEach(row=>{const button=document.createElement('button');button.type='button';button.className='btn btn--ghost btn--sm';button.textContent=row.querySelector('[data-course-name]')?.value || 'ویرایش دوره';button.addEventListener('click',()=>{mode='extras';pending=nameOf(row.querySelector('.form-group'));rebuild();});holder.append(button);});}
    shortcuts();new MutationObserver(()=>{const groups=[...list.querySelectorAll('.form-group')],fresh=groups.find(group=>!previous.has(group));previous=new Set(groups);shortcuts();if(fresh){mode='extras';pending=nameOf(fresh);}rebuild();if(fresh && media.matches)render(true);}).observe(list,{childList:true});
    function activate(){
      form.classList.toggle('wizard-mode',media.matches);panel.hidden=controls.hidden=!media.matches;review.hidden=true;
      if(media.matches)rebuild();else{extras.hidden=false;form.querySelector('.member-essentials').hidden=false;form.querySelectorAll('.is-wizard-active,.is-wizard-section,.is-wizard-course').forEach(node=>node.classList.remove('is-wizard-active','is-wizard-section','is-wizard-course'));}
    }
    media.addEventListener('change',activate);activate();
  });
}

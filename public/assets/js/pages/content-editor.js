export function initContentEditor() {
  const form=document.querySelector('[data-content-editor]'); if(!form) return;
  const panels=[...form.querySelectorAll('[data-editor-panel]')];
  const prev=form.querySelector('[data-editor-prev]'), next=form.querySelector('[data-editor-next]'), submit=form.querySelector('[type="submit"]');
  const stepButtons=[...form.querySelectorAll('[data-editor-step]')];
  const status=form.querySelector('[data-editor-save-status]');
  let step=Number(form.dataset.startStep)||1;
  const show=(number,focus=false)=>{
    step=Math.max(1,Math.min(3,number));
    panels.forEach(panel=>{ panel.hidden=Number(panel.dataset.editorPanel)!==step; });
    stepButtons.forEach(button=>{ const selected=Number(button.dataset.editorStep)===step; button.classList.toggle('is-active',selected); if(selected) button.setAttribute('aria-current','step'); else button.removeAttribute('aria-current'); });
    prev.hidden=step===1; next.hidden=step===3; submit.hidden=step!==3;
    if(focus) stepButtons[step-1].focus({preventScroll:true});
  };
  // SEO preview belongs to publishing, even though its existing initializer manages hidden.
  const seo=form.querySelector('[data-content-seo-preview]'); if(seo && form.elements.entity_type.value==='article') { seo.dataset.editorPanel='3'; if(!panels.includes(seo)) panels.push(seo); }
  show(step);
  stepButtons.forEach(button=>button.addEventListener('click',()=>show(Number(button.dataset.editorStep))));
  prev.addEventListener('click',()=>show(step-1,true));
  next.addEventListener('click',()=>{
    const invalid=[...form.querySelectorAll('input,textarea,select')].find(field=>field.required && !field.closest('[hidden]') && !field.checkValidity());
    if(invalid) { invalid.reportValidity(); return; } show(step+1,true);
  });
  form.addEventListener('invalid',event=>{
    const target=event.target, panel=target.closest('[data-editor-panel]'); if(panel) show(Number(panel.dataset.editorPanel));
    const language=target.closest('[role="tabpanel"]');
    if(language?.hidden) form.querySelector(`[aria-controls="${language.id}"]`)?.click();
  },true);
  form.addEventListener('input',()=>{ status.textContent='تغییرات هنوز ذخیره نشده‌اند.'; });
  form.addEventListener('submit',event=>{ if(step!==3) { event.preventDefault(); next.click(); return; } submit.disabled=true; status.textContent='در حال ذخیره در دیتابیس…'; });
  window.addEventListener('pageshow',()=>{ submit.disabled=false; });
  form.querySelectorAll('[data-collection]').forEach(collection=>{
    const rows=collection.querySelector('[data-collection-rows]'); let index=rows.children.length;
    collection.querySelector('[data-add-row]').addEventListener('click',()=>{
      if(rows.children.length>=40) { status.textContent='حداکثر ۴۰ ردیف قابل ثبت است.'; return; }
      const holder=document.createElement('template'); holder.innerHTML=collection.querySelector('template').innerHTML.replaceAll('__INDEX__',String(index++));
      rows.append(holder.content.cloneNode(true)); rows.lastElementChild.open=true; rows.lastElementChild.querySelector('input').focus();
    });
    rows.addEventListener('click',event=>{ if(event.target.closest('[data-delete-row]')) { event.target.closest('.editor-collection-row').remove(); status.textContent='حذف ردیف بعد از ذخیره اعمال می‌شود.'; } });
    rows.addEventListener('input',event=>{ if(event.target.matches('[data-row-title]')) event.target.closest('.editor-collection-row').querySelector('.editor-collection-title').textContent=event.target.value||'ردیف جدید'; });
  });
  form.querySelectorAll('[data-insert-heading]').forEach(button=>button.addEventListener('click',()=>{
    const field=form.elements[button.dataset.insertHeading]; if(!field) return;
    const text=field.value.slice(field.selectionStart,field.selectionEnd)||'عنوان بخش';
    field.setRangeText(`\n\n## ${text}\n\n`,field.selectionStart,field.selectionEnd,'end'); field.focus(); field.dispatchEvent(new Event('input',{bubbles:true}));
  }));
  const imageInput=form.elements.image_upload, preview=form.querySelector('[data-editor-image-preview]'); let blobUrl='';
  imageInput?.addEventListener('change',()=>{
    const file=imageInput.files[0]; if(!file || !preview || !['image/jpeg','image/png','image/webp'].includes(file.type)) return;
    if(blobUrl) URL.revokeObjectURL(blobUrl); blobUrl=URL.createObjectURL(file); const image=document.createElement('img'); image.src=blobUrl; image.alt='پیش‌نمایش تصویر انتخاب‌شده'; preview.replaceChildren(image);
  });
}

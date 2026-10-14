export function initManualPayments(){
  document.querySelectorAll('[data-receipt-form]').forEach(form=>{
    const input=form.querySelector('[name=receipt]'),preview=form.querySelector('[data-receipt-preview]'),status=form.querySelector('[data-receipt-status]'),submit=form.querySelector('[data-receipt-submit]');
    const submitText=submit.textContent;
    let url;
    input.addEventListener('change',()=>{
      if(url)URL.revokeObjectURL(url);
      preview.hidden=true;input.setCustomValidity('');status.textContent='';
      const file=input.files[0];if(!file)return;
      if(!['image/jpeg','image/png'].includes(file.type) || file.size>8388608){
        input.setCustomValidity('یک تصویر JPG یا PNG با حجم حداکثر ۸ مگابایت انتخاب کنید.');
        status.textContent=input.validationMessage;return;
      }
      url=URL.createObjectURL(file);preview.src=url;preview.hidden=false;
      status.textContent='تصویر را بررسی کنید و سپس رسید را ارسال کنید.';
    });
    form.addEventListener('submit',()=>{submit.disabled=true;submit.textContent='در حال ارسال رسید…';form.setAttribute('aria-busy','true');});
    window.addEventListener('pageshow',()=>{submit.disabled=false;submit.textContent=submitText;form.removeAttribute('aria-busy');});
  });
  document.querySelectorAll('[data-copy-card]').forEach(button=>button.addEventListener('click',async()=>{
    const status=button.parentElement.querySelector('[data-copy-status]');
    try{
      if(navigator.clipboard?.writeText)await navigator.clipboard.writeText(button.dataset.copyCard);
      else {const input=document.createElement('textarea');input.value=button.dataset.copyCard;input.style.position='fixed';input.style.opacity='0';document.body.append(input);input.select();const copied=document.execCommand('copy');input.remove();if(!copied)throw Error('copy');}
      status.textContent='شماره کارت کپی شد.';
    }catch{status.textContent='کپی خودکار ممکن نیست؛ شماره کارت را انتخاب و کپی کنید.';}
  }));
}

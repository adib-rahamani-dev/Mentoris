const statusLabels={open:'منتظر پاسخ تیم',waiting:'در حال بررسی',answered:'پاسخ داده‌شده',closed:'بسته‌شده'};
const node=(tag,text)=>{const item=document.createElement(tag);if(text!==undefined)item.textContent=text;return item;};
export function initSupport(){
 document.querySelectorAll('[data-support-utc]').forEach(item=>{item.textContent=new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short',timeZone:'Asia/Tehran'}).format(new Date(item.dataset.supportUtc.replace(' ','T')+'Z'));});
 const thread=document.querySelector('[data-support-thread]');let polling=false;
 const addMessage=message=>{
  if(document.querySelector(`[data-message-id="${message.id}"]`))return;
  const item=node('article');item.className=`support-message support-message--${message.sender_role==='staff'?'staff':'member'}`;item.dataset.messageId=message.id;
  const header=node('header');header.append(node('strong',message.sender_role==='staff'?'تیم منتوریس':'پیام عضو'),node('time',new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short',timeZone:'Asia/Tehran'}).format(new Date(message.created_at.replace(' ','T')+'Z'))));item.append(header,node('p',message.body));
  for(const file of message.attachments||[]){const wrap=node('div');wrap.className='support-message__file';const path=`/support/attachments/${file.id}`;if(['voice','audio'].includes(file.media_kind)){const audio=node('audio');audio.controls=true;audio.preload='none';audio.src=path;wrap.append(audio);}const link=node('a',file.filename);link.href=path;wrap.append(link);item.append(wrap);}
  document.querySelector('[data-support-messages]').append(item);
 };
 const refresh=async()=>{
  if(!thread||polling||document.hidden)return;polling=true;const status=thread.querySelector('[data-support-sync]');
  try{const response=await fetch(thread.dataset.supportThread,{headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store'});if(!response.ok||!response.headers.get('content-type')?.includes('application/json'))throw new Error('برای ادامهٔ گفتگو، اتصال و ورود به حساب را بررسی کنید.');const data=await response.json();data.messages.forEach(addMessage);document.querySelector('[data-ticket-status]').textContent=statusLabels[data.ticket.status]||data.ticket.status;if(data.ticket.status==='closed'){const form=document.querySelector('[data-support-form]');if(form){for(const input of form.elements)input.disabled=true;}status.textContent='این گفتگو بسته شد؛ برای موضوع تازه تیکت جدید ثبت کنید.';}else status.textContent='گفتگو به‌روز است؛ پاسخ تازه خودکار همین‌جا نمایش داده می‌شود.';}
  catch(error){status.textContent=error.message||'ارتباط موقتاً قطع است؛ متن پیام شما حفظ شده است.';}finally{polling=false;}
 };
 if(thread){setInterval(refresh,15000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});}
 document.querySelectorAll('[data-support-form]').forEach(form=>{
  const key=`mentoris.support.draft.${form.dataset.supportDraft}`,status=form.querySelector('[data-support-status]'),button=form.querySelector('button[type="submit"]')||form.querySelector('button');let sending=false;
  const requestKey=node('input');requestKey.type='hidden';requestKey.name='request_key';requestKey.value=[...crypto.getRandomValues(new Uint8Array(16))].map(value=>value.toString(16).padStart(2,'0')).join('');form.append(requestKey);
  try{const saved=JSON.parse(sessionStorage.getItem(key)||'null');if(saved)for(const name of ['subject','category','message','request_key'])if(typeof saved[name]==='string'&&form.elements[name])form.elements[name].value=saved[name];}catch{}
  form.addEventListener('input',()=>{try{const values={};for(const name of ['subject','category','message','request_key'])if(form.elements[name])values[name]=form.elements[name].value;sessionStorage.setItem(key,JSON.stringify(values));}catch{}});
  form.addEventListener('submit',async event=>{
   event.preventDefault();if(sending)return;const file=form.elements.attachment?.files[0];if(file&&file.size>8388608){status.textContent='حجم فایل باید حداکثر ۸ مگابایت باشد.';return;}if(!form.elements.message.value.trim()&&!file){status.textContent='پیام بنویسید یا فایل انتخاب کنید.';return;}
   sending=true;button.disabled=true;status.textContent='در حال ارسال…';
   try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{Accept:'application/json'}});if(!response.headers.get('content-type')?.includes('application/json'))throw new Error('نشست ورود منقضی شده یا سرور پاسخ نداد؛ پیام حفظ شده است.');const data=await response.json();if(!response.ok)throw new Error(data.message||'ارسال انجام نشد.');try{sessionStorage.removeItem(key);}catch{}if(!thread){location.assign(data.url);return;}form.elements.message.value='';form.elements.attachment.value='';requestKey.value=[...crypto.getRandomValues(new Uint8Array(16))].map(value=>value.toString(16).padStart(2,'0')).join('');status.textContent='پیام ارسال شد.';await refresh();}
   catch(error){status.textContent=error.message;}finally{sending=false;button.disabled=false;}
  });
 });
}

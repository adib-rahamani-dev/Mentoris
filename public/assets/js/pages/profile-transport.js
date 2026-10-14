const headers={Accept:'application/json','X-Requested-With':'XMLHttpRequest'};
const actor=form=>form.dataset.actorKey || document.querySelector('meta[name=account-id]')?.content || (form.matches('[data-member-form]')&&!new URL(form.action).pathname.startsWith('/admin')?form.dataset.stateKey:'');
const error=(message,status=0,extras={})=>Object.assign(new Error(message),{status,...extras});
async function request(url,options={},timeout=20000){
  const abort=new AbortController(),timer=setTimeout(()=>abort.abort(),timeout);
  try{return await fetch(url,{credentials:'same-origin',cache:'no-store',headers,...options,signal:abort.signal});}
  catch{throw error('ارتباط برقرار نشد. پاسخ‌های شما باقی مانده؛ دوباره ذخیره کنید.');}
  finally{clearTimeout(timer);}
}
async function parse(response){
  if(response.redirected && new URL(response.url).pathname==='/login')throw error('برای ذخیره، دوباره وارد حساب شوید.',401);
  let text,data;try{text=await response.text();}catch{throw error('ارتباط قطع شد. پاسخ‌ها باقی مانده؛ دوباره ذخیره کنید.');}
  try{data=JSON.parse(text);}catch{}
  if(!data || typeof data!=='object'){
    const message=response.status===401?'برای ذخیره، دوباره وارد حساب شوید.':response.status===419?'زمان فرم گذشته؛ دوباره ذخیره کنید.':response.status===413?'حجم فایل زیاد است؛ عکس کوچک‌تری انتخاب کنید.':response.status===403?'سرور این درخواست را نپذیرفت. دوباره تلاش کنید یا از ذخیره ساده استفاده کنید.':'ذخیره انجام نشد؛ پاسخ سرور معتبر نیست. پاسخ‌های شما باقی مانده است.';
    throw error(message,response.status,{simpleSave:response.status!==401 && response.status!==419});
  }
  if(!response.ok)throw error(response.status>=500?'ذخیره موقتاً انجام نشد. پاسخ‌های شما باقی مانده؛ دوباره تلاش کنید.':data.message||'ذخیره انجام نشد.',response.status,{errors:data.errors});
  return data;
}
function setToken(token){if(typeof token==='string')document.querySelectorAll('input[name=_token]').forEach(input=>input.value=token);}
async function session(){const state=await parse(await request('/auth/session'));if(typeof state.authenticated!=='boolean' || typeof state.csrf_token!=='string')throw error('ارتباط با حساب برقرار نشد؛ دوباره تلاش کنید.');return state;}
function encoded(data){
  if([...data.values()].some(value=>value instanceof File && value.size>0))return data;
  const body=new URLSearchParams();for(const [name,value] of data)if(typeof value==='string')body.append(name,value);return body;
}
let recovery;
function recover(form,state,expected){
  if(recovery)return recovery;
  recovery=new Promise((resolve,reject)=>{
    const dialog=document.createElement('dialog');dialog.className='profile-recovery-dialog';dialog.setAttribute('dir','rtl');dialog.setAttribute('aria-labelledby','profile-recovery-title');
    dialog.innerHTML='<form class="stack" data-recovery-login><header><span class="eyebrow">ادامه ذخیره اطلاعات</span><h2 id="profile-recovery-title">دوباره وارد حساب شوید</h2><p>فرم شما همین‌جا باقی می‌ماند. با همان حساب وارد شوید تا ذخیره ادامه پیدا کند.</p></header><label class="form-group"><span class="form-label">شماره موبایل یا ایمیل</span><input class="form-control ltr" name="identifier" autocomplete="username" maxlength="120" required></label><label class="form-group"><span class="form-label">رمز عبور</span><input class="form-control ltr" name="password" type="password" autocomplete="current-password" maxlength="128" required></label><p role="status" data-recovery-status></p><button class="btn btn--primary" type="submit">ورود و ادامه ذخیره</button><button class="btn btn--ghost" type="button" data-recovery-cancel>فعلاً ادامه نمی‌دهم</button></form>';
    document.body.append(dialog);const login=dialog.querySelector('form'),status=dialog.querySelector('[data-recovery-status]'),submit=login.querySelector('[type=submit]');
    const phone=form.dataset.loginIdentifier || document.querySelector('[data-member-form]')?.dataset.loginIdentifier;if(phone)login.elements.identifier.value=phone;
    const finish=(value,failure)=>{dialog.remove();recovery=null;failure?reject(failure):resolve(value);};
    const cancel=()=>finish(null,error('ذخیره انجام نشد. پاسخ‌ها محفوظ‌اند؛ هر زمان خواستید دوباره ذخیره کنید.'));
    dialog.addEventListener('cancel',event=>{event.preventDefault();cancel();});dialog.querySelector('[data-recovery-cancel]').addEventListener('click',cancel);
    login.addEventListener('submit',async event=>{
      event.preventDefault();submit.disabled=true;status.textContent='در حال ورود…';
      try{
        state=await session();const data=new URLSearchParams(new FormData(login));data.set('_token',state.csrf_token);data.set('recover','1');
        const next=form.querySelector('[name=next]')?.value;if(next)data.set('next',next);
        const result=await parse(await request('/login',{method:'POST',body:data}));login.elements.password.value='';
        if(expected && result.user_id!==expected){status.textContent='این حساب با صاحب فرم متفاوت است؛ با حساب قبلی وارد شوید.';return;}
        const renewed=await session();setToken(renewed.csrf_token);finish(renewed);
      }catch(e){status.textContent=e.message;}
      finally{submit.disabled=false;}
    });
    if(typeof dialog.showModal==='function')dialog.showModal();else{dialog.setAttribute('open','');dialog.setAttribute('aria-modal','true');dialog.classList.add('is-fallback');}login.elements.identifier.focus();
  });return recovery;
}
export async function sendProfile(form,data){
  const expected=actor(form),requiresLogin=!!expected || form.matches('[data-member-form],[data-async-save],[data-community-toggle]');
  let state=await session();
  if(requiresLogin && (!state.authenticated || (expected && state.user_id!==expected)))state=await recover(form,state,expected);
  setToken(state.csrf_token);data.set('_token',state.csrf_token);
  const post=async()=>{const result=await parse(await request(form.action,{method:'POST',body:encoded(data)},45000));if(typeof result.message!=='string')throw error('نتیجه ذخیره مشخص نیست؛ پاسخ‌های شما باقی مانده است.',200,{simpleSave:true});return result;};
  try{return await post();}
  catch(e){
    if(![401,419].includes(e.status))throw e;
    state=await session();
    if(requiresLogin && (!state.authenticated || (expected && state.user_id!==expected)))state=await recover(form,state,expected);
    setToken(state.csrf_token);data.set('_token',state.csrf_token);
    return post();
  }
}
export function simpleProfileSave(form,mode){
  form.querySelector('[data-simple-mode]')?.remove();
  if(mode){const input=document.createElement('input');input.type='hidden';input.name='mode';input.value=mode;input.dataset.simpleMode='';form.append(input);}
  HTMLFormElement.prototype.submit.call(form);
}

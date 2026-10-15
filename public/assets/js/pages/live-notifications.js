import { Toast } from '../components/toast.js?v=2.0.0';
export function initLiveNotifications(){
 const actor=document.querySelector('meta[name=account-id]')?.content;if(!actor)return;
 const key='mentoris-notifications.v1:'+actor;let known=new Set(),initialized=false,busy=false,stopped=false,last=0,delay=30000,timer;
 try{const saved=JSON.parse(sessionStorage.getItem(key)||'null');if(Array.isArray(saved)){known=new Set(saved);initialized=true;}}catch{}
 const poll=async()=>{
  if(stopped)return;if(document.hidden || busy || Date.now()-last<10000){clearTimeout(timer);timer=setTimeout(poll,delay);return;}busy=true;last=Date.now();
  const abort=new AbortController(),timeout=setTimeout(()=>abort.abort(),10000);
  try{const response=await fetch('/notifications/feed',{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'},signal:abort.signal});if(response.status===401){stopped=true;return;}if(!response.ok)throw Error();const data=await response.json();if(data.user_id!==actor){stopped=true;return;}if(!Array.isArray(data.items))throw Error();
   document.querySelectorAll('.navbar-notifications').forEach(bell=>{let badge=bell.querySelector('b');if(data.unread>0){if(!badge){badge=document.createElement('b');bell.append(badge);}badge.textContent=data.unread>99?'99+':String(data.unread);}else badge?.remove();bell.setAttribute('aria-label','اعلان‌ها، '+data.unread+' خوانده‌نشده');});
   document.querySelectorAll('.admin-notification-strip a[href="/notifications"]').forEach(link=>{let badge=link.querySelector('strong');if(data.unread>0){if(!badge){badge=document.createElement('strong');link.append(badge);}badge.textContent=(data.unread>99?'99+':String(data.unread))+' خوانده‌نشده';}else badge?.remove();});
   const fresh=data.items.filter(item=>!known.has(item.id));if(initialized && fresh.length){const item=fresh[0];Toast.show(item.message.slice(0,220),{title:item.title,type:'info',duration:9000});}
   data.items.forEach(item=>known.add(item.id));known=new Set([...known].slice(-100));initialized=true;try{sessionStorage.setItem(key,JSON.stringify([...known]));}catch{}delay=30000;
  }catch{delay=Math.min(delay*2,300000);}finally{clearTimeout(timeout);busy=false;clearTimeout(timer);if(!stopped)timer=setTimeout(poll,delay);}
 };
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)poll();});window.addEventListener('focus',poll);window.addEventListener('pageshow',poll);window.addEventListener('pagehide',()=>clearTimeout(timer));poll();
}

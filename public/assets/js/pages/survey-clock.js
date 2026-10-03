export function initSurveyClocks() {
  const dates=new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short',timeZone:'Asia/Tehran'});
  document.querySelectorAll('[data-local-date]').forEach(node=>{ const date=new Date(node.dataset.localDate); if(!Number.isNaN(date.getTime())) node.textContent=dates.format(date); });
  document.querySelectorAll('[data-survey-countdown]').forEach(node=>{
    const end=Number(node.dataset.surveyCountdown)*1000;
    let timer;
    const update=()=>{
      const seconds=Math.max(0,Math.floor((end-Date.now())/1000)); const days=Math.floor(seconds/86400),hours=Math.floor(seconds%86400/3600),minutes=Math.floor(seconds%3600/60);
      const label=seconds>0 ? `زمان باقی‌مانده: ${days ? days+' روز و ' : ''}${hours} ساعت و ${minutes} دقیقه` : 'مهلت ثبت نظر پایان یافت.';
      if(node.textContent!==label) node.textContent=label;
      if(!seconds) { const form=node.closest('form'); if(form) { form.dataset.surveyClosed='true'; form.querySelectorAll('button[type=submit]').forEach(button=>button.disabled=true); } clearInterval(timer); }
    };
    const start=()=>{ clearInterval(timer); timer=setInterval(update,1000); update(); };
    start(); window.addEventListener('pagehide',()=>clearInterval(timer)); window.addEventListener('pageshow',start);
  });
}

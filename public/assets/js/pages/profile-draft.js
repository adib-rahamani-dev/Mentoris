const prefix='mentoris.profile-draft.v2:';
const key=form=>prefix+(form.dataset.stateKey || document.querySelector('meta[name=account-id]')?.content || 'guest')+':'+new URL(form.action).pathname;
export function storeProfileDraft(form){
  if(!form.matches('[data-member-form],[data-async-save]'))return;
  const values=[...new FormData(form)].filter(([name,value])=>typeof value==='string' && !['_token','next','mode'].includes(name) && !name.includes('password'));
  const checks=[...form.querySelectorAll('input[type=checkbox]')].map(input=>[input.name,input.checked]);
  try{sessionStorage.setItem(key(form),JSON.stringify({at:Date.now(),values,checks}));}catch{}
}
export function clearProfileDraft(form){try{sessionStorage.removeItem(key(form));}catch{}}
export function restoreProfileDrafts(){
  document.querySelectorAll('[data-member-form],[data-async-save]').forEach(form=>{
    if(form.querySelector('fieldset[disabled]'))return;
    let draft;try{draft=JSON.parse(sessionStorage.getItem(key(form)) || 'null');}catch{}
    if(!draft)return;
    if(!Array.isArray(draft.values) || draft.values.length>300 || draft.values.some(entry=>!Array.isArray(entry) || typeof entry[0]!=='string' || typeof entry[1]!=='string') || !Number.isFinite(draft.at) || draft.at<Date.now()-86400000 || new URLSearchParams(location.search).has('saved')){clearProfileDraft(form);return;}
    const list=form.querySelector('[data-course-list]'),template=form.querySelector('[data-course-template]');
    if(list && template){
      const indexes=[...new Set(draft.values.map(([name])=>name.match(/^training_courses\[(\d{1,4})\]/)?.[1]).filter(Boolean))].slice(0,30);
      list.replaceChildren();indexes.forEach(index=>{const fragment=template.content.cloneNode(true);fragment.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace('__INDEX__',index));list.append(fragment);});
    }
    draft.values.forEach(([name,value])=>{const input=form.elements.namedItem(name);if(typeof value==='string' && input && input.type!=='file' && input.type!=='password' && input.type!=='hidden')input.value=value;});
    if(Array.isArray(draft.checks))draft.checks.filter(entry=>Array.isArray(entry) && typeof entry[0]==='string').forEach(([name,checked])=>{const input=form.elements.namedItem(name);if(input?.type==='checkbox')input.checked=!!checked;});
    form.dataset.draftRestored='true';
  });
}

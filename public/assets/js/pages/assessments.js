import { scoreAssessment } from './assessment-score.js?v=1.0.0';

const format = value => new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 2 }).format(value);
function element(tag, text, className) {
  const node = document.createElement(tag);
  if (text !== undefined) node.textContent = text;
  if (className) node.className = className;
  return node;
}
export function initAssessments() {
  document.querySelectorAll('[data-assessment]').forEach(root => {
    const tool = JSON.parse(root.querySelector('[data-assessment-definition]').textContent);
    const key = `mentoris.assessment.${root.dataset.assessment}.${tool.version}`;
    const questions = [...root.querySelectorAll('[data-question]')];
    const count = questions.length;
    let answers = Array(count).fill(null), step = 0, complete = false;
    const status = root.querySelector('[data-local-status]');
    try {
      const stored = JSON.parse(sessionStorage.getItem(key) || 'null');
      if (stored && Array.isArray(stored.answers) && stored.answers.length === count && stored.answers.every(value => value === null || (Number.isInteger(value) && value >= 1 && value <= tool.labels.length))) {
        answers = stored.answers;
        step = Number.isInteger(stored.step) ? Math.max(0, Math.min(count - 1, stored.step)) : 0;
        complete = stored.complete === true && answers.every(Number.isInteger);
      }
    } catch { status.textContent = 'حافظهٔ موقت مرورگر در دسترس نیست؛ پیش از بستن صفحه گزارش را ذخیره کنید.'; }
    const save = () => {
      try { sessionStorage.setItem(key, JSON.stringify({ answers, step, complete })); }
      catch { status.textContent = 'پاسخ‌ها در این صفحه موجودند؛ مرورگر اجازهٔ حفظ آن‌ها پس از تازه‌کردن را نمی‌دهد.'; }
    };
    const error = root.querySelector('[data-assessment-error]');
    const result = root.querySelector('[data-assessment-results]');
    const progress = root.querySelector('[data-assessment-progress]');
    const navigation = root.querySelector('[data-assessment-navigation]');
    const actions = root.querySelector('[data-result-actions]');
    const back = root.querySelector('[data-assessment-back]');
    const next = root.querySelector('[data-assessment-next]');
    const questionContainer = root.querySelector('.assessment-questions');
    questions.forEach((question, i) => question.querySelectorAll('input').forEach(input => { input.checked = Number(input.value) === answers[i]; }));
    function showStep(focus = false) {
      complete = false;
      result.hidden = true; actions.hidden = true; navigation.hidden = false; questionContainer.hidden = false; progress.hidden = false;
      questions.forEach((question, i) => { question.hidden = i !== step; });
      root.querySelector('[data-step-label]').textContent = `سؤال ${format(step + 1)} از ${format(count)}`;
      root.querySelector('[data-answered-label]').textContent = `${format(answers.filter(Number.isInteger).length)} پاسخ`;
      progress.querySelector('progress').value = answers.filter(Number.isInteger).length;
      back.disabled = step === 0;
      next.textContent = step === count - 1 ? 'دیدن نتیجهٔ من' : 'سؤال بعد ←';
      error.textContent = '';
      if (focus) {
        const legend=questions[step].querySelector('legend'); legend.focus({ preventScroll:true });
        const bounds=legend.getBoundingClientRect();
        if(bounds.top<90 || bounds.bottom>innerHeight-60) questions[step].scrollIntoView({block:'start',behavior:'auto'});
      }
      save();
    }
    function metric(name, metric, description, direction) {
      const card = element('article', undefined, 'assessment-metric');
      card.append(element('h3', name), element('p', description));
      const value = element('div', undefined, 'assessment-metric__value');
      value.append(element('strong', format(metric.mean)), element('span', ` میانگین از ${format(tool.labels.length)}`));
      const meter = element('meter'); meter.min = 1; meter.max = tool.labels.length; meter.value = metric.mean;
      meter.setAttribute('aria-label', `${name}: ${format(metric.mean)} از ${format(tool.labels.length)}`);
      card.append(value, meter, element('p', `جمع: ${format(metric.sum)} · دامنه: ${format(metric.min)} تا ${format(metric.max)}`, 'assessment-metric__range'));
      if (direction) card.append(element('small', direction === 'negative' ? 'نمرهٔ بیشتر = گزارش بیشتر این تجربهٔ نامطلوب' : 'نمرهٔ بیشتر = گزارش بیشتر این بُعد مثبت'));
      return card;
    }
    function showResult(focus = true) {
      let scored;
      try { scored = scoreAssessment(tool, answers); } catch (exception) {
        const missing = answers.findIndex(value => !Number.isInteger(value)); step = missing < 0 ? 0 : missing;
        showStep(true); error.textContent = exception.message; return;
      }
      complete = true;
      questionContainer.hidden = true; navigation.hidden = true; progress.hidden = true; actions.hidden = false; result.hidden = false;
      result.replaceChildren(element('span', 'گزارش خودارزیابی شما', 'eyebrow'), element('h2', 'یک تصویر روشن‌تر از پاسخ‌هایتان'), element('p', 'این گزارش توصیفی است؛ نمره‌ها رتبهٔ شما نسبت به دیگران یا تشخیص بالینی نیستند.'));
      if (scored.overall) result.append(metric('نمای کلی', scored.overall, 'پس از اعمال روش نمره‌گذاری همین نسخه.', 'positive'));
      const grid = element('div', undefined, 'assessment-metrics');
      scored.dimensions.forEach(dimension => grid.append(metric(dimension.name, dimension, dimension.description, dimension.direction)));
      result.append(grid, element('h3', 'برای ادامهٔ تأمل'), element('p', 'یک بُعد را که برایتان مهم‌تر است انتخاب کنید. پیشنهادهای زیر تمرین‌های عمومی‌اند و از این نمره‌ها نسخهٔ درمانی ساخته نمی‌شود.'));
      const ideas = element('ul'); scored.dimensions.forEach(dimension => ideas.append(element('li', `${dimension.name}: ${dimension.action}`))); result.append(ideas);
      result.append(element('h3', 'نسخه و روش محاسبه'), element('p', tool.notice), element('p', tool.scoring));
      const review = element('details'); review.append(element('summary', 'مرور همهٔ پاسخ‌ها'));
      const list = element('ol'); tool.questions.forEach((question, i) => list.append(element('li', `${question} — ${tool.labels[answers[i] - 1]}`))); review.append(list); result.append(review);
      if (focus) { result.focus({ preventScroll: true }); result.scrollIntoView({ block: 'start', behavior: 'auto' }); }
      error.textContent = ''; save();
    }
    root.addEventListener('change', event => {
      const question = event.target.closest('[data-question]');
      if (!question || event.target.type !== 'radio') return;
      answers[Number(question.dataset.question)] = Number(event.target.value);
      status.textContent = '';
      progress.querySelector('progress').value = answers.filter(Number.isInteger).length;
      root.querySelector('[data-answered-label]').textContent = `${format(answers.filter(Number.isInteger).length)} پاسخ`;
      error.textContent = ''; save();
    });
    next.addEventListener('click', () => {
      if (!Number.isInteger(answers[step])) { error.textContent = 'برای ادامه، یکی از گزینه‌ها را انتخاب کنید.'; questions[step].querySelector('input').focus(); return; }
      if (step === count - 1) showResult();
      else { step++; showStep(true); }
    });
    back.addEventListener('click', () => { if (step > 0) { step--; showStep(true); } });
    root.querySelector('[data-assessment-review]').addEventListener('click', () => { step = 0; showStep(true); root.scrollIntoView({ block: 'start' }); });
    root.querySelector('[data-reset-controls]').hidden = false;
    const confirm = root.querySelector('[data-reset-confirm]');
    root.querySelector('[data-assessment-reset]').addEventListener('click', () => { confirm.hidden = false; root.querySelector('[data-confirm-reset]').focus(); });
    root.querySelector('[data-cancel-reset]').addEventListener('click', () => { confirm.hidden = true; root.querySelector('[data-assessment-reset]').focus(); });
    root.querySelector('[data-confirm-reset]').addEventListener('click', () => {
      try { sessionStorage.removeItem(key); } catch {}
      answers = Array(count).fill(null); step = 0; complete = false;
      root.querySelectorAll('input').forEach(input => { input.checked = false; }); confirm.hidden = true; showStep(true);
      try { sessionStorage.removeItem(key); } catch {}
      status.textContent = 'پاسخ‌های این پرسشنامه پاک شدند.';
    });
    function reportText() {
      const scored = scoreAssessment(tool, answers);
      const lines = [`منتوریس | ${tool.title}`, '', 'خودارزیابی آموزشی؛ این گزارش تشخیص بالینی نیست.', ''];
      if (scored.overall) lines.push(`نمای کلی: ${format(scored.overall.sum)} | میانگین ${format(scored.overall.mean)}`);
      scored.dimensions.forEach(dimension => lines.push(`${dimension.name}: جمع ${format(dimension.sum)}، میانگین ${format(dimension.mean)}، دامنه ${format(dimension.min)} تا ${format(dimension.max)}`));
      lines.push('', tool.scoring, '', tool.notice, '', 'پاسخ‌های شما');
      tool.questions.forEach((question, i) => lines.push(`${i + 1}. ${question}\n${tool.labels[answers[i] - 1]}`));
      lines.push('', `https://mentorisacademy.com/resources/${root.dataset.assessment}`);
      return lines.join('\n');
    }
    root.querySelector('[data-assessment-download]').addEventListener('click', () => {
      const url = URL.createObjectURL(new Blob(['\uFEFF', reportText()], { type: 'text/plain;charset=utf-8' }));
      const link = element('a'); link.href = url; link.download = `mentoris-${root.dataset.assessment}-report.txt`; link.click(); setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
    root.querySelector('[data-assessment-print]').addEventListener('click', () => {
      document.body.classList.add('printing-assessment');
      const details = [...result.querySelectorAll('details')]; const opened = details.map(item => item.open); details.forEach(item => { item.open = true; });
      const restore = () => { document.body.classList.remove('printing-assessment'); details.forEach((item, i) => { item.open = opened[i]; }); };
      window.addEventListener('afterprint', restore, { once: true });
      try { window.print(); } catch { restore(); }
    });
    if (complete) showResult(false); else showStep();
  });
}

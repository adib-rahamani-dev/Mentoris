import qrcode from './vendor/qrcode.js';

const studio = document.querySelector('[data-qr-studio]');
if (studio) {
  const form = studio.querySelector('[data-qr-form]');
  const codeSlot = studio.querySelector('[data-qr-code]');
  const error = studio.querySelector('[data-qr-error]');
  const palettes = {
    forest: { ink: '#173e36', paper: '#ffffff', accent: '#d8b77b' },
    ink: { ink: '#1d2b40', paper: '#ffffff', accent: '#8aaac5' },
    plum: { ink: '#432943', paper: '#ffffff', accent: '#cf9ca3' },
  };
  let currentSvg = '';
  let currentUrl = '';

  function makeSvg(url, ink, paper) {
    const qr = qrcode(0, 'H');
    qr.addData(url);
    qr.make();
    const count = qr.getModuleCount();
    const size = count + 8;
    const rects = [`<rect width="${size}" height="${size}" fill="${paper}"/>`];
    for (let row = 0; row < count; row++) {
      for (let col = 0; col < count; col++) {
        if (qr.isDark(row, col)) rects.push(`<rect x="${col + 4}" y="${row + 4}" width="1" height="1"/>`);
      }
    }
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${size} ${size}" width="640" height="640" shape-rendering="crispEdges" role="img" aria-label="QR code"><g fill="${ink}">${rects.join('')}</g></svg>`;
  }

  function normalizeUrl(raw) {
    const origin = new URL(studio.dataset.siteOrigin);
    const value = raw.trim();
    if (!value || value.startsWith('//') || value.includes('\\')) throw new Error('آدرس معتبر نیست.');
    const parsed = new URL(value, origin);
    if (parsed.origin !== origin.origin || !['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password) {
      throw new Error('فقط آدرس‌های همین سایت پذیرفته می‌شود.');
    }
    if (parsed.hash) throw new Error('بخش پس از # را از آدرس حذف کنید.');
    return parsed.href;
  }

  function generate(event) {
    if (event) event.preventDefault();
    error.hidden = true;
    try {
      const data = new FormData(form);
      const url = normalizeUrl(String(data.get('path') || ''));
      const title = String(data.get('title') || '').trim();
      if (!title) throw new Error('عنوان کارت را وارد کنید.');
      const paletteName = String(data.get('palette') || 'forest');
      const palette = palettes[paletteName] || palettes.forest;
      currentSvg = makeSvg(url, palette.ink, palette.paper);
      currentUrl = url;
      codeSlot.innerHTML = currentSvg;
      studio.querySelector('[data-qr-title]').textContent = title;
      studio.querySelector('[data-qr-url]').textContent = url;
      studio.querySelector('[data-qr-poster]').style.setProperty('--qr-ink', palette.ink);
      studio.querySelector('[data-qr-poster]').style.setProperty('--qr-accent', palette.accent);
      studio.querySelectorAll('[data-qr-print],[data-qr-download],[data-qr-copy]').forEach(button => button.disabled = false);
    } catch (caught) {
      currentSvg = '';
      currentUrl = '';
      studio.querySelectorAll('[data-qr-print],[data-qr-download],[data-qr-copy]').forEach(button => button.disabled = true);
      error.textContent = caught instanceof Error ? caught.message : 'ساخت QR ممکن نشد.';
      error.hidden = false;
    }
  }

  form.addEventListener('submit', generate);
  studio.querySelectorAll('[data-qr-event]').forEach(button => button.addEventListener('click', () => {
    form.elements.namedItem('path').value = '/feedback?event=' + button.dataset.qrEvent;
    form.elements.namedItem('title').value = button.dataset.qrEvent === 'therapists-circle-tabriz' ? 'بازخورد همایش اول منتوریس' : 'بازخورد همایش دوم منتوریس';
    generate();
  }));
  studio.querySelector('[data-qr-print]').addEventListener('click', () => { if (currentSvg) window.print(); });
  studio.querySelector('[data-qr-download]').addEventListener('click', () => {
    if (!currentSvg) return;
    const blobUrl = URL.createObjectURL(new Blob([currentSvg], { type: 'image/svg+xml' }));
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = 'mentoris-qr.svg';
    link.click();
    setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
  });
  studio.querySelector('[data-qr-copy]').addEventListener('click', async () => {
    if (!currentUrl) return;
    try { await navigator.clipboard.writeText(currentUrl); }
    catch { error.textContent = 'کپی خودکار در این مرورگر در دسترس نیست. آدرس زیر QR را انتخاب و کپی کنید.'; error.hidden = false; }
  });
  generate();
}

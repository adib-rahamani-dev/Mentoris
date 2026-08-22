export class Theme {
  static key = 'mentoris-theme';

  static init() {
    const button = document.querySelector('[data-theme-toggle]');
    if (!button) return null;
    const apply = (theme, persist = true) => {
      document.documentElement.dataset.theme = theme;
      button.setAttribute('aria-label', theme === 'dark' ? 'فعال‌کردن حالت روشن' : 'فعال‌کردن حالت تیره');
      button.setAttribute('aria-pressed', String(theme === 'light'));
      document.querySelector('meta[name="theme-color"]')?.setAttribute('content', theme === 'dark' ? '#050914' : '#f6f7fb');
      if (persist) localStorage.setItem(Theme.key, theme);
    };
    button.addEventListener('click', () => apply(document.documentElement.dataset.theme === 'light' ? 'dark' : 'light'));
    apply(document.documentElement.dataset.theme || 'dark', false);
    return { apply };
  }
}

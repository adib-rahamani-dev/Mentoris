export function initContentSeoPreview() {
  const panel = document.querySelector('[data-content-seo-preview]');
  const form = panel?.closest('form');
  if (!panel || !form) return;

  const field = (name) => form.elements.namedItem(name);
  const target = (name) => panel.querySelector(`[data-seo-${name}]`);
  const render = () => {
    const isArticle = field('entity_type')?.value === 'article';
    panel.hidden = !isArticle;
    if (!isArticle) return;
    const title = field('fa_title')?.value.trim() || 'عنوان مقاله';
    const description = field('fa_excerpt')?.value.trim() || 'خلاصه‌ای روشن از چیزی که خواننده در مقاله یاد می‌گیرد.';
    const body = field('fa_body')?.value.trim() || '';
    const slug = field('slug')?.value.trim() || 'article-slug';
    const image = field('image')?.value.trim() || field('image_upload')?.files?.length;
    target('url').textContent = `mentorisacademy.com/articles/${slug}`;
    target('title').textContent = `${title} | Mentoris`;
    target('description').textContent = description;
    target('title-count').textContent = `عنوان: ${title === 'عنوان مقاله' ? 0 : [...title].length} نویسه`;
    target('description-count').textContent = `خلاصه: ${field('fa_excerpt')?.value.trim().length || 0} نویسه`;
    target('body-count').textContent = `متن: ${body ? body.split(/\s+/u).length : 0} واژه`;
    target('image-check').textContent = image ? 'تصویر: آماده' : 'تصویر: پیشنهاد می‌شود';
  };
  form.addEventListener('input', render);
  form.addEventListener('change', render);
  render();
}

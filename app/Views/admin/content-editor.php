<?php
$isEdit = is_array($entry);
$values = static function (string $locale, string $field) use ($old, $entry): string {
    if (isset($old['translations'][$locale][$field])) return (string) $old['translations'][$locale][$field];
    $value = $entry['translations'][$locale][$field] ?? '';
    if ($field === 'metadata' && is_array($value)) return $value ? (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
    return (string) $value;
};
$base = static fn (string $field, mixed $default = ''): mixed => $old[$field] ?? ($entry[$field] ?? $default);
$commonMetadata = $old['translations']['fa']['metadata'] ?? ($entry['translations']['fa']['metadata'] ?? []);
$commonMetadata = is_array($commonMetadata) ? $commonMetadata : [];
$meta = static fn (string $field, mixed $default = ''): mixed => $commonMetadata[$field] ?? $default;
$metaList = static function (string $field) use ($meta): string {
    $value = $meta($field, []);
    return is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
};
$metaTextList = static function (string $field) use ($meta): string {
    $value = $meta($field, []);
    return is_array($value) ? implode("\n", array_map('strval', $value)) : (string) $value;
};
$languageLabels = ['fa'=>'فارسی','ar'=>'عربی','ku'=>'کوردی','en'=>'English'];
?>
<header class="admin-page-head"><div><a class="admin-back" href="/admin/content">→ بازگشت به محتوا</a><span class="eyebrow">Editorial workspace</span><h1><?= $isEdit ? 'ویرایش محتوا' : 'ساخت محتوای جدید' ?></h1><p>اطلاعات پایه و نسخه‌های زبانی را تکمیل کنید؛ فیلد متادیتا برای مشخصات ساختاریافته و اختیاری است.</p></div><span class="content-status content-status--<?= e((string) $base('status', 'draft')) ?>"><?= e((string) $base('status', 'draft')) ?></span></header>
<?php if (isset($_GET['saved'])): ?><div class="alert alert--success" role="status">محتوا ذخیره شد و تغییر در گزارش فعالیت ثبت گردید.</div><?php endif; ?>
<?php if (isset($_GET['status_updated'])): ?><div class="alert alert--success" role="status">وضعیت انتشار با موفقیت تغییر کرد.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert--danger" role="alert"><strong>ذخیره انجام نشد.</strong><ul><?php foreach ($errors as $messages): foreach ($messages as $message): ?><li><?= e($message) ?></li><?php endforeach; endforeach; ?></ul></div><?php endif; ?>

<form class="content-editor" method="post" enctype="multipart/form-data" action="<?= $isEdit ? '/admin/content/' . e($entry['id']) : '/admin/content' ?>">
    <?= csrf_field() ?>
    <section class="admin-panel content-editor__settings"><header><h2>تنظیمات انتشار</h2></header><div class="content-settings-grid">
        <label class="form-group"><span class="form-label">نوع محتوا</span><select class="form-select" name="entity_type" required><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>" <?= $base('entity_type') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <label class="form-group"><span class="form-label">نامک انگلیسی</span><input class="form-control ltr" name="slug" value="<?= e((string) $base('slug')) ?>" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" maxlength="190" placeholder="therapists-circle" required></label>
        <label class="form-group"><span class="form-label">وضعیت</span><select class="form-select" name="status"><option value="draft" <?= $base('status', 'draft') === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option><option value="published" <?= $base('status') === 'published' ? 'selected' : '' ?>>منتشرشده</option><option value="archived" <?= $base('status') === 'archived' ? 'selected' : '' ?>>بایگانی</option></select></label>
        <label class="form-group"><span class="form-label">ترتیب نمایش</span><input class="form-control ltr" type="number" name="sort_order" value="<?= e((string) $base('sort_order', 0)) ?>" min="-9999" max="9999"></label>
    </div></section>

    <section class="admin-panel content-editor__settings"><header><div><h2>مشخصات کاربردی</h2><p>این فیلدها بدون نیاز به JSON در کارت، صفحه جزئیات و SEO استفاده می‌شوند.</p></div></header><div class="content-settings-grid">
        <label class="form-group"><span class="form-label">تصویر محتوا یا مدرس</span><input class="form-control" type="file" name="image_upload" accept="image/jpeg,image/png,image/webp"><small class="form-hint">JPEG، PNG یا WebP واقعی؛ حداکثر ۵ مگابایت.</small></label>
        <label class="form-group"><span class="form-label">یا مسیر تصویر موجود</span><input class="form-control ltr" name="image" value="<?= e((string)$meta('image')) ?>" maxlength="255" placeholder="images/example.jpg"><small class="form-hint">مسیر داخل public/assets/images؛ آپلود جدید اولویت دارد.</small></label>
        <label class="form-group"><span class="form-label">دسته‌بندی</span><input class="form-control" name="category" value="<?= e((string)$meta('category')) ?>" maxlength="120" placeholder="پژوهش، کارگاه، روان‌درمانی..."></label>
        <label class="form-group"><span class="form-label">مدت</span><input class="form-control" name="duration" value="<?= e((string)$meta('duration')) ?>" maxlength="80" placeholder="۱۲ ساعت"></label>
        <label class="form-group"><span class="form-label">زمان‌بندی</span><input class="form-control" name="schedule" value="<?= e((string)$meta('schedule')) ?>" maxlength="120" placeholder="پنجشنبه‌ها ۱۸ تا ۲۰"></label>
        <label class="form-group"><span class="form-label">شیوه اجرا</span><input class="form-control" name="format" value="<?= e((string)$meta('format')) ?>" maxlength="80" placeholder="حضوری / آنلاین / ترکیبی"></label>
        <label class="form-group"><span class="form-label">مکان</span><input class="form-control" name="location" value="<?= e((string)$meta('location')) ?>" maxlength="190" placeholder="تبریز یا آنلاین"></label>
        <label class="form-group"><span class="form-label">شروع</span><input class="form-control ltr" type="datetime-local" name="starts_at" value="<?= e(str_replace(' ', 'T', substr((string)$meta('starts_at'),0,16))) ?>"></label>
        <label class="form-group"><span class="form-label">پایان</span><input class="form-control ltr" type="datetime-local" name="ends_at" value="<?= e(str_replace(' ', 'T', substr((string)$meta('ends_at'),0,16))) ?>"></label>
        <label class="form-group"><span class="form-label">قیمت به تومان</span><input class="form-control ltr" type="number" name="price_amount" value="<?= e((string)$meta('price_amount',0)) ?>" min="0" step="1000"></label>
        <label class="form-group"><span class="form-label">ظرفیت</span><input class="form-control ltr" type="number" name="capacity" value="<?= e((string)$meta('capacity',0)) ?>" min="0" max="100000"></label>
        <label class="form-group"><span class="form-label">لاین آکادمی</span><input class="form-control ltr" name="line_slug" value="<?= e((string)$meta('line_slug')) ?>" maxlength="190" placeholder="therapist-development"><small class="form-hint">برای برنامه، دوره یا رویداد.</small></label>
        <label class="form-group"><span class="form-label">نامک مدرس</span><input class="form-control ltr" name="instructor_slug" value="<?= e((string)$meta('instructor_slug')) ?>" maxlength="190" placeholder="maryam-haghani"><small class="form-hint">نامک پروفایل عمومی مدرس.</small></label>
        <label class="form-group"><span class="form-label">وضعیت دوره/رویداد</span><select class="form-select ltr" name="content_status"><option value="">بدون وضعیت عملیاتی</option><?php $operationalStatus=(string)($meta('course_status') ?: $meta('event_status')); foreach(['active'=>'Course: Active','coming-soon'=>'Course: Coming Soon','upcoming'=>'Event: Upcoming','registration-open'=>'Event: Registration Open','full'=>'Full','completed'=>'Completed','canceled'=>'Canceled'] as $key=>$label): ?><option value="<?=e($key)?>" <?=$operationalStatus===$key?'selected':''?>><?=e($label)?></option><?php endforeach; ?></select></label>
        <label class="form-group"><span class="form-label">سطح</span><input class="form-control" name="level" value="<?= e((string)$meta('level')) ?>" maxlength="80" placeholder="مقدماتی / حرفه‌ای"></label>
        <label class="form-group"><span class="form-label">زمان مطالعه</span><input class="form-control" name="read_time" value="<?= e((string)$meta('read_time')) ?>" maxlength="80" placeholder="۵ دقیقه"></label>
        <label class="form-group"><span class="form-label">نام نویسنده</span><input class="form-control" name="author" value="<?= e((string)$meta('author')) ?>" maxlength="120" placeholder="آکادمی منتوریس"></label>
        <label class="form-group"><span class="form-label">رنگ بصری</span><select class="form-select ltr" name="tone"><?php foreach([''=>'پیش‌فرض','sage'=>'Sage','teal'=>'Teal','blue'=>'Blue','violet'=>'Violet','amber'=>'Amber','rose'=>'Rose','indigo'=>'Indigo'] as $key=>$label): ?><option value="<?=e($key)?>" <?=$meta('tone')===$key?'selected':''?>><?=e($label)?></option><?php endforeach; ?></select></label>
        <label class="form-group"><span class="form-label">آیکون</span><select class="form-select ltr" name="icon"><?php foreach([''=>'پیش‌فرض','brain'=>'Brain','book'=>'Book','users'=>'Users','search'=>'Search','heart'=>'Heart','activity'=>'Activity','shield'=>'Shield','trending'=>'Trending','certificate'=>'Certificate'] as $key=>$label): ?><option value="<?=e($key)?>" <?=$meta('icon')===$key?'selected':''?>><?=e($label)?></option><?php endforeach; ?></select></label>
        <label class="form-group content-fields__wide"><span class="form-label">وعده/پیام لاین</span><input class="form-control" name="promise" value="<?=e((string)$meta('promise'))?>" maxlength="500"></label>
        <label class="form-group content-fields__wide"><span class="form-label">لینک ثبت‌نام</span><input class="form-control ltr" name="registration_url" value="<?= e((string)$meta('registration_url')) ?>" maxlength="500" placeholder="https://... یا /contact"><small class="form-hint">فقط HTTPS یا مسیر داخلی سایت پذیرفته می‌شود.</small></label>
        <label class="form-group"><span class="form-label">دوره‌های مرتبط</span><input class="form-control ltr" name="related_courses" value="<?= e($metaList('related_courses')) ?>" placeholder="course-one, course-two"></label>
        <label class="form-group"><span class="form-label">رویدادهای مرتبط</span><input class="form-control ltr" name="related_events" value="<?= e($metaList('related_events')) ?>" placeholder="event-one, event-two"></label>
        <label class="form-group"><span class="form-label">مدرسان مرتبط</span><input class="form-control ltr" name="related_mentors" value="<?= e($metaList('related_mentors')) ?>" placeholder="maryam-haghani"></label>
        <label class="form-group"><span class="form-label">مخاطبان برنامه</span><textarea class="form-textarea" name="target_audience" rows="4" placeholder="هر مورد در یک خط"><?=e($metaTextList('target_audience'))?></textarea></label>
        <label class="form-group"><span class="form-label">اهداف برنامه</span><textarea class="form-textarea" name="objectives" rows="4" placeholder="هر هدف در یک خط"><?=e($metaTextList('objectives'))?></textarea></label>
        <label class="form-group"><span class="form-label">مخاطبان دوره</span><textarea class="form-textarea" name="audience" rows="4" placeholder="هر مورد در یک خط"><?=e($metaTextList('audience'))?></textarea></label>
        <label class="form-group"><span class="form-label">نکات برجسته رویداد</span><textarea class="form-textarea" name="highlights" rows="4" placeholder="هر مورد در یک خط"><?=e($metaTextList('highlights'))?></textarea></label>
        <label class="check content-fields__wide"><input type="checkbox" name="featured" value="1" <?= $meta('featured',false) ? 'checked' : '' ?>><span>نمایش به‌عنوان محتوای ویژه</span></label>
    </div></section>

    <div class="tabs content-editor__languages"><nav class="admin-tabs content-language-tabs" role="tablist"><?php foreach ($languageLabels as $locale => $label): ?><button class="<?= $locale === 'fa' ? 'is-active' : '' ?>" type="button" role="tab" aria-selected="<?= $locale === 'fa' ? 'true' : 'false' ?>" aria-controls="content-<?= e($locale) ?>"><?= e($label) ?></button><?php endforeach; ?></nav>
    <?php foreach ($languageLabels as $locale => $label): ?><section class="admin-panel content-language-panel <?= $locale === 'fa' ? 'is-active' : '' ?>" id="content-<?= e($locale) ?>" data-tab-panel role="tabpanel" <?= $locale === 'fa' ? '' : 'hidden' ?> dir="<?= $locale === 'en' ? 'ltr' : 'rtl' ?>"><header><div><span class="eyebrow"><?= e(strtoupper($locale)) ?></span><h2><?= e($label) ?></h2></div><span><?= $locale === 'fa' ? 'الزامی' : 'اختیاری' ?></span></header><div class="content-fields">
        <label class="form-group"><span class="form-label">عنوان</span><input class="form-control" name="<?= e($locale) ?>_title" value="<?= e($values($locale, 'title')) ?>" maxlength="255" <?= $locale === 'fa' ? 'required' : '' ?>></label>
        <label class="form-group"><span class="form-label">زیرعنوان</span><input class="form-control" name="<?= e($locale) ?>_subtitle" value="<?= e($values($locale, 'subtitle')) ?>" maxlength="255"></label>
        <label class="form-group content-fields__wide"><span class="form-label">خلاصه</span><textarea class="form-textarea" name="<?= e($locale) ?>_excerpt" rows="3"><?= e($values($locale, 'excerpt')) ?></textarea></label>
        <label class="form-group content-fields__wide"><span class="form-label">متن کامل</span><textarea class="form-textarea content-body-field" name="<?= e($locale) ?>_body" rows="12"><?= e($values($locale, 'body')) ?></textarea></label>
        <label class="form-group content-fields__wide"><span class="form-label">متادیتا JSON</span><textarea class="form-textarea ltr" name="<?= e($locale) ?>_metadata" rows="5" spellcheck="false" placeholder='{"duration":"12 hours","price":0}'><?= e($values($locale, 'metadata')) ?></textarea><small class="form-hint">برای تاریخ، ظرفیت، قیمت، مدرس یا سایر داده‌های اختصاصی.</small></label>
    </div></section><?php endforeach; ?></div>
    <div class="content-editor__actions"><a class="btn btn--ghost" href="/admin/content">انصراف</a><button class="btn btn--primary btn--lg" type="submit">ذخیره امن محتوا</button></div>
</form>
<?php if($isEdit): $previewBase=['academy_line'=>'/academy/','specialization'=>'/specializations/','program'=>'/programs/','course'=>'/courses/','event'=>'/events/','mentor'=>'/mentors/','article'=>'/articles/'][$entry['entity_type']]??null; ?><section class="admin-panel content-danger-zone"><div><h2>چرخه انتشار</h2><p>بایگانی، محتوا را از سایت عمومی خارج می‌کند اما داده و ترجمه‌ها برای بازیابی حفظ می‌شوند.</p></div><div class="cluster"><?php if($previewBase && $entry['status']==='published'):?><a class="btn btn--ghost" href="<?=e($previewBase.$entry['slug'])?>" target="_blank" rel="noopener">پیش‌نمایش <?=icon('arrow-up-left')?></a><?php endif;?><form method="post" action="/admin/content/<?=e($entry['id'])?>/status"><?=csrf_field()?><input type="hidden" name="status" value="<?=$entry['status']==='archived'?'draft':'archived'?>"><button class="btn <?=$entry['status']==='archived'?'btn--secondary':'btn--ghost'?>" type="submit"><?=$entry['status']==='archived'?'بازگردانی به پیش‌نویس':'بایگانی امن'?></button></form></div></section><?php endif; ?>

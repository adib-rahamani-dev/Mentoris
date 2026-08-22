<?php $lineIndex = array_column($lines, null, 'slug'); ?>
<section class="page-hero programs-hero"><div class="container page-hero__content"><span class="eyebrow">Mentoris Programs</span><h1>مسیرهای یکپارچه برای <span class="text-gradient">رشد حرفه‌ای</span></h1><p>هر Program مجموعه‌ای هدفمند از دوره، رویداد، منتورینگ و پروژه است؛ نه فقط یک کلاس مستقل.</p><div class="programs-legend"><span><strong><?= count($programs) ?></strong> Program</span><span><strong><?= count($lines) ?></strong> Academy Line</span><span><strong>۲۱</strong> Specialization</span></div></div></section>

<section class="section programs-page"><div class="container">
    <div class="program-filters surface" data-reveal>
        <label class="input-group"><span class="sr-only">جستجوی Program</span><span class="input-group__icon">⌕</span><input class="form-control" type="search" placeholder="جستجو در Programها..." data-program-search></label>
        <div class="program-filter-chips" role="group" aria-label="فیلتر بر اساس لاین"><button class="filter-chip is-active" data-program-filter="all">همه لاین‌ها</button><?php foreach ($lines as $line): ?><button class="filter-chip" data-program-filter="<?= e($line['slug']) ?>"><?= e($line['title']) ?></button><?php endforeach; ?></div>
    </div>
    <p class="program-results muted" aria-live="polite"><span data-program-count><?= count($programs) ?></span> Program یافت شد</p>
    <div class="grid grid--3 programs-catalog" data-program-grid>
        <?php foreach ($programs as $program): $program['line_title'] = $lineIndex[$program['line_slug']]['title'] ?? ''; ?>
            <div data-program-item data-program-text="<?= e($program['title'] . ' ' . $program['subtitle'] . ' ' . $program['short_description']) ?>" data-program-category="<?= e($program['line_slug']) ?>"><?php require view_path('components/cards/program-card.php'); ?></div>
        <?php endforeach; ?>
    </div>
    <div class="empty-state" data-program-empty hidden><span>⌕</span><h3>Programی پیدا نشد</h3><p>عبارت جستجو یا لاین دیگری را امتحان کنید.</p></div>
</div></section>

<section class="section section--muted"><div class="container"><div class="community-banner"><div class="stack"><span class="eyebrow">Program vs Course</span><h2>Program چه تفاوتی با یک دوره دارد؟</h2><p>Program یک مسیر چندمرحله‌ای است که دوره‌های مرتبط، تجربه‌های عملی، رویدادها و همراهی منتورها را برای رسیدن به یک خروجی حرفه‌ای مشخص کنار هم می‌چیند.</p><div class="cluster"><a class="btn btn--primary" href="/academy">مشاهده لاین‌ها</a><a class="btn btn--ghost" href="/contact">راهنمای انتخاب مسیر</a></div></div><div class="community-orbit"><span>360°</span><small>یادگیری یکپارچه</small></div></div></div></section>

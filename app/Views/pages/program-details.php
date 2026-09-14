<section class="program-detail-hero program-detail-hero--<?= e($program['tone']) ?>">
    <div class="container program-detail-hero__grid">
        <div class="stack" data-reveal>
            <div class="breadcrumb"><a href="/programs">Programها</a><span><?= icon('arrow-left') ?></span><a href="/academy/<?= e($program['line']['slug']) ?>"><?= e($program['line']['title']) ?></a></div>
            <span class="badge badge--brand"><?= e($program['line']['en']) ?></span>
            <h1><?= e($program['title']) ?></h1>
            <p class="program-detail-subtitle ltr"><?= e($program['subtitle']) ?></p>
            <p class="program-detail-lead"><?= e($program['short_description']) ?></p>
            <div class="program-detail-meta"><span><small>مدت</small><strong><?= e($program['duration']) ?></strong></span><span><small>سطح</small><strong><?= e($program['level']) ?></strong></span><span><small>شیوه اجرا</small><strong><?= e($program['format']) ?></strong></span></div>
        </div>
        <aside class="program-enroll-card" data-reveal><span class="eyebrow">Next Cohort</span><h2>برای دوره بعدی آماده‌اید؟</h2><p>برای دریافت زمان‌بندی، شرایط ورود و گفتگوی راهنما درخواست خود را ثبت کنید.</p><a class="btn btn--primary btn--lg w-full" href="/contact?subject=program&program=<?= e($program['slug']) ?>">درخواست مشاوره</a><small>بدون تعهد به ثبت‌نام · پاسخ طی ۲ روز کاری</small></aside>
    </div>
</section>

<nav class="program-anchor-nav" aria-label="بخش‌های Program"><div class="container"><a href="#overview">معرفی</a><a href="#audience">مخاطبان</a><a href="#objectives">اهداف</a><a href="#courses">دوره‌ها</a><a href="#events">رویدادها</a><a href="#mentors">منتورها</a></div></nav>

<section class="section" id="overview"><div class="container program-overview-grid"><div class="stack" data-reveal><span class="eyebrow">About The Program</span><h2>درباره این Program</h2><p class="program-long-description"><?= e($program['description']) ?></p><blockquote class="program-promise"><?= e($program['line']['promise']) ?></blockquote></div><div class="program-outcome" data-reveal><span><?= icon('trending', 'ui-icon--lg') ?></span><h3>خروجی مسیر</h3><p>در پایان Program، یک نقشه رشد شخصی و یک پروژه عملی قابل ارائه در حوزه تخصصی خود خواهید داشت.</p></div></div></section>

<section class="section section--muted"><div class="container grid grid--2 program-requirements">
    <div id="audience" class="program-list-card" data-reveal><span class="eyebrow">Target Audience</span><h2>این Program برای چه کسانی است؟</h2><?php if ($program['target_audience']): ?><ul class="check-list"><?php foreach ($program['target_audience'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul><?php else: $emptyIcon='users'; require view_path('components/content-empty.php'); endif; ?></div>
    <div id="objectives" class="program-list-card program-list-card--objectives" data-reveal><span class="eyebrow">Objectives</span><h2>در این مسیر چه می‌آموزید؟</h2><?php if ($program['objectives']): ?><ol class="objective-list"><?php foreach ($program['objectives'] as $index => $item): ?><li><span>0<?= $index + 1 ?></span><p><?= e($item) ?></p></li><?php endforeach; ?></ol><?php else: $emptyIcon='trending'; require view_path('components/content-empty.php'); endif; ?></div>
</div></section>

<section class="section" id="courses"><div class="container"><header class="section__head"><div><span class="eyebrow">Related Courses</span><h2>دوره‌های تشکیل‌دهنده Program</h2><p>این دوره‌ها به ترتیب منطقی برای ساختن دانش و مهارت موردنیاز کنار هم قرار گرفته‌اند.</p></div></header><?php if ($program['related_courses']): ?><div class="grid grid--3"><?php foreach ($program['related_courses'] as $course) { require view_path('components/cards/course-card.php'); } ?></div><?php else: $emptyIcon='book'; require view_path('components/content-empty.php'); endif; ?></div></section>

<section class="section section--muted" id="events"><div class="container"><header class="section__head"><div><span class="eyebrow">Related Events</span><h2>رویدادهای مرتبط</h2><p>فضاهایی برای تمرین، گفتگو و ارتباط با جامعه تخصصی این لاین.</p></div></header><?php if ($program['related_events']): ?><div class="grid grid--3"><?php foreach ($program['related_events'] as $event) { require view_path('components/cards/event-card.php'); } ?></div><?php else: $emptyIcon='calendar'; require view_path('components/content-empty.php'); endif; ?></div></section>

<section class="section" id="mentors"><div class="container"><header class="section__head"><div><span class="eyebrow">Related Mentors</span><h2>منتورهای این Program</h2><p>متخصصانی که در طول مسیر، یادگیری و پروژه حرفه‌ای شما را همراهی می‌کنند.</p></div></header><?php if ($program['related_mentors']): ?><div class="grid grid--4 program-mentors"><?php foreach ($program['related_mentors'] as $mentor) { require view_path('components/cards/mentor-card.php'); } ?></div><?php else: $emptyIcon='user'; require view_path('components/content-empty.php'); endif; ?></div></section>

<section class="section section--muted"><div class="container"><div class="public-cta"><span class="eyebrow">Your Next Step</span><h2>این Program با هدف شما هم‌مسیر است؟</h2><p>برای بررسی پیش‌نیازها و طراحی مسیر شخصی با تیم آکادمی گفتگو کنید.</p><div class="cluster"><a class="btn btn--primary btn--lg" href="/contact?subject=program">گفتگوی راهنما</a><a class="btn btn--ghost btn--lg" href="/programs">مقایسه Programها</a></div></div></div></section>

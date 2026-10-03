<?php
$homeCopy = [
    'fa' => ['about'=>'درباره منتوریس','about_link'=>'داستان منتوریس','lines'=>'مسیرهای آکادمی','lines_text'=>'هفت حوزه‌ای که نقشه فعالیت‌های علمی و حرفه‌ای منتوریس را شکل می‌دهند.','events'=>'رویداد پیش‌رو','events_text'=>'فرصتی برای گفت‌وگو، ارتباط و رشد در کنار جامعه حرفه‌ای.','courses'=>'دوره‌ها و برنامه‌های آموزشی','founder'=>'بنیان‌گذار منتوریس','founder_link'=>'مطالعه بیوگرافی کامل','experts'=>'اساتید و همکاران علمی','content'=>'پژوهش و محتوای تخصصی','community'=>'جامعه حرفه‌ای منتوریس','community_text'=>'شبکه‌ای برای یادگیری، تبادل تجربه و ارتباط میان درمانگران، پژوهشگران و متخصصان سلامت روان.','join'=>'همراه منتوریس شوید','contact'=>'با ما در ارتباط باشید'],
    'ar' => ['about'=>'عن منتوريس','about_link'=>'قصة منتوريس','lines'=>'مسارات الأكاديمية','lines_text'=>'سبعة مجالات ترسم خريطة العمل العلمي والمهني في منتوريس.','events'=>'الفعالية القادمة','events_text'=>'فرصة للحوار والتواصل والنمو مع المجتمع المهني.','courses'=>'الدورات والبرامج','founder'=>'مؤسِّسة منتوريس','founder_link'=>'السيرة الكاملة','experts'=>'الخبراء والشركاء العلميون','content'=>'البحث والمحتوى المتخصص','community'=>'مجتمع منتوريس المهني','community_text'=>'شبكة للتعلّم وتبادل الخبرات والتواصل بين المعالجين والباحثين ومتخصصي الصحة النفسية.','join'=>'انضم إلى منتوريس','contact'=>'تواصل معنا'],
    'ku' => ['about'=>'دەربارەی مێنتۆریس','about_link'=>'چیرۆکی مێنتۆریس','lines'=>'ڕێگاکانی ئەکادیمی','lines_text'=>'حەوت بوار کە نەخشەی کاری زانستی و پیشەیی مێنتۆریس پێکدەهێنن.','events'=>'بۆنەی داهاتوو','events_text'=>'دەرفەتێک بۆ گفتوگۆ، پەیوەندی و گەشە لەگەڵ کۆمەڵگەی پیشەیی.','courses'=>'کۆرس و بەرنامەکان','founder'=>'دامەزرێنەری مێنتۆریس','founder_link'=>'ژیاننامەی تەواو','experts'=>'مامۆستا و هاوکارانی زانستی','content'=>'توێژینەوە و ناوەڕۆکی پسپۆڕی','community'=>'کۆمەڵگەی پیشەیی مێنتۆریس','community_text'=>'تۆڕێک بۆ فێربوون، گۆڕینەوەی ئەزموون و پەیوەندی لەنێوان چارەسەرکاران و توێژەران.','join'=>'لەگەڵ مێنتۆریس بن','contact'=>'پەیوەندیمان پێوە بکەن'],
    'en' => ['about'=>'About Mentoris','about_link'=>'Our story','lines'=>'Academy pathways','lines_text'=>'Seven fields that shape Mentoris Academy’s scientific and professional roadmap.','events'=>'Upcoming event','events_text'=>'An opportunity to connect, exchange experience, and grow with a professional community.','courses'=>'Courses and learning programs','founder'=>'Founder of Mentoris','founder_link'=>'Read the full biography','experts'=>'Experts and academic collaborators','content'=>'Research and specialist content','community'=>'Mentoris professional community','community_text'=>'A network for learning, exchanging experience, and connecting therapists, researchers, and mental-health professionals.','join'=>'Join Mentoris','contact'=>'Contact us'],
][locale()] ?? [];
if (!empty($eventsArchived)) {
    [$homeCopy['events'], $homeCopy['events_text']] = [
        'fa' => ['نشست‌های برگزارشده', 'مروری بر گردهمایی‌های جامعهٔ حرفه‌ای منتوریس؛ این نشست‌ها برگزار شده‌اند و ثبت‌نامشان پایان یافته است.'],
        'ar' => ['لقاءات أُقيمت', 'تعرّف إلى لقاءات مجتمع منتوريس المهني السابقة؛ انتهى التسجيل فيها.'],
        'ku' => ['کۆبوونەوە بەڕێوەچووەکان', 'چاوێک بە کۆبوونەوەکانی کۆمەڵگەی پیشەیی مێنتۆریسدا؛ تۆمارکردنیان کۆتایی هاتووە.'],
        'en' => ['Past gatherings', 'Explore previous Mentoris community gatherings. Registration for these events has closed.'],
    ][locale()];
}
?>
<section class="public-hero public-hero--sage" id="home">
    <div class="public-hero__backdrop" aria-hidden="true"></div>
    <div class="container public-hero__sage-grid">
        <div class="public-hero__content" data-reveal>
            <span class="hero-kicker"><i aria-hidden="true"></i><?= e(t('home.badge')) ?></span>
            <h1><?= e(t('home.title.before')) ?> <span class="text-gradient"><?= e(t('home.title.accent')) ?></span></h1>
            <p><?= e(t('home.lead')) ?></p>
            <div class="hero__actions"><a class="btn btn--primary btn--lg" href="/about"><?= e(t('home.cta.primary')) ?></a><a class="btn btn--ghost btn--lg" href="/founder"><?= e(t('home.cta.secondary')) ?></a></div>
            <div class="hero-proof" aria-label="Mentoris values"><span><b>01</b> Learning</span><span><b>02</b> Mentorship</span><span><b>03</b> Community</span></div>
        </div>
        <div class="hero-scene" data-reveal><img src="<?= asset('images/mentoris-hero-sage-v2.png') ?>" alt="فضای آرام و حرفه‌ای آکادمی منتوریس" width="1536" height="1024" fetchpriority="high"><div class="hero-scene__seal"><b>M</b><span>MENTORIS<br><small>ACADEMY</small></span></div></div>
    </div>
</section>

<section class="section" id="mission"><div class="container"><div class="grid grid--2 mission-grid">
    <article class="mission-card" data-reveal><span class="eyebrow">Mission</span><h2><?= e(t('home.mission')) ?></h2><p><?= e(t('home.mission.text')) ?></p></article>
    <article class="mission-card mission-card--accent" data-reveal><span class="eyebrow">Vision</span><h2><?= e(t('home.vision')) ?></h2><p><?= e(t('home.vision.text')) ?></p></article>
</div></div></section>

<section class="section section--muted"><div class="container story-panel" data-reveal>
    <div><span class="eyebrow">Mentoris Story</span><h2><?= e($homeCopy['about']) ?></h2><p class="lead-copy"><?= e($about['lead']) ?></p><?php foreach (array_slice($about['paragraphs'], 0, 2) as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?><a class="btn btn--secondary" href="/about"><?= e($homeCopy['about_link']) ?></a></div>
    <blockquote><span aria-hidden="true">“</span><?= e($about['signature']) ?></blockquote>
</div></section>

<section class="section" id="lines"><div class="container">
    <header class="section__head" data-reveal><div><span class="eyebrow">Academy Lines</span><h2><?= e($homeCopy['lines']) ?></h2><p><?= e($homeCopy['lines_text']) ?></p></div><a class="btn btn--ghost" href="/academy"><?= e(t('nav.lines')) ?></a></header>
    <div class="academy-lines-grid"><?php foreach ($lines as $index => $line): ?><a class="line-card" href="/academy/<?= e($line['slug']) ?>" data-reveal><span class="line-card__number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><div class="line-card__icon"><?= icon($line['icon'], 'ui-icon--lg') ?></div><h3><?= e($line['title']) ?></h3><span class="line-card__en"><?= e($line['en']) ?></span><p><?= e($line['description']) ?></p></a><?php endforeach; ?></div>
</div></section>

<section class="section section--muted" id="events"><div class="container">
    <header class="section__head" data-reveal><div><span class="eyebrow">Events</span><h2><?= e($homeCopy['events']) ?></h2><p><?= e($homeCopy['events_text']) ?></p></div><a class="btn btn--secondary" href="/events"><?= e(t('nav.events')) ?></a></header>
    <?php if ($events): ?><div class="grid grid--<?= count($events) === 2 ? '2' : '3' ?> home-events <?= count($events) === 1 ? 'home-events--single' : '' ?>"><?php foreach ($events as $event): $event = \App\Services\PublicContentService::event($event['slug']) ?? $event; require view_path('components/cards/event-card.php'); endforeach; ?></div><?php else: ?><?php $emptyIcon='calendar'; require view_path('components/content-empty.php'); ?><?php endif; ?>
</div></section>

<?php if (!empty($courses)): ?>
<section class="section" id="courses"><div class="container">
    <header class="section__head" data-reveal><div><span class="eyebrow">Learning</span><h2><?= e($homeCopy['courses']) ?></h2></div><a class="btn btn--ghost" href="/courses"><?= e(t('nav.courses')) ?></a></header>
    <div class="grid grid--3"><?php foreach (array_slice($courses, 0, 3) as $course): require view_path('components/cards/course-card.php'); endforeach; ?></div>
</div></section>
<?php endif; ?>

<section class="section section--muted founder-preview"><div class="container founder-preview__grid">
    <div class="founder-preview__portrait" data-reveal><img src="<?= asset($founder['image']) ?>" alt="<?= e($founder['name']) ?>" loading="lazy" width="1024" height="1536"></div>
    <div class="founder-preview__content stack" data-reveal><span class="eyebrow">Founder</span><h2><?= e($homeCopy['founder']) ?></h2><h3><?= e($founder['name']) ?></h3><p class="founder-role"><?= e($founder['role']) ?></p><p><?= e($founder['short_bio']) ?></p><blockquote>«<?= e($founder['quote']) ?>»</blockquote><div><a class="btn btn--primary" href="/founder"><?= e($homeCopy['founder_link']) ?></a></div></div>
</div></section>

<?php if ($articles): ?><section class="section"><div class="container"><header class="section__head"><div><span class="eyebrow">Mentoris Journal</span><h2><?= e($homeCopy['content']) ?></h2></div><a class="btn btn--ghost" href="/articles"><?= e(t('nav.articles')) ?></a></header><div class="grid grid--3"><?php foreach (array_slice($articles, 0, 3) as $article) { require view_path('components/cards/article-card.php'); } ?></div></div></section><?php endif; ?>

<section class="section section--muted"><div class="container"><div class="community-launch" data-reveal><div><span class="eyebrow">Community</span><h2><?= e($homeCopy['community']) ?></h2><p><?= e($homeCopy['community_text']) ?></p></div><div class="cluster"><a class="btn btn--primary btn--lg" href="/community"><?= e($homeCopy['join']) ?></a><a class="btn btn--ghost btn--lg" href="/contact"><?= e($homeCopy['contact']) ?></a></div></div></div></section>

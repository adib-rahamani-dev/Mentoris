<?php
$homeCopy = [
    'fa' => ['about'=>'درباره منتوریس','about_link'=>'داستان منتوریس','lines'=>'مسیرهای آکادمی','lines_text'=>'هفت حوزه‌ای که نقشه فعالیت‌های علمی و حرفه‌ای منتوریس را شکل می‌دهند.','events'=>'رویداد پیش‌رو','events_text'=>'فرصتی برای گفت‌وگو، ارتباط و رشد در کنار جامعه حرفه‌ای.','courses'=>'دوره‌ها و برنامه‌های آموزشی','founder'=>'بنیان‌گذار منتوریس','founder_link'=>'مطالعه بیوگرافی کامل','experts'=>'اساتید و همکاران علمی','content'=>'پژوهش و محتوای تخصصی','community'=>'جامعه حرفه‌ای منتوریس','community_text'=>'شبکه‌ای برای یادگیری، تبادل تجربه و ارتباط میان درمانگران، پژوهشگران و متخصصان سلامت روان.','join'=>'همراه منتوریس شوید','contact'=>'با ما در ارتباط باشید'],
    'ar' => ['about'=>'عن منتوريس','about_link'=>'قصة منتوريس','lines'=>'مسارات الأكاديمية','lines_text'=>'سبعة مجالات ترسم خريطة العمل العلمي والمهني في منتوريس.','events'=>'الفعالية القادمة','events_text'=>'فرصة للحوار والتواصل والنمو مع المجتمع المهني.','courses'=>'الدورات والبرامج','founder'=>'مؤسِّسة منتوريس','founder_link'=>'السيرة الكاملة','experts'=>'الخبراء والشركاء العلميون','content'=>'البحث والمحتوى المتخصص','community'=>'مجتمع منتوريس المهني','community_text'=>'شبكة للتعلّم وتبادل الخبرات والتواصل بين المعالجين والباحثين ومتخصصي الصحة النفسية.','join'=>'انضم إلى منتوريس','contact'=>'تواصل معنا'],
    'ku' => ['about'=>'دەربارەی مێنتۆریس','about_link'=>'چیرۆکی مێنتۆریس','lines'=>'ڕێگاکانی ئەکادیمی','lines_text'=>'حەوت بوار کە نەخشەی کاری زانستی و پیشەیی مێنتۆریس پێکدەهێنن.','events'=>'بۆنەی داهاتوو','events_text'=>'دەرفەتێک بۆ گفتوگۆ، پەیوەندی و گەشە لەگەڵ کۆمەڵگەی پیشەیی.','courses'=>'کۆرس و بەرنامەکان','founder'=>'دامەزرێنەری مێنتۆریس','founder_link'=>'ژیاننامەی تەواو','experts'=>'مامۆستا و هاوکارانی زانستی','content'=>'توێژینەوە و ناوەڕۆکی پسپۆڕی','community'=>'کۆمەڵگەی پیشەیی مێنتۆریس','community_text'=>'تۆڕێک بۆ فێربوون، گۆڕینەوەی ئەزموون و پەیوەندی لەنێوان چارەسەرکاران و توێژەران.','join'=>'لەگەڵ مێنتۆریس بن','contact'=>'پەیوەندیمان پێوە بکەن'],
    'en' => ['about'=>'About Mentoris','about_link'=>'Our story','lines'=>'Academy pathways','lines_text'=>'Seven fields that shape Mentoris Academy’s scientific and professional roadmap.','events'=>'Upcoming event','events_text'=>'An opportunity to connect, exchange experience, and grow with a professional community.','courses'=>'Courses and learning programs','founder'=>'Founder of Mentoris','founder_link'=>'Read the full biography','experts'=>'Experts and academic collaborators','content'=>'Research and specialist content','community'=>'Mentoris professional community','community_text'=>'A network for learning, exchanging experience, and connecting therapists, researchers, and mental-health professionals.','join'=>'Join Mentoris','contact'=>'Contact us'],
][locale()] ?? [];
?>
<section class="public-hero public-hero--founder" id="home">
    <div class="public-hero__backdrop" aria-hidden="true"></div>
    <div class="container public-hero__founder-grid">
        <div class="public-hero__content" data-reveal>
            <span class="badge badge--brand"><?= e(t('home.badge')) ?></span>
            <h1><?= e(t('home.title.before')) ?> <span class="text-gradient"><?= e(t('home.title.accent')) ?></span></h1>
            <p><?= e(t('home.lead')) ?></p>
            <div class="hero__actions"><a class="btn btn--primary btn--lg" href="/about"><?= e(t('home.cta.primary')) ?></a><a class="btn btn--ghost btn--lg" href="/founder"><?= e(t('home.cta.secondary')) ?></a></div>
            <div class="hero-proof" aria-label="Mentoris values"><span><b>01</b> Science</span><span><b>02</b> Mentoring</span><span><b>03</b> Community</span></div>
        </div>
        <figure class="hero-founder" data-reveal>
            <div class="hero-founder__frame"><img src="<?= asset($founder['image']) ?>" alt="<?= e($founder['name'] . '، ' . $founder['role']) ?>" width="1024" height="1536" fetchpriority="high"></div>
            <figcaption><strong><?= e($founder['name']) ?></strong><span><?= e($founder['role']) ?></span></figcaption>
        </figure>
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
    <div class="academy-lines-grid"><?php foreach ($lines as $index => $line): ?><a class="line-card" href="/academy/<?= e($line['slug']) ?>" data-reveal><span class="line-card__number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><div class="line-card__icon" aria-hidden="true"><?= e($line['icon']) ?></div><h3><?= e($line['title']) ?></h3><span class="line-card__en"><?= e($line['en']) ?></span><p><?= e($line['description']) ?></p></a><?php endforeach; ?></div>
</div></section>

<section class="section section--muted" id="events"><div class="container">
    <header class="section__head" data-reveal><div><span class="eyebrow">Events</span><h2><?= e($homeCopy['events']) ?></h2><p><?= e($homeCopy['events_text']) ?></p></div><a class="btn btn--secondary" href="/events"><?= e(t('nav.events')) ?></a></header>
    <?php if ($events): ?><div class="grid grid--3"><?php foreach ($events as $event): $event = \App\Services\PublicContentService::event($event['slug']) ?? $event; require view_path('components/cards/event-card.php'); endforeach; ?></div><?php else: ?><?php $emptyIcon='◫'; require view_path('components/content-empty.php'); ?><?php endif; ?>
</div></section>

<section class="section" id="courses"><div class="container">
    <header class="section__head" data-reveal><div><span class="eyebrow">Learning</span><h2><?= e($homeCopy['courses']) ?></h2></div><a class="btn btn--ghost" href="/courses"><?= e(t('nav.courses')) ?></a></header>
    <?php $emptyIcon='▤'; require view_path('components/content-empty.php'); ?>
</div></section>

<section class="section section--muted founder-preview"><div class="container founder-preview__grid">
    <div class="founder-preview__portrait" data-reveal><img src="<?= asset($founder['image']) ?>" alt="<?= e($founder['name']) ?>" loading="lazy" width="1024" height="1536"></div>
    <div class="founder-preview__content stack" data-reveal><span class="eyebrow">Founder</span><h2><?= e($homeCopy['founder']) ?></h2><h3><?= e($founder['name']) ?></h3><p class="founder-role"><?= e($founder['role']) ?></p><p><?= e($founder['short_bio']) ?></p><blockquote>«<?= e($founder['quote']) ?>»</blockquote><div><a class="btn btn--primary" href="/founder"><?= e($homeCopy['founder_link']) ?></a></div></div>
</div></section>

<section class="section"><div class="container"><div class="grid grid--2 launch-empty-grid">
    <div><header class="section__head"><div><span class="eyebrow">Faculty</span><h2><?= e($homeCopy['experts']) ?></h2></div></header><?php $emptyIcon='◎'; require view_path('components/content-empty.php'); ?></div>
    <div><header class="section__head"><div><span class="eyebrow">Knowledge</span><h2><?= e($homeCopy['content']) ?></h2></div></header><?php $emptyIcon='⌁'; require view_path('components/content-empty.php'); ?></div>
</div></div></section>

<section class="section section--muted"><div class="container"><div class="community-launch" data-reveal><div><span class="eyebrow">Community</span><h2><?= e($homeCopy['community']) ?></h2><p><?= e($homeCopy['community_text']) ?></p></div><div class="cluster"><a class="btn btn--primary btn--lg" href="/community"><?= e($homeCopy['join']) ?></a><a class="btn btn--ghost btn--lg" href="/contact"><?= e($homeCopy['contact']) ?></a></div></div></div></section>

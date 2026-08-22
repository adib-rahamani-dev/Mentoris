<section class="public-hero" id="home">
    <div class="public-hero__backdrop" aria-hidden="true"></div>
    <div class="container public-hero__inner">
        <div class="public-hero__content" data-reveal>
            <span class="badge badge--brand">Mentoris Academy</span>
            <h1>اکوسیستم رشد، یادگیری و <span class="text-gradient">تأثیرگذاری</span></h1>
            <p>فضایی علمی و حرفه‌ای برای روان‌شناسان، درمانگران، دانشجویان و همه کسانی که به توسعه فردی و سلامت روان اهمیت می‌دهند.</p>
            <div class="hero__actions"><a class="btn btn--primary btn--lg" href="/programs">مشاهده برنامه‌ها</a><a class="btn btn--ghost btn--lg" href="/about">درباره Mentoris</a></div>
            <div class="public-hero__trust"><span><strong>+۳۵</strong> دوره تخصصی</span><span><strong>+۲۰</strong> استاد و منتور</span><span><strong>+۲٬۸۰۰</strong> عضو جامعه</span></div>
        </div>
    </div>
</section>

<section class="section" id="mission">
    <div class="container">
        <div class="grid grid--2 mission-grid">
            <article class="mission-card" data-reveal><span class="eyebrow">Mission</span><h2>مأموریت ما</h2><p>تبدیل دانش معتبر روان‌شناسی به تجربه‌های یادگیری کاربردی، اخلاق‌مدار و در دسترس؛ برای متخصصانی که می‌خواهند اثر عمیق‌تری بر زندگی انسان‌ها بگذارند.</p></article>
            <article class="mission-card mission-card--accent" data-reveal><span class="eyebrow">Vision</span><h2>چشم‌انداز ما</h2><p>ساختن یک اکوسیستم پویا و بین‌رشته‌ای که آموزش، پژوهش و جامعه حرفه‌ای را برای ارتقای سلامت روان فارسی‌زبانان به هم پیوند می‌دهد.</p></article>
        </div>
    </div>
</section>

<section class="section section--muted" id="lines">
    <div class="container">
        <header class="section__head" data-reveal><div><span class="eyebrow">Academy Lines</span><h2>لاین‌های اصلی Mentoris</h2><p>شش مسیر مکمل برای پوشش نیازهای یادگیری، حرفه‌ای و اجتماعی جامعه Mentoris.</p></div><a class="btn btn--ghost" href="/about#lines">آشنایی بیشتر</a></header>
        <div class="academy-lines-grid">
            <?php foreach ($lines as $index => $line): ?><article class="line-card" data-reveal><span class="line-card__number">0<?= $index + 1 ?></span><div class="line-card__icon" aria-hidden="true">◇</div><h3><?= e($line['title']) ?></h3><span class="line-card__en"><?= e($line['en']) ?></span><p><?= e($line['description']) ?></p></article><?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="events">
    <div class="container">
        <header class="section__head" data-reveal><div><span class="eyebrow">Upcoming</span><h2>رویدادها و برنامه‌های جاری</h2><p>تازه‌ترین فرصت‌های یادگیری و گفتگو در جامعه Mentoris.</p></div><a class="brand" href="/programs">مشاهده همه برنامه‌ها ←</a></header>
        <div class="grid grid--3"><?php foreach ($events as $event) { require view_path('components/cards/event-card.php'); } ?></div>
    </div>
</section>

<section class="section section--muted" id="courses">
    <div class="container">
        <header class="section__head" data-reveal><div><span class="eyebrow">Featured Courses</span><h2>دوره‌های منتخب</h2><p>برنامه‌هایی ساختاریافته، مبتنی بر شواهد و نزدیک به نیازهای واقعی حرفه.</p></div><a class="btn btn--secondary" href="/programs">همه دوره‌ها</a></header>
        <div class="grid grid--3"><?php foreach ($programs as $course) { require view_path('components/cards/course-card.php'); } ?></div>
    </div>
</section>

<section class="section founder-preview">
    <div class="container founder-preview__grid">
        <div class="founder-preview__portrait" data-reveal><img src="<?= asset('images/founder-fictional-v1.png') ?>" alt="پرتره نمایشی بنیان‌گذار Mentoris" loading="lazy"><span class="founder-preview__note">تصویر نمایشی · قابل جایگزینی</span></div>
        <div class="founder-preview__content stack" data-reveal><span class="eyebrow">Founder</span><h2>درباره بنیان‌گذار</h2><h3>دکتر امیرحسین محمدی</h3><p>روان‌شناس، پژوهشگر و مدرس؛ با تمرکز بر پیوند دانش دانشگاهی، تجربه بالینی و نیازهای واقعی جامعه. Mentoris از یک باور ساده آغاز شد: یادگیری حرفه‌ای باید عمیق، انسانی و اثرگذار باشد.</p><blockquote>«توسعه حرفه‌ای فقط آموختن ابزارهای تازه نیست؛ ساختن شیوه‌ای مسئولانه‌تر برای دیدن انسان است.»</blockquote><div><a class="btn btn--secondary" href="/founder">مشاهده داستان کامل</a></div></div>
    </div>
</section>

<section class="section section--muted" id="mentors">
    <div class="container">
        <header class="section__head" data-reveal><div><span class="eyebrow">Our Mentors</span><h2>اساتید و متخصصان</h2><p>شبکه‌ای از متخصصان باتجربه که آموزش را با عمل حرفه‌ای پیوند می‌دهند.</p></div><a class="brand" href="/mentors">مشاهده همه متخصصان ←</a></header>
        <div class="grid grid--4"><?php foreach ($mentors as $mentor) { require view_path('components/cards/mentor-card.php'); } ?></div>
    </div>
</section>

<section class="section" id="research">
    <div class="container">
        <header class="section__head" data-reveal><div><span class="eyebrow">Research & Content</span><h2>پژوهش، مقاله و محتوای تازه</h2><p>خواندنی‌ها و شنیدنی‌هایی برای ادامه یادگیری در مسیر حرفه‌ای.</p></div></header>
        <div class="grid grid--4"><?php foreach ($articles as $article) { require view_path('components/cards/article-card.php'); } ?></div>
    </div>
</section>

<section class="section section--muted" id="community">
    <div class="container"><div class="community-banner public-community" data-reveal><div class="stack"><span class="eyebrow">Mentoris Community</span><h2>یادگیری در کنار یک جامعه حرفه‌ای</h2><p>پرسش‌هایتان را به اشتراک بگذارید، تجربه‌های واقعی را بشنوید و با همکارانی هم‌مسیر ارتباط بسازید.</p><div><a class="btn btn--primary" href="/contact">پیوستن به Community</a></div></div><div class="community-orbit" aria-hidden="true"><span>۲۸۰۰+</span><small>عضو همراه</small></div></div></div>
</section>

<section class="section"><div class="container"><div class="public-cta" data-reveal><span class="eyebrow">Start Your Journey</span><h2>مسیر رشد حرفه‌ای شما از اینجا آغاز می‌شود</h2><p>برنامه مناسب خود را پیدا کنید یا برای انتخاب مسیر با تیم Mentoris گفتگو کنید.</p><div class="cluster"><a class="btn btn--primary btn--lg" href="/programs">مشاهده برنامه‌ها</a><a class="btn btn--ghost btn--lg" href="/contact">دریافت راهنمایی</a></div></div></div></section>

<section class="hero" id="intro">
    <div class="container hero__grid">
        <div class="hero__content" data-reveal>
            <span class="badge badge--brand">Mentoris Design System · v1.0</span>
            <h1 class="hero__title">زبان بصری مشترک برای <span class="text-gradient">رشد و یادگیری</span></h1>
            <p class="hero__lead">یک سیستم طراحی تاریک، آرام و حرفه‌ای که تمام صفحات آینده منتوریس را یکپارچه، سریع و دسترس‌پذیر می‌کند.</p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="#foundations">مشاهده بنیان‌ها</a>
                <button class="btn btn--ghost btn--lg" type="button" data-modal-open="demo-modal">نمایش Modal</button>
            </div>
        </div>
        <div class="hero__visual" aria-hidden="true" data-reveal>
            <div class="hero-orbit">
                <div class="hero-orbit__core"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M9 3a3 3 0 0 0-3 3v1a3 3 0 0 0-2 5.8A3.5 3.5 0 0 0 7.5 18H9m6-15a3 3 0 0 1 3 3v1a3 3 0 0 1 2 5.8A3.5 3.5 0 0 1 16.5 18H15M9 3v18m6-18v18M9 8h3m0 5h3m-6 4h3"/></svg></div>
                <i class="hero-orbit__node"></i><i class="hero-orbit__node"></i><i class="hero-orbit__node"></i>
            </div>
        </div>
    </div>
</section>

<section class="section section--muted" id="foundations">
    <div class="container">
        <header class="section__head" data-reveal><div><span class="eyebrow">Foundations</span><h2>بنیان‌های طراحی</h2><p>تمام رنگ‌ها، فاصله‌ها، تایپوگرافی، شعاع و سایه‌ها از توکن‌های مرکزی تغذیه می‌شوند.</p></div><span class="badge badge--success">CSS Variables</span></header>
        <div class="grid grid--4" data-reveal>
            <div class="token-swatch token-swatch--brand">Brand 600 · #7C3AED</div>
            <div class="token-swatch token-swatch--surface">Surface 2 · #0D1524</div>
            <div class="token-swatch token-swatch--success">Success · #34D399</div>
            <div class="token-swatch token-swatch--danger">Danger · #FB7185</div>
        </div>
        <div class="grid grid--3 mt-6">
            <article class="card feature-card" data-reveal><div class="feature-card__icon">Aa</div><h3>تایپوگرافی فارسی</h3><p>فونت محلی، وزن‌های مشخص و مقیاس واکنش‌گرا برای خوانایی بهتر.</p></article>
            <article class="card feature-card" data-reveal><div class="feature-card__icon">↔</div><h3>Spacing چهارپیکسلی</h3><p>ریتم ثابت از ۴ تا ۹۶ پیکسل برای چیدمان قابل پیش‌بینی.</p></article>
            <article class="card feature-card" data-reveal><div class="feature-card__icon">◫</div><h3>Grid منعطف</h3><p>کانتینر ۱۳۲۰ پیکسلی و شبکه‌های دو، سه و چهارستونه responsive.</p></article>
        </div>
    </div>
</section>

<section class="section" id="components">
    <div class="container flow" style="--flow-space: var(--space-12)">
        <header class="section__head" data-reveal><div><span class="eyebrow">Components</span><h2>کامپوننت‌های مشترک</h2><p>حالت‌های تعاملی، تمرکز کیبورد و رفتار responsive از ابتدا در هر جزء تعریف شده‌اند.</p></div></header>

        <div class="surface component-stage flow" data-reveal>
            <h3>دکمه‌ها و Badgeها</h3>
            <div class="component-row"><button class="btn btn--primary">دکمه اصلی</button><button class="btn btn--secondary">دکمه ثانویه</button><button class="btn btn--ghost">دکمه شفاف</button><button class="btn btn--danger">عملیات حساس</button></div>
            <div class="component-row"><span class="badge badge--brand">پیشنهاد ویژه</span><span class="badge badge--success">فعال</span><span class="badge badge--warning">در انتظار</span><span class="badge badge--danger">ظرفیت محدود</span><span class="badge badge--neutral">آفلاین</span></div>
        </div>

        <div class="grid grid--2">
            <div class="surface component-stage form-demo flow" data-reveal>
                <h3>فرم‌ها</h3>
                <label class="form-group"><span class="form-label">نام و نام خانوادگی</span><input class="form-control" placeholder="مثلاً سارا احمدی"></label>
                <label class="form-group"><span class="form-label">حوزه علاقه‌مندی</span><select class="form-select"><option>روان‌شناسی و رشد فردی</option><option>توسعه حرفه‌ای</option></select></label>
                <label class="check"><input type="checkbox" checked> قوانین و حریم خصوصی را می‌پذیرم</label>
                <button class="btn btn--primary" type="button" data-toast="درخواست شما با موفقیت ثبت شد." data-toast-title="ثبت موفق">ثبت درخواست و نمایش Toast</button>
            </div>

            <div class="surface component-stage" data-reveal>
                <div class="tabs">
                    <div class="tabs__list" role="tablist" aria-label="معرفی سیستم">
                        <button class="tabs__tab" role="tab" aria-selected="true" aria-controls="tab-visual">زبان بصری</button>
                        <button class="tabs__tab" role="tab" aria-selected="false" aria-controls="tab-access">دسترسی‌پذیری</button>
                        <button class="tabs__tab" role="tab" aria-selected="false" aria-controls="tab-code">معماری</button>
                    </div>
                    <div class="tabs__panel" id="tab-visual" role="tabpanel"><h3>هویت آرام و متمرکز</h3><p>تم تاریک عمیق با بنفش کنترل‌شده، به محتوای تخصصی حس اعتماد و تمرکز می‌دهد.</p></div>
                    <div class="tabs__panel" id="tab-access" role="tabpanel" hidden><h3>قابل استفاده برای همه</h3><p>کنتراست مناسب، focus visible، حرکت کاهش‌پذیر و کنترل کامل با صفحه‌کلید.</p></div>
                    <div class="tabs__panel" id="tab-code" role="tabpanel" hidden><h3>ساختار لایه‌ای</h3><p>توکن‌ها، پایه، layout، components، sections و pages بدون وابستگی درهم.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--muted courses-showcase" id="courses">
    <div class="container">
        <header class="section__head" data-reveal><div><span class="eyebrow">Cards & Slider</span><h2>کارت‌های محتوایی</h2><p>یک الگوی مشترک برای دوره، رویداد، مقاله، منتور و منابع.</p></div></header>
        <div class="slider" data-reveal>
            <div class="slider__viewport"><div class="slider__track">
                <?php foreach ([['طرحواره‌درمانی پیشرفته','۱۲ جلسه','۳٬۹۰۰٬۰۰۰ تومان','brand'],['ذهن‌آگاهی و خودشناسی','۸ جلسه','۲٬۴۰۰٬۰۰۰ تومان','success'],['روان‌شناسی کودک','کارگاه','۱٬۸۰۰٬۰۰۰ تومان','warning'],['توسعه حرفه‌ای درمانگران','آنلاین','رایگان','neutral']] as [$name,$meta,$price,$badge]): ?>
                    <article class="card slider__slide">
                        <div class="card__media"><span class="badge badge--<?= e($badge) ?> card__badge"><?= e($meta) ?></span><svg class="card__media-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 3a3 3 0 0 0-3 3v1a3 3 0 0 0-2 5.8A3.5 3.5 0 0 0 7.5 18H9m6-15a3 3 0 0 1 3 3v1a3 3 0 0 1 2 5.8A3.5 3.5 0 0 1 16.5 18H15M9 3v18m6-18v18"/></svg></div>
                        <div class="card__body"><h3 class="card__title"><?= e($name) ?></h3><p class="card__text">یادگیری ساختاریافته با همراهی متخصصان و تمرین‌های کاربردی.</p><div class="card__meta"><span>آنلاین</span><span>گواهی پایان دوره</span></div></div>
                        <div class="card__footer"><span class="course-price"><?= e($price) ?></span><button class="btn btn--secondary btn--sm">جزئیات</button></div>
                    </article>
                <?php endforeach; ?>
            </div></div>
            <div class="slider__controls"><button class="btn btn--ghost btn--icon" data-slider-prev aria-label="اسلاید قبلی">→</button><button class="btn btn--ghost btn--icon" data-slider-next aria-label="اسلاید بعدی">←</button></div>
        </div>
    </div>
</section>

<section class="section" id="faq">
    <div class="container container--md">
        <header class="section__head center" data-reveal><div><span class="eyebrow">Accordion</span><h2>سؤالات متداول Design System</h2></div></header>
        <div class="accordion" data-accordion="single" data-reveal>
            <?php foreach ([['faq-1','چطور رنگ اصلی را تغییر دهیم؟','فقط توکن‌های Brand در variables.css را تغییر دهید؛ تمام کامپوننت‌ها خودکار هماهنگ می‌شوند.'],['faq-2','آیا کامپوننت‌ها مستقل هستند؟','بله، هر کامپوننت فایل CSS و JavaScript مستقل دارد و از قراردادهای مشترک استفاده می‌کند.'],['faq-3','آیا سیستم روی موبایل آماده است؟','بله، Grid، Navbar، Slider، تایپوگرافی و فاصله‌ها برای موبایل و تبلت واکنش‌گرا هستند.']] as $index => [$id,$question,$answer]): ?>
                <div class="accordion__item"><button class="accordion__trigger" type="button" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="<?= e($id) ?>"><span><?= e($question) ?></span><span class="accordion__icon">+</span></button><div class="accordion__panel <?= $index === 0 ? 'is-open' : '' ?>" id="<?= e($id) ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"><div><p><?= e($answer) ?></p></div></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container"><div class="community-banner" data-reveal><div class="stack"><span class="eyebrow">Community</span><h2>با یک زبان مشترک بسازیم</h2><p>از این پس تمام صفحات Mentoris روی همین توکن‌ها و کامپوننت‌ها توسعه پیدا می‌کنند.</p><div><button class="btn btn--primary" data-toast="Design System آماده استفاده در صفحات بعدی است.">اعلام آمادگی</button></div></div><div aria-hidden="true" class="center"><span class="text-gradient" style="font-size:5rem">M</span></div></div></div>
</section>

<div class="modal" id="demo-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="demo-modal-title">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header"><h3 id="demo-modal-title">عضویت در Mentoris</h3><button class="modal__close" type="button" data-modal-close aria-label="بستن">×</button></div>
        <div class="modal__body stack"><p>این Modal با focus trap، کلید Escape و بازگرداندن تمرکز ساخته شده است.</p><label class="form-group"><span class="form-label">ایمیل</span><input class="form-control ltr" type="email" placeholder="name@example.com"></label></div>
        <div class="modal__footer"><button class="btn btn--ghost" data-modal-close>انصراف</button><button class="btn btn--primary" data-toast="عضویت آزمایشی انجام شد.">ادامه</button></div>
    </div>
</div>

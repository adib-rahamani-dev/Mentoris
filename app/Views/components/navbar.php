<?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; $authIdentity = (new \App\Core\Session())->get('auth.user'); ?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand-logo" href="/" aria-label="Mentoris - صفحه اصلی"><span class="brand-logo__mark" aria-hidden="true"></span><span class="brand-logo__text"><strong>Mentoris</strong><small>Academy</small></span></a>
        <nav class="navbar" data-navbar aria-label="منوی اصلی">
            <a class="navbar__link <?= $currentPath === '/' ? 'is-active' : '' ?>" href="/">خانه</a>
            <div class="dropdown"><button class="dropdown__trigger <?= str_starts_with($currentPath, '/programs') || str_starts_with($currentPath, '/courses') || str_starts_with($currentPath, '/academy') || str_starts_with($currentPath, '/specializations') ? 'is-active' : '' ?>" type="button" aria-expanded="false">آکادمی ▾</button><div class="dropdown__menu"><a class="dropdown__item" href="/courses">دوره‌ها</a><a class="dropdown__item" href="/programs">Programها</a><a class="dropdown__item" href="/academy">لاین‌های آکادمی</a><a class="dropdown__item" href="/specializations">تخصص‌ها</a></div></div>
            <a class="navbar__link <?= str_starts_with($currentPath, '/events') ? 'is-active' : '' ?>" href="/events">رویدادها</a>
            <a class="navbar__link <?= str_starts_with($currentPath, '/community') ? 'is-active' : '' ?>" href="/community">Community</a>
            <a class="navbar__link <?= $currentPath === '/mentors' ? 'is-active' : '' ?>" href="/mentors">اساتید</a>
            <a class="navbar__link <?= $currentPath === '/about' ? 'is-active' : '' ?>" href="/about">درباره ما</a>
            <a class="navbar__link <?= $currentPath === '/founder' ? 'is-active' : '' ?>" href="/founder">بنیان‌گذار</a>
            <a class="navbar__link <?= $currentPath === '/contact' ? 'is-active' : '' ?>" href="/contact">ارتباط با ما</a>
            <a class="navbar__link navbar__account-link <?= in_array($currentPath, ['/dashboard','/profile','/my-courses','/my-events','/my-certificates','/notifications'], true) ? 'is-active' : '' ?>" href="<?= $authIdentity ? '/dashboard' : '/login' ?>"><?= $authIdentity ? 'حساب من' : 'ورود / عضویت' ?></a>
        </nav>
        <div class="navbar__actions"><button class="theme-toggle" type="button" data-theme-toggle aria-label="تغییر حالت رنگ" aria-pressed="false"><span class="theme-toggle__sun" aria-hidden="true">☀</span><span class="theme-toggle__moon" aria-hidden="true">☾</span></button><a class="btn btn--primary btn--sm" href="<?= $authIdentity ? '/dashboard' : '/login' ?>"><?= $authIdentity ? e($authIdentity['name'] ?? 'حساب من') : 'ورود / عضویت' ?></a><button class="navbar__toggle" type="button" data-navbar-toggle aria-label="بازکردن منو" aria-expanded="false"><span></span></button></div>
    </div>
</header>

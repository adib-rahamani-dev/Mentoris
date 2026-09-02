<?php

use App\Core\Translator;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$authIdentity = (new \App\Core\Session())->get('auth.user');
$accountPaths = ['/dashboard', '/profile', '/my-courses', '/my-events', '/my-certificates', '/notifications', '/orders'];
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand-logo" href="/" aria-label="<?= e(t('brand.home')) ?>">
            <span class="brand-logo__mark" aria-hidden="true"></span>
            <span class="brand-logo__text"><strong>Mentoris</strong><small>Academy</small></span>
        </a>

        <nav class="navbar" data-navbar aria-label="<?= e(t('nav.home')) ?>">
            <a class="navbar__link <?= $currentPath === '/' ? 'is-active' : '' ?>" href="/"><?= e(t('nav.home')) ?></a>
            <div class="dropdown">
                <button class="dropdown__trigger <?= str_starts_with($currentPath, '/programs') || str_starts_with($currentPath, '/courses') || str_starts_with($currentPath, '/academy') || str_starts_with($currentPath, '/specializations') ? 'is-active' : '' ?>" type="button" aria-expanded="false"><?= e(t('nav.academy')) ?> ▾</button>
                <div class="dropdown__menu">
                    <a class="dropdown__item" href="/courses"><?= e(t('nav.courses')) ?></a>
                    <a class="dropdown__item" href="/programs"><?= e(t('nav.programs')) ?></a>
                    <a class="dropdown__item" href="/academy"><?= e(t('nav.lines')) ?></a>
                    <a class="dropdown__item" href="/specializations"><?= e(t('nav.specializations')) ?></a>
                </div>
            </div>
            <a class="navbar__link <?= str_starts_with($currentPath, '/events') ? 'is-active' : '' ?>" href="/events"><?= e(t('nav.events')) ?></a>
            <a class="navbar__link <?= str_starts_with($currentPath, '/community') ? 'is-active' : '' ?>" href="/community"><?= e(t('nav.community')) ?></a>
            <a class="navbar__link <?= $currentPath === '/mentors' ? 'is-active' : '' ?>" href="/mentors"><?= e(t('nav.mentors')) ?></a>
            <a class="navbar__link <?= $currentPath === '/about' ? 'is-active' : '' ?>" href="/about"><?= e(t('nav.about')) ?></a>
            <a class="navbar__link <?= $currentPath === '/founder' ? 'is-active' : '' ?>" href="/founder"><?= e(t('nav.founder')) ?></a>
            <a class="navbar__link <?= $currentPath === '/contact' ? 'is-active' : '' ?>" href="/contact"><?= e(t('nav.contact')) ?></a>
            <a class="navbar__link navbar__account-link <?= in_array($currentPath, $accountPaths, true) ? 'is-active' : '' ?>" href="<?= $authIdentity ? '/dashboard' : '/login' ?>"><?= e($authIdentity ? t('nav.account') : t('nav.login')) ?></a>
        </nav>

        <div class="navbar__actions">
            <div class="language-switcher dropdown">
                <button class="language-switcher__trigger dropdown__trigger" type="button" aria-expanded="false" aria-label="<?= e(t('language.select')) ?>"><span aria-hidden="true">文</span><b><?= e(strtoupper(locale())) ?></b></button>
                <div class="dropdown__menu language-switcher__menu">
                    <?php foreach (Translator::SUPPORTED as $language): ?>
                        <?php $languageTag = $language === 'ku' ? 'ckb' : $language; ?>
                        <a class="dropdown__item <?= locale() === $language ? 'is-active' : '' ?>" href="<?= e(locale_url($language)) ?>" lang="<?= e($languageTag) ?>" hreflang="<?= e($languageTag) ?>"><?= e(Translator::localeName($language)) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="theme-toggle" type="button" data-theme-toggle data-label-light="<?= e(t('theme.toggle')) ?>" data-label-dark="<?= e(t('theme.toggle')) ?>" aria-label="<?= e(t('theme.toggle')) ?>" aria-pressed="false"><span class="theme-toggle__sun" aria-hidden="true">☀</span><span class="theme-toggle__moon" aria-hidden="true">☾</span></button>
            <?php if ($authIdentity && ($authIdentity['account_role'] ?? 'student') !== 'student'): ?><a class="admin-quick-link" href="/admin" aria-label="<?= e(t('nav.admin')) ?>">⚙</a><?php endif; ?>
            <a class="btn btn--primary btn--sm navbar__desktop-account" href="<?= $authIdentity ? '/dashboard' : '/login' ?>"><?= e($authIdentity ? ($authIdentity['name'] ?? t('nav.account')) : t('nav.login')) ?></a>
            <button class="navbar__toggle" type="button" data-navbar-toggle aria-label="<?= e(t('nav.open')) ?>" aria-expanded="false"><span></span></button>
        </div>
    </div>
</header>

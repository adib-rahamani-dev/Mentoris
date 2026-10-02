<?php

use App\Core\Translator;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$authIdentity = (new \App\Core\Session())->get('auth.user');
$notificationCount=0;
if (!empty($authIdentity['id'])) {
    try {
        $notificationQuery=\App\Core\Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=:id AND read_at IS NULL');
        $notificationQuery->execute(['id'=>$authIdentity['id']]); $notificationCount=(int)$notificationQuery->fetchColumn();
    } catch (\PDOException) { $notificationCount=0; }
}
$accountPaths = ['/dashboard', '/profile', '/my-courses', '/my-events', '/my-certificates', '/notifications', '/orders'];
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand-logo" href="/" aria-label="<?= e(t('brand.home')) ?>">
            <img class="brand-logo__mark" src="<?= asset('icons/favicon.svg') ?>" alt="" width="40" height="40">
            <span class="brand-logo__text"><strong>Mentoris</strong><small>Academy</small></span>
        </a>

        <nav class="navbar" data-navbar aria-label="<?= e(t('nav.home')) ?>">
            <a class="navbar__link <?= $currentPath === '/' ? 'is-active' : '' ?>" href="/"><?= e(t('nav.home')) ?></a>
            <div class="dropdown">
                <button class="dropdown__trigger <?= str_starts_with($currentPath, '/programs') || str_starts_with($currentPath, '/courses') || str_starts_with($currentPath, '/academy') || str_starts_with($currentPath, '/specializations') ? 'is-active' : '' ?>" type="button" aria-expanded="false"><?= e(t('nav.academy')) ?> <?= icon('chevron-down', 'ui-icon--sm') ?></button>
                <div class="dropdown__menu">
                    <a class="dropdown__item" href="/courses"><?= e(t('nav.courses')) ?></a>
                    <a class="dropdown__item" href="/programs"><?= e(t('nav.programs')) ?></a>
                    <a class="dropdown__item" href="/academy"><?= e(t('nav.lines')) ?></a>
                    <a class="dropdown__item" href="/specializations"><?= e(t('nav.specializations')) ?></a>
                </div>
            </div>
            <a class="navbar__link <?= str_starts_with($currentPath, '/events') ? 'is-active' : '' ?>" href="/events"><?= e(t('nav.events')) ?></a>
            <a class="navbar__link <?= str_starts_with($currentPath, '/articles') ? 'is-active' : '' ?>" href="/articles"><?= e(t('nav.articles')) ?></a>
            <a class="navbar__link <?= str_starts_with($currentPath, '/community') ? 'is-active' : '' ?>" href="/community"><?= e(t('nav.community')) ?></a>
            <a class="navbar__link <?= $currentPath === '/mentors' ? 'is-active' : '' ?>" href="/mentors"><?= e(t('nav.mentors')) ?></a>
            <a class="navbar__link <?= $currentPath === '/about' ? 'is-active' : '' ?>" href="/about"><?= e(t('nav.about')) ?></a>
            <a class="navbar__link <?= $currentPath === '/founder' ? 'is-active' : '' ?>" href="/founder"><?= e(t('nav.founder')) ?></a>
            <a class="navbar__link <?= $currentPath === '/contact' ? 'is-active' : '' ?>" href="/contact"><?= e(t('nav.contact')) ?></a>
            <a class="navbar__link navbar__account-link <?= in_array($currentPath, $accountPaths, true) ? 'is-active' : '' ?>" href="<?= $authIdentity ? '/dashboard' : '/login' ?>"><?= e($authIdentity ? t('nav.account') : t('nav.login')) ?></a>
        </nav>

        <div class="navbar__actions">
            <?php if($authIdentity):?><a class="navbar-notifications" href="/notifications" aria-label="اعلان‌ها، <?= $notificationCount ?> خوانده‌نشده"><?= icon('bell') ?><?php if($notificationCount):?><b><?= $notificationCount>99 ? '99+' : $notificationCount ?></b><?php endif;?></a><?php endif;?>
            <div class="language-switcher dropdown">
                <button class="language-switcher__trigger dropdown__trigger" type="button" aria-expanded="false" aria-label="<?= e(t('language.select')) ?>"><?= icon('globe') ?><b><?= e(strtoupper(locale())) ?></b></button>
                <div class="dropdown__menu language-switcher__menu">
                    <?php foreach (Translator::SUPPORTED as $language): ?>
                        <?php $languageTag = $language === 'ku' ? 'ckb' : $language; ?>
                        <a class="dropdown__item <?= locale() === $language ? 'is-active' : '' ?>" href="<?= e(locale_url($language)) ?>" lang="<?= e($languageTag) ?>" hreflang="<?= e($languageTag) ?>"><?= e(Translator::localeName($language)) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="theme-toggle" type="button" data-theme-toggle data-label-light="<?= e(t('theme.toggle')) ?>" data-label-dark="<?= e(t('theme.toggle')) ?>" aria-label="<?= e(t('theme.toggle')) ?>" aria-pressed="false"><span class="theme-toggle__sun"><?= icon('sun') ?></span><span class="theme-toggle__moon"><?= icon('moon') ?></span></button>
            <?php if ($authIdentity && ($authIdentity['account_role'] ?? 'student') !== 'student'): ?><a class="admin-quick-link" href="/admin" aria-label="<?= e(t('nav.admin')) ?>"><?= icon('settings') ?></a><?php endif; ?>
            <a class="btn btn--primary btn--sm navbar__desktop-account" href="<?= $authIdentity ? '/dashboard' : '/login' ?>"><?= e($authIdentity ? ($authIdentity['name'] ?? t('nav.account')) : t('nav.login')) ?></a>
            <button class="navbar__toggle" type="button" data-navbar-toggle aria-label="<?= e(t('nav.open')) ?>" aria-expanded="false"><span></span></button>
        </div>
    </div>
</header>

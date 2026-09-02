<!doctype html>
<html lang="fa" dir="rtl" data-admin>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050914">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($title ?? 'Mentoris Admin') ?></title>
    <script nonce="<?= e(\App\Core\Security::cspNonce()) ?>">try{document.documentElement.dataset.theme=localStorage.getItem('mentoris-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark')}catch(e){document.documentElement.dataset.theme='dark'}</script>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=10.0.0">
    <script type="module" src="<?= asset('js/app.js') ?>?v=10.0.0" defer></script>
</head>
<body class="admin-body">
    <a class="skip-link" href="#admin-content">رفتن به محتوای مدیریت</a>
    <header class="admin-topbar"><a class="brand-logo" href="/admin"><span class="brand-logo__mark" aria-hidden="true"></span><span class="brand-logo__text"><strong>Mentoris</strong><small>Control Center</small></span></a><div class="admin-topbar__actions"><a class="btn btn--ghost btn--sm" href="/" target="_blank" rel="noopener">مشاهده سایت ↗</a><button class="theme-toggle" type="button" data-theme-toggle data-label-light="فعال‌کردن حالت روشن" data-label-dark="فعال‌کردن حالت تیره" aria-label="تغییر حالت رنگ" aria-pressed="false"><span class="theme-toggle__sun" aria-hidden="true">☀</span><span class="theme-toggle__moon" aria-hidden="true">☾</span></button></div></header>
    <div class="admin-layout">
        <?php require view_path('components/admin/sidebar.php'); ?>
        <main class="admin-main" id="admin-content"><?= $content ?></main>
    </div>
    <div class="toast-region" aria-live="polite" aria-atomic="true"></div>
</body>
</html>

<?php

use App\Services\SeoService;

$seo = SeoService::metadata(
    (string) ($title ?? 'Mentoris Academy'),
    (string) ($description ?? ''),
    [
        'image' => $seoImage ?? null,
        'type' => $seoType ?? 'website',
        'indexable' => $indexable ?? null,
    ]
);
$structuredData = SeoService::structuredData($seo, $structuredData ?? []);
?>
<!doctype html>
<html lang="<?= e(locale_html()) ?>" dir="<?= e(locale_direction()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050914">
    <meta name="color-scheme" content="dark light">
    <meta name="description" content="<?= e($seo['description']) ?>">
    <meta name="robots" content="<?= e($seo['robots']) ?>">
    <meta name="author" content="Mentoris Academy">
    <link rel="canonical" href="<?= e($seo['canonical']) ?>">
    <?php foreach ($seo['alternates'] as $alternate): ?>
        <link rel="alternate" hreflang="<?= e($alternate['language']) ?>" href="<?= e($alternate['url']) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e($seo['x_default']) ?>">
    <meta property="og:site_name" content="Mentoris Academy">
    <meta property="og:type" content="<?= e($seo['type']) ?>">
    <meta property="og:title" content="<?= e($seo['title']) ?>">
    <meta property="og:description" content="<?= e($seo['description']) ?>">
    <meta property="og:url" content="<?= e($seo['canonical']) ?>">
    <meta property="og:image" content="<?= e($seo['image']) ?>">
    <meta property="og:image:alt" content="Mentoris Academy">
    <meta property="og:locale" content="<?= e($seo['locale']) ?>">
    <?php foreach ($seo['locale_alternates'] as $alternateLocale): ?>
        <meta property="og:locale:alternate" content="<?= e($alternateLocale) ?>">
    <?php endforeach; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($seo['title']) ?>">
    <meta name="twitter:description" content="<?= e($seo['description']) ?>">
    <meta name="twitter:image" content="<?= e($seo['image']) ?>">
    <link rel="icon" href="/assets/icons/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="/manifest.json">
    <title><?= e($seo['title']) ?></title>
    <script nonce="<?= e(\App\Core\Security::cspNonce()) ?>">try{document.documentElement.dataset.theme=localStorage.getItem('mentoris-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark')}catch(e){document.documentElement.dataset.theme='dark'}</script>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=10.0.0">
    <script type="module" src="<?= asset('js/app.js') ?>?v=10.0.0" defer></script>
    <script nonce="<?= e(\App\Core\Security::cspNonce()) ?>" type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</head>
<body>
    <a class="skip-link" href="#main-content"><?= e(t('skip')) ?></a>
    <?php require view_path('components/navbar.php'); ?>
    <main id="main-content"><?= $content ?></main>
    <?php require view_path('components/footer.php'); ?>
    <div class="toast-region" aria-live="polite" aria-atomic="true"></div>
</body>
</html>

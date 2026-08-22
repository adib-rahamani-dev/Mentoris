<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050914">
    <meta name="description" content="<?= e($description ?? 'آکادمی تخصصی رشد، یادگیری و سلامت روان Mentoris') ?>">
    <title><?= e($title ?? 'Mentoris') ?></title>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('mentoris-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark')}catch(e){document.documentElement.dataset.theme='dark'}</script>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=8.0.0">
    <script type="module" src="<?= asset('js/app.js') ?>?v=8.0.0" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>
    <?php require view_path('components/navbar.php'); ?>
    <main id="main-content"><?= $content ?></main>
    <?php require view_path('components/footer.php'); ?>
    <div class="toast-region" aria-live="polite" aria-atomic="true"></div>
</body>
</html>

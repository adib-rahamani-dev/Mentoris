<?php
$userPath = parse_url($_SERVER['REQUEST_URI'] ?? '/dashboard', PHP_URL_PATH);
$unread = count(array_filter($user['notifications'] ?? [], fn ($item) => empty($item['read_at'])));
?>
<aside class="user-sidebar">
    <div class="user-sidebar__identity"><span><?= e(mb_substr($user['name'] ?? 'M', 0, 1)) ?></span><div><strong><?= e($user['name'] ?? '') ?></strong><small><?= e($user['email'] ?? '') ?></small></div></div>
    <nav aria-label="منوی ناحیه کاربری">
        <a class="<?= $userPath === '/dashboard' ? 'is-active' : '' ?>" href="/dashboard"><span><?= icon('home') ?></span>نمای کلی</a>
        <a class="<?= $userPath === '/my-courses' ? 'is-active' : '' ?>" href="/my-courses"><span><?= icon('book') ?></span>دوره‌های من</a>
        <a class="<?= $userPath === '/my-events' ? 'is-active' : '' ?>" href="/my-events"><span><?= icon('calendar') ?></span>رویدادهای من</a>
        <a class="<?= $userPath === '/orders' || str_starts_with((string) $userPath, '/orders/') ? 'is-active' : '' ?>" href="/orders"><span><?= icon('package') ?></span>سفارش‌ها</a>
        <a class="<?= $userPath === '/my-certificates' ? 'is-active' : '' ?>" href="/my-certificates"><span><?= icon('certificate') ?></span>گواهی‌های من</a>
        <a class="<?= $userPath === '/notifications' ? 'is-active' : '' ?>" href="/notifications"><span><?= icon('bell') ?></span>اعلان‌ها<?php if ($unread): ?><em><?= $unread ?></em><?php endif; ?></a>
        <a class="<?= $userPath === '/profile' ? 'is-active' : '' ?>" href="/profile"><span><?= icon('user') ?></span>پروفایل</a>
        <?php if (\App\Core\Authorization::can($user, 'admin.access')): ?><a href="/admin"><span><?= icon('settings') ?></span>مرکز مدیریت</a><?php endif; ?>
    </nav>
    <form method="post" action="/logout"><?= csrf_field() ?><button type="submit"><span><?= icon('log-out') ?></span>خروج از حساب</button></form>
</aside>

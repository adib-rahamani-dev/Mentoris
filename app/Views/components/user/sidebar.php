<?php
$userPath = parse_url($_SERVER['REQUEST_URI'] ?? '/dashboard', PHP_URL_PATH);
$unread = count(array_filter($user['notifications'] ?? [], fn ($item) => empty($item['read_at'])));
?>
<aside class="user-sidebar">
    <div class="user-sidebar__identity"><span><?= e(mb_substr($user['name'] ?? 'M', 0, 1)) ?></span><div><strong><?= e($user['name'] ?? '') ?></strong><small><?= e($user['email'] ?? '') ?></small></div></div>
    <nav aria-label="منوی ناحیه کاربری">
        <a class="<?= $userPath === '/dashboard' ? 'is-active' : '' ?>" href="/dashboard"><span>⌂</span>نمای کلی</a>
        <a class="<?= $userPath === '/my-courses' ? 'is-active' : '' ?>" href="/my-courses"><span>▤</span>دوره‌های من</a>
        <a class="<?= $userPath === '/my-events' ? 'is-active' : '' ?>" href="/my-events"><span>◫</span>رویدادهای من</a>
        <a class="<?= $userPath === '/orders' || str_starts_with((string) $userPath, '/orders/') ? 'is-active' : '' ?>" href="/orders"><span>◧</span>سفارش‌ها</a>
        <a class="<?= $userPath === '/my-certificates' ? 'is-active' : '' ?>" href="/my-certificates"><span>◇</span>گواهی‌های من</a>
        <a class="<?= $userPath === '/notifications' ? 'is-active' : '' ?>" href="/notifications"><span>◉</span>اعلان‌ها<?php if ($unread): ?><em><?= $unread ?></em><?php endif; ?></a>
        <a class="<?= $userPath === '/profile' ? 'is-active' : '' ?>" href="/profile"><span>◎</span>پروفایل</a>
    </nav>
    <form method="post" action="/logout"><?= csrf_field() ?><button type="submit"><span>←</span>خروج از حساب</button></form>
</aside>

<?php
use App\Core\Authorization;
$adminPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin';
?>
<aside class="admin-sidebar">
    <div class="admin-identity"><span><?= e(mb_substr($admin['name'] ?? 'M', 0, 1)) ?></span><div><strong><?= e($admin['name'] ?? '') ?></strong><small><?= e(Authorization::roleLabel(Authorization::role($admin))) ?></small></div></div>
    <nav aria-label="منوی مدیریت">
        <a class="<?= $adminPath === '/admin' ? 'is-active' : '' ?>" href="/admin"><span aria-hidden="true">⌂</span>نمای کلی</a>
        <?php if (Authorization::can($admin, 'users.view')): ?><a class="<?= $adminPath === '/admin/users' ? 'is-active' : '' ?>" href="/admin/users"><span aria-hidden="true">◎</span>کاربران و نقش‌ها</a><?php endif; ?>
        <?php if (Authorization::can($admin, 'content.view')): ?><a class="<?= $adminPath === '/admin/content' ? 'is-active' : '' ?>" href="/admin/content"><span aria-hidden="true">▤</span>محتوای سایت</a><?php endif; ?>
        <?php if (Authorization::can($admin, 'analytics.view')): ?><a class="<?= $adminPath === '/admin/analytics' ? 'is-active' : '' ?>" href="/admin/analytics"><span aria-hidden="true">⌁</span>آمار و گزارش‌ها</a><?php endif; ?>
    </nav>
    <div class="admin-sidebar__footer"><a href="/dashboard">پنل کاربری</a><form method="post" action="/logout"><?= csrf_field() ?><button type="submit">خروج امن</button></form></div>
</aside>

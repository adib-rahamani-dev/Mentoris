<?php
use App\Core\Authorization;
$adminPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin', '/') ?: '/admin';
$active = static fn (string $path): string => $adminPath === $path || ($path !== '/admin' && str_starts_with($adminPath, $path . '/')) ? 'is-active' : '';
?>
<aside class="admin-sidebar" data-admin-sidebar>
    <div class="admin-identity"><span><?= e(mb_substr($admin['name'] ?? 'M', 0, 1)) ?></span><div><strong><?= e($admin['name'] ?? '') ?></strong><small><?= e(Authorization::roleLabel(Authorization::role($admin))) ?></small></div></div>
    <nav aria-label="منوی مدیریت">
        <small class="admin-nav-label">نمای کلی</small>
        <a class="<?= $active('/admin') ?>" href="/admin"><span aria-hidden="true">⌂</span>داشبورد</a>
        <?php if (Authorization::can($admin, 'analytics.view')): ?><a class="<?= $active('/admin/analytics') ?>" href="/admin/analytics"><span aria-hidden="true">⌁</span>تحلیل و گزارش‌ها</a><?php endif; ?>
        <small class="admin-nav-label">عملیات</small>
        <?php if (Authorization::can($admin, 'users.view')): ?><a class="<?= $active('/admin/users') ?>" href="/admin/users"><span aria-hidden="true">◎</span>کاربران و نقش‌ها</a><?php endif; ?>
        <?php if (Authorization::can($admin, 'orders.view')): ?><a class="<?= $active('/admin/orders') ?>" href="/admin/orders"><span aria-hidden="true">◇</span>سفارش‌ها و پرداخت</a><?php endif; ?>
        <?php if (Authorization::can($admin, 'engagements.view')): ?><a class="<?= $active('/admin/engagements') ?>" href="/admin/engagements"><span aria-hidden="true">✦</span>مرکز ارتباطات</a><?php endif; ?>
        <?php if (Authorization::can($admin, 'content.view')): ?><a class="<?= $active('/admin/content') ?>" href="/admin/content"><span aria-hidden="true">▤</span>محتوای سایت</a><?php endif; ?>
        <?php if (Authorization::can($admin, 'audit.view')): ?><small class="admin-nav-label">نظارت</small><a class="<?= $active('/admin/audit') ?>" href="/admin/audit"><span aria-hidden="true">◉</span>گزارش فعالیت‌ها</a><?php endif; ?>
        <?php if (Authorization::can($admin, 'system.view')): ?><a class="<?= $active('/admin/system') ?>" href="/admin/system"><span aria-hidden="true">⚙</span>امنیت و سلامت سیستم</a><?php endif; ?>
    </nav>
    <div class="admin-sidebar__footer"><a href="/dashboard">پنل کاربری</a><form method="post" action="/logout"><?= csrf_field() ?><button type="submit">خروج امن</button></form></div>
</aside>

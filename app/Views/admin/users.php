<?php use App\Core\Authorization; ?>
<header class="admin-page-head">
    <div><span class="eyebrow">Users & Access</span><h1>کاربران و نقش‌ها</h1><p>جست‌وجو، مشاهده پرونده، نقش‌بندی و کنترل وضعیت همه حساب‌ها.</p></div>
    <span class="admin-count"><?= number_format($summary['filtered']) ?> نتیجه از <?= number_format($summary['total']) ?></span>
</header>

<section class="admin-kpis" aria-label="خلاصه کاربران">
    <div><span>کل کاربران</span><strong><?= number_format($summary['total']) ?></strong><small>همه حساب‌های ثبت‌شده</small></div>
    <div><span>نمایش فعلی</span><strong><?= number_format($summary['filtered']) ?></strong><small>مطابق فیلتر انتخابی</small></div>
    <div><span>حساب فعال در نتیجه</span><strong><?= number_format($summary['active']) ?></strong><small>امکان ورود به سامانه</small></div>
    <div><span>نقش‌های قابل مدیریت</span><strong><?= number_format(count($roles)) ?></strong><small>از کاربر تا مدیرکل</small></div>
</section>

<?php if (isset($_GET['updated'])): ?><div class="alert alert--success" role="status">دسترسی کاربر با موفقیت به‌روزرسانی شد.</div><?php endif; ?>
<?php if (($_GET['error'] ?? '') === 'self-access'): ?><div class="alert alert--danger" role="alert">برای جلوگیری از قفل‌شدن پنل، نمی‌توانید دسترسی حساب فعلی را کاهش دهید.</div><?php endif; ?>

<form class="admin-filters" method="get" action="/admin/users">
    <label><span class="sr-only">جست‌وجو</span><input class="form-control" type="search" name="q" value="<?= e($filters['query']) ?>" placeholder="نام، ایمیل یا شماره تماس..."></label>
    <label><span class="sr-only">نقش</span><select class="form-select" name="role"><option value="all">همه نقش‌ها</option><?php foreach ($roles as $key => $label): ?><option value="<?= e($key) ?>" <?= $filters['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
    <label><span class="sr-only">وضعیت</span><select class="form-select" name="status"><option value="all">همه وضعیت‌ها</option><option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>فعال</option><option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : '' ?>>تعلیق‌شده</option></select></label>
    <button class="btn btn--primary" type="submit">اعمال فیلتر</button>
</form>

<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>کاربر</th><th>نقش</th><th>وضعیت</th><th>عضویت</th><th>آخرین ورود</th><th></th></tr></thead><tbody>
<?php foreach ($users as $user): ?><tr>
    <td><a class="table-user" href="/admin/users/<?= e($user['id']) ?>"><span><?= e(mb_substr($user['name'], 0, 1)) ?></span><div><strong><?= e($user['name']) ?></strong><small class="ltr"><?= e($user['email']) ?></small><?php if ($user['phone']): ?><small class="ltr"><?= e($user['phone']) ?></small><?php endif; ?></div></a></td>
    <td><?= e(Authorization::roleLabel(Authorization::role($user))) ?></td>
    <td><span class="order-status order-status--<?= e($user['status']) ?>"><?= $user['status'] === 'active' ? 'فعال' : 'تعلیق‌شده' ?></span></td>
    <td class="ltr"><?= e(substr((string) $user['created_at'], 0, 10)) ?></td>
    <td class="ltr"><?= e($user['last_login_at'] ? substr((string) $user['last_login_at'], 0, 16) : '—') ?></td>
    <td><a class="table-action" href="/admin/users/<?= e($user['id']) ?>">پرونده کامل ←</a></td>
</tr><?php endforeach; ?>
<?php if (!$users): ?><tr><td colspan="6"><div class="content-empty"><span class="content-empty__icon">⌕</span><div><h3>کاربری پیدا نشد</h3><p>فیلترها یا عبارت جست‌وجو را تغییر دهید.</p></div></div></td></tr><?php endif; ?>
</tbody></table></div>

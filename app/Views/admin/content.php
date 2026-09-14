<?php use App\Core\Authorization; $canManageContent=Authorization::can($admin,'content.manage'); ?>
<header class="admin-page-head">
    <div><span class="eyebrow">Content Studio</span><h1>استودیوی محتوای منتوریس</h1><p>ساخت، ترجمه، انتشار و کنترل یکپارچه محتوای سایت در چهار زبان.</p></div>
    <?php if($canManageContent): ?><div class="admin-head-actions"><a class="btn btn--secondary" href="/admin/content/new?type=mentor"><?=icon('user')?> مدرس جدید</a><a class="btn btn--secondary" href="/admin/content/new?type=article"><?=icon('file')?> مقاله جدید</a><a class="btn btn--primary" href="/admin/content/new"><?=icon('activity')?> محتوای جدید</a></div><?php endif; ?>
</header>

<?php $moduleIcons=['academy'=>'brain','specializations'=>'certificate','events'=>'calendar','courses'=>'book','programs'=>'trending','experts'=>'users','articles'=>'file']; ?>
<div class="admin-content-grid"><?php foreach ($modules as $module): ?><article class="admin-content-card"><div><span class="admin-content-card__icon" aria-hidden="true"><?= icon($moduleIcons[$module['key']] ?? 'activity') ?></span><span class="content-status content-status--<?= e($module['status']) ?>"><?= $module['status'] === 'published' ? 'منتشرشده' : 'به‌زودی' ?></span></div><h2><?= e($module['title']) ?></h2><p><strong><?= number_format($module['count']) ?></strong> مورد عمومی</p><a class="btn btn--ghost btn--sm" href="<?= e($module['url']) ?>" target="_blank" rel="noopener">مشاهده در سایت <?= icon('arrow-up-left') ?></a></article><?php endforeach; ?></div>

<section class="admin-panel mt-8">
    <header><div><span class="eyebrow">Database content</span><h2>محتوای قابل مدیریت</h2></div><span class="admin-count"><?= number_format($pagination['total']) ?> مورد</span></header>
    <form class="admin-filters admin-filters--compact" method="get">
        <label><span class="sr-only">جست‌وجو</span><input class="form-control" name="q" value="<?= e($filters['query']) ?>" placeholder="عنوان یا نامک..."></label>
        <label><select class="form-select" name="type"><option value="all">همه نوع‌ها</option><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>" <?= $filters['type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <label><select class="form-select" name="status"><option value="all">همه وضعیت‌ها</option><option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option><option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>منتشرشده</option><option value="archived" <?= $filters['status'] === 'archived' ? 'selected' : '' ?>>بایگانی</option></select></label>
        <button class="btn btn--secondary">فیلتر</button>
    </form>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>عنوان</th><th>نوع</th><th>ترجمه</th><th>وضعیت</th><th>ویرایش</th><th></th></tr></thead><tbody>
    <?php foreach ($items as $item): $previewBase=['academy_line'=>'/academy/','specialization'=>'/specializations/','program'=>'/programs/','course'=>'/courses/','event'=>'/events/','mentor'=>'/mentors/','article'=>'/articles/'][$item['entity_type']]??null; ?><tr><td><strong><?= e($item['title'] ?: $item['slug']) ?></strong><small class="table-sub ltr"><?= e($item['slug']) ?></small></td><td><?= e($types[$item['entity_type']] ?? $item['entity_type']) ?></td><td><?= number_format((int) $item['translation_count']) ?> از ۴</td><td><span class="content-status content-status--<?= e($item['status']) ?>"><?= e($item['status']) ?></span></td><td class="ltr"><?= e(substr($item['updated_at'], 0, 16)) ?></td><td><div class="cluster"><?php if($previewBase&&$item['status']==='published'):?><a class="table-action" href="<?=e($previewBase.$item['slug'])?>" target="_blank" rel="noopener">نمایش</a><?php endif;?><?php if($canManageContent): ?><a class="table-action" href="/admin/content/<?= e($item['id']) ?>/edit">ویرایش <?=icon('arrow-left')?></a><?php endif; ?></div></td></tr><?php endforeach; ?>
    <?php if (!$items): ?><tr><td colspan="6"><div class="content-empty"><span class="content-empty__icon"><?= icon('file', 'ui-icon--lg') ?></span><div><h3>هنوز محتوای دیتابیسی ندارید</h3><p>اولین صفحه، دوره، رویداد یا مقاله را از دکمه «محتوای جدید» بسازید.</p></div></div></td></tr><?php endif; ?>
    </tbody></table></div>
    <?php require view_path('components/admin/pagination.php'); ?>
</section>

<section class="admin-panel mt-8"><header><div><span class="eyebrow">Publishing policy</span><h2>قاعده انتشار منتوریس</h2></div></header><ul class="check-list"><li>هیچ محتوای ساختگی یا عدد نمایشی منتشر نمی‌شود.</li><li>بخش بدون محتوای تأییدشده، وضعیت استاندارد «به‌زودی» دارد.</li><li>وضعیت پیش‌نویس در موتورهای جست‌وجو نمایش داده نمی‌شود.</li><li>هر تغییر مهم با نام مدیر در Audit Log ثبت می‌شود.</li></ul></section>

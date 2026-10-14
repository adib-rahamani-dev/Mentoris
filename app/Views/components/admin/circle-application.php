<?php $application=(new \App\Repositories\CircleRepository())->application($item['id']); if($application): ?>
<details class="support-faq"><summary>سوابق و رزومه متقاضی</summary><dl>
<?php foreach(['education'=>'مقطع','university'=>'دانشگاه','specialization'=>'رشته و گرایش','experience'=>'سابقه بالینی','approach'=>'رویکرد','practice_areas'=>'حوزه فعالیت','professional_number'=>'شماره نظام / دانشجویی','motivation'=>'توضیحات'] as $key=>$label): ?>
<dt><?= e($label) ?></dt><dd><?= nl2br(e($application['details'][$key]??'—')) ?></dd>
<?php endforeach; ?></dl><?php if(!empty($application['resume'])): ?><a class="btn btn--secondary" href="/admin/circle-applications/<?= e($item['id']) ?>/resume">دانلود رزومه</a><?php else: ?><p class="form-hint">رزومه‌ای ارسال نشده است (اختیاری).</p><?php endif; ?></details>
<?php endif; ?>

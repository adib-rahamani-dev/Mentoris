<?php
$courseStatusLabels = \App\Services\PublicContentService::courseStatusLabels();
$courseStatus = $course['status'] ?? 'active';
$courseStatusLabel = $course['status_label'] ?? ($courseStatusLabels[$courseStatus] ?? $courseStatus);
$courseCapacity = (int) ($course['capacity'] ?? 0);
$courseEnrolled = (int) ($course['enrolled'] ?? 0);
$courseAvailable = $course['available'] ?? max(0, $courseCapacity - $courseEnrolled);
?>
<article class="card program-card course-card course-card--<?= e($courseStatus) ?>">
    <div class="card__media program-card__media program-card__media--<?= e($course['tone']) ?>">
        <?php if(!empty($course['image'])): ?><img class="course-card__poster" src="<?= asset($course['image']) ?>" alt="پوستر <?= e($course['title']) ?>" width="1024" height="1536" loading="lazy"><?php endif; ?>
        <span class="badge badge--neutral card__badge"><?= e($course['category']) ?></span>
        <span class="course-status course-status--<?= e($courseStatus) ?>"><?= e($courseStatusLabel) ?></span>
        <?php if(empty($course['image'])): ?><span class="program-card__symbol"><?= icon('book', 'ui-icon--lg') ?></span><?php endif; ?>
    </div>
    <div class="card__body"><h3 class="card__title"><a href="/courses/<?= e($course['slug']) ?>"><?= e($course['title']) ?></a></h3><p class="program-card__subtitle ltr"><?= e($course['subtitle']) ?></p><p class="card__text course-card__description"><?= e($course['description'] ?? '') ?></p><div class="card__meta"><span><?= icon('clock') ?> <?= e($course['duration']) ?></span><span><?= icon('trending') ?> <?= e($course['level']) ?></span><span><?= icon('users') ?> <?= e($course['type'] ?? '') ?></span></div></div>
    <div class="course-card__availability"><span><?= ($course['registration_mode'] ?? '')==='request' ? 'پرداخت نقدی و اقساطی · ثبت درخواست' : ($courseStatus === 'active' ? e((string) $courseAvailable) . ' جای خالی' : e($courseStatusLabel)) ?></span><small><?= e($course['schedule'] ?? '') ?></small></div>
    <div class="card__footer"><span class="course-price"><?= e($course['price']) ?></span><a class="btn btn--secondary btn--sm" href="/courses/<?= e($course['slug']) ?>">مشاهده دوره</a></div>
</article>

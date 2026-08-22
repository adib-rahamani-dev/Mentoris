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
        <span class="badge badge--neutral card__badge"><?= e($course['category']) ?></span>
        <span class="course-status course-status--<?= e($courseStatus) ?>"><?= e($courseStatusLabel) ?></span>
        <span class="program-card__symbol" aria-hidden="true">⌁</span>
    </div>
    <div class="card__body"><h3 class="card__title"><a href="/courses/<?= e($course['slug']) ?>"><?= e($course['title']) ?></a></h3><p class="program-card__subtitle ltr"><?= e($course['subtitle']) ?></p><p class="card__text course-card__description"><?= e($course['description'] ?? '') ?></p><div class="card__meta"><span>◷ <?= e($course['duration']) ?></span><span>◈ <?= e($course['level']) ?></span><span>⌾ <?= e($course['type'] ?? '') ?></span></div></div>
    <div class="course-card__availability"><span><?= $courseStatus === 'active' ? e((string) $courseAvailable) . ' جای خالی' : e($courseStatusLabel) ?></span><small><?= e($course['schedule'] ?? '') ?></small></div>
    <div class="card__footer"><span class="course-price"><?= e($course['price']) ?></span><a class="btn btn--secondary btn--sm" href="/courses/<?= e($course['slug']) ?>">مشاهده دوره</a></div>
</article>

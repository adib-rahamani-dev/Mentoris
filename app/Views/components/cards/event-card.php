<?php
$statusLabels = \App\Services\PublicContentService::eventStatusLabels();
$modeLabels = \App\Services\PublicContentService::eventModeLabels();
$status = $event['status'] ?? 'upcoming';
$statusLabel = $event['status_label'] ?? ($statusLabels[$status] ?? $status);
$mode = $event['mode'] ?? 'offline';
$modeLabel = $event['mode_label'] ?? ($modeLabels[$mode] ?? $mode);
$capacity = (int) ($event['capacity'] ?? 0);
$registered = (int) ($event['registered'] ?? 0);
$available = $event['available'] ?? max(0, $capacity - $registered);
$progress = $capacity > 0 ? min(100, (int) round(($registered / $capacity) * 100)) : 0;
?>
<article class="card event-card event-card--<?= e($status) ?>">
    <div class="event-card__visual event-card__visual--<?= e($event['tone'] ?? 'violet') ?>">
        <span><?= e($event['day']) ?></span><small><?= e($event['month']) ?></small>
        <em class="event-status event-status--<?= e($status) ?>"><?= e($statusLabel) ?></em>
    </div>
    <div class="card__body">
        <div class="event-card__tags"><span class="badge badge--neutral"><?= e($event['type']) ?></span><span class="event-mode"><?= e($modeLabel) ?></span></div>
        <h3 class="card__title mt-4"><a href="/events/<?= e($event['slug']) ?>"><?= e($event['title']) ?></a></h3>
        <p class="card__text"><?= e($event['short_description'] ?? '') ?></p>
        <div class="event-card__meta"><span>◷ <?= e($event['date']) ?></span><span>⌖ <?= e($event['location'] ?? 'Mentoris') ?></span></div>
        <div class="event-capacity" aria-label="<?= e($registered) ?> نفر از <?= e($capacity) ?> نفر ثبت‌نام کرده‌اند"><div><span>ظرفیت</span><strong><?= e((string) $available) ?> جای خالی</strong></div><div class="event-capacity__track"><span style="width:<?= $progress ?>%"></span></div></div>
    </div>
    <div class="card__footer"><span class="muted"><?= e($event['instructor']['name'] ?? 'آکادمی Mentoris') ?></span><a class="btn btn--secondary btn--sm" href="/events/<?= e($event['slug']) ?>"><?= $status === 'registration-open' ? 'ثبت‌نام' : 'جزئیات' ?></a></div>
</article>

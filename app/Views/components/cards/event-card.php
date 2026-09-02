<?php
$statusLabels = \App\Services\PublicContentService::eventStatusLabels();
$modeLabels = \App\Services\PublicContentService::eventModeLabels();
$status = $event['status'] ?? 'upcoming';
$statusLabel = $event['status_label'] ?? ($statusLabels[$status] ?? $status);
$mode = $event['mode'] ?? 'offline';
$modeLabel = $event['mode_label'] ?? ($modeLabels[$mode] ?? $mode);
$capacity = (int) ($event['capacity'] ?? 0);
$registered = (int) ($event['registered'] ?? 0);
$available = $event['available'] ?? ($capacity > 0 ? max(0, $capacity - $registered) : null);
$progress = $capacity > 0 ? min(100, (int) round(($registered / $capacity) * 100)) : 0;
$cardCopy = [
    'fa'=>['capacity'=>'ظرفیت','limited'=>'ظرفیت محدود','seats'=>'جای خالی','register'=>'ثبت‌نام','details'=>'جزئیات'],
    'ar'=>['capacity'=>'السعة','limited'=>'سعة محدودة','seats'=>'مقاعد متاحة','register'=>'التسجيل','details'=>'التفاصيل'],
    'ku'=>['capacity'=>'شوێن','limited'=>'شوێنی سنووردار','seats'=>'شوێنی بەتاڵ','register'=>'تۆمارکردن','details'=>'وردەکاری'],
    'en'=>['capacity'=>'Capacity','limited'=>'Limited capacity','seats'=>'seats available','register'=>'Register','details'=>'Details'],
][locale()];
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
        <div class="event-capacity" aria-label="<?= e($capacity > 0 ? ((string)$available . ' ' . $cardCopy['seats']) : $cardCopy['limited']) ?>"><div><span><?= e($cardCopy['capacity']) ?></span><strong><?= e($capacity > 0 ? ((string)$available . ' ' . $cardCopy['seats']) : $cardCopy['limited']) ?></strong></div><?php if ($capacity > 0): ?><div class="event-capacity__track"><span style="width:<?= $progress ?>%"></span></div><?php endif; ?></div>
    </div>
    <div class="card__footer"><span class="muted"><?= e($event['instructor']['name'] ?? 'Mentoris Academy') ?></span><a class="btn btn--secondary btn--sm" href="/events/<?= e($event['slug']) ?>"><?= e($status === 'registration-open' ? $cardCopy['register'] : $cardCopy['details']) ?></a></div>
</article>

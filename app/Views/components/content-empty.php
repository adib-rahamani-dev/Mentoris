<?php
$emptyTitle = $emptyTitle ?? t('empty.title');
$emptyText = $emptyText ?? t('empty.text');
$emptyIcon = $emptyIcon ?? '✦';
?>
<div class="content-empty" role="status" data-reveal>
    <span class="content-empty__icon" aria-hidden="true"><?= e($emptyIcon) ?></span>
    <div><h3><?= e($emptyTitle) ?></h3><p><?= e($emptyText) ?></p></div>
</div>
<?php unset($emptyTitle, $emptyText, $emptyIcon); ?>

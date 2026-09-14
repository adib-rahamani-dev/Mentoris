<?php
$emptyTitle = $emptyTitle ?? t('empty.title');
$emptyText = $emptyText ?? t('empty.text');
$emptyIcon = $emptyIcon ?? 'activity';
?>
<div class="content-empty" role="status" data-reveal>
    <span class="content-empty__icon"><?= icon($emptyIcon, 'ui-icon--lg') ?></span>
    <div><h3><?= e($emptyTitle) ?></h3><p><?= e($emptyText) ?></p></div>
</div>
<?php unset($emptyTitle, $emptyText, $emptyIcon); ?>

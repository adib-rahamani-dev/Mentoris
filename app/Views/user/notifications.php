<?php $notifications=$user['notifications']??[]; ?>
<section class="user-area"><div class="container user-layout">
<?php require view_path('components/user/sidebar.php'); ?>
<div class="user-main">
<header class="user-page-head"><div><span class="eyebrow">پیام‌های حساب</span><h1>اعلان‌ها</h1><p>وضعیت رسیدهای پرداخت، ثبت‌نام‌ها و پیام‌های مهم شما.</p></div>
<?php if($unread): ?><form method="post" action="/notifications/read-all"><?= csrf_field() ?><button class="btn btn--secondary btn--sm">خواندم؛ همه اعلان‌ها</button></form><?php endif; ?></header>
<div class="notification-summary" role="status"><strong><?= (int)$unread ?> اعلان خوانده‌نشده</strong><span>اعلان‌ها تا زمانی که «خواندم» را بزنید، در این فهرست باقی می‌مانند.</span></div>
<div class="notification-list">
<?php foreach($notifications as $notification):
$paymentTarget=match($notification['title']){
'رسید پرداخت تازه'=>\App\Core\Authorization::can($user,'payments.manage')?'/admin/manual-payments':null,
'رسید پرداخت دریافت شد','بررسی رسید پرداخت'=>'/payments',
default=>null,
}; ?>
<article class="notification-item <?= empty($notification['read_at'])?'is-unread':'' ?>">
<span aria-hidden="true"><?= icon($paymentTarget?'card':'bell') ?></span>
<div class="notification-item__body"><h2><?= e($notification['title']) ?></h2><p><?= e($notification['message']) ?></p><small><time datetime="<?= e(str_replace(' ','T',$notification['created_at']).'Z') ?>"><?= e((new DateTimeImmutable($notification['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Tehran'))->format('Y/m/d · H:i')) ?></time></small>
<?php if($paymentTarget): ?><a class="notification-action" href="<?= e($paymentTarget) ?>"><?= $paymentTarget==='/payments'?'مشاهده وضعیت پرداخت':'بررسی رسیدها' ?> <?= icon('arrow-up-left') ?></a><?php endif; ?>
</div>
<?php if(empty($notification['read_at'])): ?><form method="post" action="/notifications/<?= e($notification['id']) ?>/read"><?= csrf_field() ?><button class="btn btn--ghost btn--sm">خواندم</button></form><?php else: ?><small>خوانده‌شده</small><?php endif; ?>
</article>
<?php endforeach; ?>
<?php if(!$notifications): ?><div class="user-empty"><span><?= icon('bell','ui-icon--lg') ?></span><h2>اعلان جدیدی ندارید</h2><p>نتیجه بررسی رسید و پیام‌های مهم منتوریس اینجا نمایش داده می‌شود.</p></div><?php endif; ?>
</div></div></div></section>
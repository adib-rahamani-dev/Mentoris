<section class="stream-page section">
    <div class="container stream-page__layout">
        <div class="stream-page__intro">
            <span class="eyebrow">MENTORIS / LIVE</span>
            <h1>اینجا، برای گفت‌وگوی زنده کنار هم هستیم.</h1>
            <p>برنامه‌های آنلاین منتوریس از همین صفحه پخش می‌شوند. زمان و جزئیات برنامه بعدی از مسیرهای رسمی منتوریس اعلام خواهد شد.</p>
            <div class="stream-page__actions"><a class="btn btn--primary" href="/events">رویدادهای منتوریس <?= icon('arrow-left') ?></a><a class="btn btn--secondary" href="/community">جامعه منتوریس</a></div>
        </div>
        <div class="stream-stage">
            <?php if ($streamStatus === 'live' && $embedUrl !== ''): ?>
                <div class="stream-stage__status stream-stage__status--live"><span aria-hidden="true"></span> پخش زنده</div>
                <div class="stream-stage__player"><iframe src="<?= e($embedUrl) ?>" title="پخش زنده منتوریس" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
                <a class="stream-stage__fallback" href="<?= e($fallbackUrl) ?>" target="_blank" rel="noopener noreferrer">اگر پخش باز نمی‌شود، از صفحه اصلی پخش ببینید <?= icon('arrow-up-left') ?></a>
            <?php else: ?>
                <div class="stream-stage__waiting"><span class="stream-stage__symbol" aria-hidden="true">م</span><span class="stream-stage__status"><?= $streamStatus === 'ended' ? 'پخش پایان یافت' : 'در انتظار برنامه بعدی' ?></span><h2><?= $streamStatus === 'ended' ? 'از همراهی شما سپاسگزاریم.' : 'به‌زودی اینجا می‌بینیمتان.' ?></h2><p>در زمان پخش، تصویر برنامه در همین قاب نمایش داده می‌شود.</p></div>
            <?php endif; ?>
        </div>
    </div>
</section>

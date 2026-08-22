<article class="card article-card">
    <div class="article-card__visual article-card__visual--<?= e($article['tone']) ?>"><span aria-hidden="true">∿</span></div>
    <div class="card__body"><span class="badge badge--neutral"><?= e($article['type']) ?></span><h3 class="card__title mt-4"><?= e($article['title']) ?></h3><div class="card__meta"><span>مطالعه: <?= e($article['read']) ?></span></div></div>
    <div class="card__footer"><a class="brand" href="#">مطالعه مطلب ←</a></div>
</article>

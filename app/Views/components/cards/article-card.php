<?php $articleTopic = strtolower(explode('-', (string) ($article['slug'] ?? 'general'))[0]); ?>
<article class="card article-card">
    <div class="article-card__visual article-card__visual--<?= e($articleTopic) ?>"><?php if (!empty($article['image'])): ?><img src="<?= asset($article['image']) ?>" alt="" width="800" height="520" loading="lazy"><?php else: ?><span class="article-card__visual-label">MENTORIS JOURNAL</span><strong><?= e($article['type']) ?></strong><?php endif; ?></div>
    <div class="card__body"><span class="badge badge--neutral"><?= e($article['type']) ?></span><h3 class="card__title mt-4"><a href="/articles/<?= e($article['slug']) ?>"><?= e($article['title']) ?></a></h3><p class="card__text"><?= e($article['excerpt'] ?? '') ?></p><div class="card__meta"><span><?= icon('clock') ?> <?= e($article['read']) ?></span><span><?= e($article['author'] ?? 'Mentoris') ?></span></div></div>
    <div class="card__footer"><a class="brand" href="/articles/<?= e($article['slug']) ?>"><?= e(t('nav.articles')) ?> <?= icon('arrow-left') ?></a></div>
</article>

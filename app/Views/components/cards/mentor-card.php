<article class="card mentor-card">
    <div class="mentor-card__portrait mentor-card__portrait--<?= e($mentor['tone']) ?>"><?php if (!empty($mentor['image'])): ?><img src="<?= asset($mentor['image']) ?>" alt="<?= e($mentor['name']) ?>" width="1024" height="1536" loading="lazy"><?php else: ?><span><?= e($mentor['initials']) ?></span><?php endif; ?></div>
    <div class="card__body center"><h3 class="card__title"><?= e($mentor['name']) ?></h3><p class="card__text"><?= e($mentor['role']) ?></p><span class="badge badge--neutral mt-4"><?= e($mentor['specialty']) ?></span></div>
    <div class="card__footer"><a class="btn btn--secondary btn--sm" href="/founder"><?= e(t('nav.founder')) ?></a></div>
</article>

<article class="card program-card">
    <div class="card__media program-card__media program-card__media--<?= e($program['tone']) ?>">
        <span class="badge badge--brand card__badge"><?= e($program['line_title'] ?? $program['line_slug']) ?></span>
        <span class="program-card__symbol" aria-hidden="true">Ψ</span>
    </div>
    <div class="card__body">
        <h3 class="card__title"><?= e($program['title']) ?></h3>
        <p class="program-card__subtitle ltr"><?= e($program['subtitle']) ?></p>
        <p class="card__text mt-4"><?= e($program['short_description']) ?></p>
        <div class="card__meta"><span><?= e($program['duration']) ?></span><span><?= e($program['level']) ?></span><span><?= e($program['format']) ?></span></div>
    </div>
    <div class="card__footer"><span class="brand">Program</span><a class="btn btn--secondary btn--sm" href="/programs/<?= e($program['slug']) ?>">مشاهده مسیر</a></div>
</article>

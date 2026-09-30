<?php $journalCopy = [
    'fa'=>['button'=>'مرور مقاله‌ها','caption'=>'یادگیری، گفت‌وگو، تمرین','eyebrow'=>'از نظریه تا اتاق درمان','heading'=>'راهنماهای کاربردی برای درمانگران','lead'=>'مقاله‌هایی درباره رویکردهای درمانی، مهارت بالینی و مراقبت از خود در مسیر حرفه‌ای.','alt'=>'دفتر یادداشت باز در فضایی آرام برای یادگیری و تأمل حرفه‌ای'],
    'ar'=>['button'=>'تصفح المقالات','caption'=>'تعلّم، حوار، ممارسة','eyebrow'=>'من النظرية إلى الممارسة','heading'=>'أدلة عملية للمعالجين','lead'=>'مقالات حول مناهج العلاج والمهارات السريرية والعناية بالنفس في المسار المهني.','alt'=>'دفتر مفتوح في مساحة هادئة للتعلم والتأمل المهني'],
    'ku'=>['button'=>'وتارەکان ببینە','caption'=>'فێربوون، گفتوگۆ، ڕاهێنان','eyebrow'=>'لە تیۆرییەوە بۆ کردار','heading'=>'ڕێنمایی کرداری بۆ چارەسەرکاران','lead'=>'وتارگەلێک لەسەر ڕێبازەکانی چارەسەری و توانا کلینیکییەکان و چاودێریی خۆ لە ڕێگای پیشەییدا.','alt'=>'دەفتەرێکی کراوە لە شوێنێکی ئارام بۆ فێربوون و بیرکردنەوە'],
    'en'=>['button'=>'Browse articles','caption'=>'Learn, discuss, practise','eyebrow'=>'From theory to practice','heading'=>'Practical guides for therapists','lead'=>'Articles on therapeutic approaches, clinical skills and professional wellbeing.','alt'=>'An open notebook in a calm setting for learning and professional reflection'],
][locale()]; ?>
<section class="page-hero journal-hero">
    <div class="container journal-hero__grid">
        <div class="page-hero__content">
            <span class="eyebrow">Mentoris Journal</span>
            <h1><?= e(t('articles.title')) ?></h1>
            <p><?= e(t('articles.lead')) ?></p>
            <?php if ($articles): ?><a class="btn btn--primary" href="#journal-articles"><?= e($journalCopy['button']) ?> <?= icon('arrow-left') ?></a><?php endif; ?>
        </div>
        <figure class="journal-hero__image"><img src="<?= asset('images/mentoris-journal-editorial-v1.webp') ?>" alt="<?= e($journalCopy['alt']) ?>" width="1536" height="1024" fetchpriority="high"><figcaption><?= e($journalCopy['caption']) ?></figcaption></figure>
    </div>
</section>
<section class="section" id="journal-articles"><div class="container">
    <?php if ($articles): ?>
        <header class="section__head journal-section-head"><div><span class="eyebrow"><?= e($journalCopy['eyebrow']) ?></span><h2><?= e($journalCopy['heading']) ?></h2><p><?= e($journalCopy['lead']) ?></p></div></header>
        <div class="grid grid--3 articles-grid"><?php foreach ($articles as $article) { require view_path('components/cards/article-card.php'); } ?></div>
    <?php else: ?><?php $emptyIcon='file'; require view_path('components/content-empty.php'); ?><?php endif; ?>
</div></section>

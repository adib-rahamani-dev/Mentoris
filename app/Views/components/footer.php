<footer class="site-footer">
    <div class="container site-footer__top">
        <div class="site-footer__brand stack">
            <a class="brand-logo" href="/" aria-label="<?= e(t('brand.home')) ?>"><span class="brand-logo__mark" aria-hidden="true"></span><span class="brand-logo__text"><strong>Mentoris</strong><small>Academy</small></span></a>
            <p><?= e(t('footer.tagline')) ?></p>
        </div>
        <div>
            <h2 class="site-footer__title"><?= e(t('footer.quick')) ?></h2>
            <ul class="site-footer__links">
                <li><a href="/about"><?= e(t('nav.about')) ?></a></li>
                <li><a href="/founder"><?= e(t('nav.founder')) ?></a></li>
                <li><a href="/events"><?= e(t('nav.events')) ?></a></li>
                <li><a href="/community"><?= e(t('nav.community')) ?></a></li>
            </ul>
        </div>
        <div>
            <h2 class="site-footer__title"><?= e(t('footer.contact')) ?></h2>
            <ul class="site-footer__links">
                <li><a class="ltr" href="mailto:Academichaghani@gmail.com">Academichaghani@gmail.com</a></li>
                <li><a class="ltr" href="tel:+989100077611">09100077611</a></li>
                <li><a class="ltr" href="https://instagram.com/Mentoris_Academy" rel="noopener noreferrer" target="_blank">@Mentoris_Academy</a></li>
                <li><?= e(t('footer.location')) ?></li>
            </ul>
        </div>
    </div>
    <div class="container site-footer__bottom"><span>© <?= date('Y') ?> Mentoris Academy — <?= e(t('footer.rights')) ?></span><a href="https://Mentorisacademy.com" class="ltr">Mentorisacademy.com</a></div>
</footer>

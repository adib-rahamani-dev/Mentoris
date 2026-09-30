<header class="admin-page-head"><div><span class="eyebrow">QR Studio</span><h1>استودیوی QR منتوریس</h1><p>پیوند را وارد کنید، رنگ را انتخاب کنید و نسخه مناسب چاپ یا نمایش روی اسلاید را دریافت کنید.</p></div><a class="btn btn--secondary" href="/feedback" target="_blank" rel="noopener">دیدن صفحه بازخورد <?= icon('arrow-up-left') ?></a></header>

<div class="qr-studio" data-qr-studio data-site-origin="<?= e($baseUrl) ?>">
    <section class="qr-studio__controls admin-panel">
        <header><div><span class="eyebrow">01 / Create</span><h2>ساخت کد</h2></div></header>
        <form class="stack" data-qr-form>
            <label class="form-group"><span class="form-label">آدرس صفحه در سایت</span><input class="form-control ltr" type="text" name="path" value="/feedback" maxlength="300" spellcheck="false" required><small>مانند /feedback یا /live؛ کد فقط برای صفحه‌های همین دامنه ساخته می‌شود.</small></label>
            <label class="form-group"><span class="form-label">عنوان کارت چاپی</span><input class="form-control" type="text" name="title" value="بازخورد نشست منتوریس" maxlength="80" required></label>
            <fieldset class="qr-palette"><legend>پالت رنگ</legend><div class="qr-palette__options"><label><input type="radio" name="palette" value="forest" checked><span class="qr-palette__swatch qr-palette__swatch--forest"></span><strong>جنگلی</strong></label><label><input type="radio" name="palette" value="ink"><span class="qr-palette__swatch qr-palette__swatch--ink"></span><strong>مرکبی</strong></label><label><input type="radio" name="palette" value="plum"><span class="qr-palette__swatch qr-palette__swatch--plum"></span><strong>آلویی</strong></label></div></fieldset>
            <button class="btn btn--primary" type="submit">ساخت و نمایش QR <?= icon('arrow-left') ?></button>
            <p class="qr-studio__error" data-qr-error role="alert" hidden></p>
        </form>
        <div class="qr-studio__note"><strong>وضعیت صفحه بازخورد</strong><p><?= $feedbackReady ? 'جدول‌های ثبت‌نام و کد ورود سالن آماده‌اند. پیش از چاپ، فرم را با یک ثبت‌نام واقعی امتحان کنید.' : 'برای فعال‌شدن فرم، جدول‌های رویداد باید موجود باشند و FEEDBACK_ACCESS_CODE در تنظیمات سرور تعریف شود.' ?></p></div>
    </section>

    <section class="qr-studio__preview admin-panel">
        <header><div><span class="eyebrow">02 / Preview</span><h2>پیش‌نمایش چاپ</h2></div></header>
        <div class="qr-poster" data-qr-poster><div class="qr-poster__top"><span>MENTORIS</span><small>SCAN / EXPLORE</small></div><div class="qr-poster__body"><span class="qr-poster__eyebrow">با یک اسکن، همراه شوید</span><h2 data-qr-title>بازخورد نشست منتوریس</h2><div class="qr-poster__code" data-qr-code aria-label="کد QR"></div><p>دوربین گوشی را روی این کد بگیرید.</p></div><div class="qr-poster__foot"><span data-qr-url></span><strong>MENTORIS ACADEMY</strong></div></div>
        <div class="qr-studio__actions"><button class="btn btn--primary" type="button" data-qr-print disabled>چاپ کارت</button><button class="btn btn--secondary" type="button" data-qr-download disabled>دریافت SVG</button><button class="btn btn--ghost" type="button" data-qr-copy disabled>کپی آدرس</button></div>
        <p class="qr-studio__hint">قبل از چاپ انبوه، کد را با دوربین دو گوشی مختلف اسکن کنید. رنگ روشن پشت کد و حاشیه سفید برای خوانایی آن حفظ می‌شود.</p>
    </section>
</div>
<script type="module" src="<?= asset('js/admin-qr.js') ?>?v=1.0.0"></script>

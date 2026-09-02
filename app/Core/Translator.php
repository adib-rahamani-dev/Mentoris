<?php

declare(strict_types=1);

namespace App\Core;

final class Translator
{
    public const SUPPORTED = ['fa', 'ar', 'ku', 'en'];

    private static string $locale = 'fa';

    private const MESSAGES = [
        'fa' => [
            'language.name' => 'فارسی', 'language.select' => 'انتخاب زبان',
            'skip' => 'رفتن به محتوای اصلی', 'brand.home' => 'منتوریس — صفحه اصلی',
            'nav.home' => 'خانه', 'nav.academy' => 'آکادمی', 'nav.courses' => 'دوره‌ها',
            'nav.programs' => 'برنامه‌ها', 'nav.lines' => 'لاین‌های آکادمی', 'nav.specializations' => 'تخصص‌ها',
            'nav.events' => 'رویدادها', 'nav.community' => 'جامعه حرفه‌ای', 'nav.mentors' => 'اساتید',
            'nav.about' => 'درباره ما', 'nav.founder' => 'بنیان‌گذار', 'nav.contact' => 'ارتباط با ما',
            'nav.login' => 'ورود / عضویت', 'nav.account' => 'حساب من', 'nav.admin' => 'مدیریت',
            'nav.open' => 'بازکردن منو', 'theme.toggle' => 'تغییر حالت رنگ',
            'footer.tagline' => 'جایی برای یادگیری، ارتباط و حرفه‌ای‌تر شدن.',
            'footer.quick' => 'دسترسی سریع', 'footer.contact' => 'ارتباط با ما',
            'footer.location' => 'تبریز، ایران', 'footer.rights' => 'تمام حقوق محفوظ است.',
            'empty.title' => 'به‌زودی', 'empty.text' => 'در حال آماده‌سازی این بخش هستیم. تازه‌ترین خبرها از کانال‌های رسمی منتوریس اعلام می‌شود.',
            'home.badge' => 'آکادمی منتوریس', 'home.title.before' => 'یادگیری، ارتباط و', 'home.title.accent' => 'حرفه‌ای‌تر شدن',
            'home.lead' => 'مسیر تازه‌ای برای رشد حرفه‌ای در روان‌شناسی و روان‌درمانی؛ با پیوند دانش علمی، تجربه، بازخورد و جامعه حرفه‌ای.',
            'home.cta.primary' => 'آشنایی با منتوریس', 'home.cta.secondary' => 'درباره بنیان‌گذار',
            'home.mission' => 'ماموریت ما', 'home.mission.text' => 'تبدیل منتورینگ حرفه‌ای به بخشی جدی از مسیر رشد درمانگران و پژوهشگران؛ جایی که آموزش، تجربه و فرصت‌های واقعی رشد کنار هم قرار می‌گیرند.',
            'home.vision' => 'چشم‌انداز ما', 'home.vision.text' => 'ساختن پلی واقعی میان جامعه روان‌شناسی ایران و شبکه بین‌المللی متخصصان، با تکیه بر دانش روز و استانداردهای حرفه‌ای جهان.',
        ],
        'ar' => [
            'language.name' => 'العربية', 'language.select' => 'اختيار اللغة',
            'skip' => 'الانتقال إلى المحتوى الرئيسي', 'brand.home' => 'منتوريس — الصفحة الرئيسية',
            'nav.home' => 'الرئيسية', 'nav.academy' => 'الأكاديمية', 'nav.courses' => 'الدورات',
            'nav.programs' => 'البرامج', 'nav.lines' => 'مسارات الأكاديمية', 'nav.specializations' => 'التخصصات',
            'nav.events' => 'الفعاليات', 'nav.community' => 'المجتمع المهني', 'nav.mentors' => 'الخبراء',
            'nav.about' => 'من نحن', 'nav.founder' => 'المؤسِّسة', 'nav.contact' => 'اتصل بنا',
            'nav.login' => 'دخول / تسجيل', 'nav.account' => 'حسابي', 'nav.admin' => 'الإدارة',
            'nav.open' => 'فتح القائمة', 'theme.toggle' => 'تغيير المظهر',
            'footer.tagline' => 'مكان للتعلّم والتواصل والتطور المهني.',
            'footer.quick' => 'روابط سريعة', 'footer.contact' => 'تواصل معنا',
            'footer.location' => 'تبريز، إيران', 'footer.rights' => 'جميع الحقوق محفوظة.',
            'empty.title' => 'قريباً', 'empty.text' => 'نعمل على إعداد هذا القسم. ستُنشر آخر الأخبار عبر قنوات منتوريس الرسمية.',
            'home.badge' => 'أكاديمية منتوريس', 'home.title.before' => 'التعلّم والتواصل و', 'home.title.accent' => 'التطور المهني',
            'home.lead' => 'مسار جديد للنمو المهني في علم النفس والعلاج النفسي، يجمع المعرفة العلمية والخبرة والتغذية الراجعة والمجتمع المهني.',
            'home.cta.primary' => 'اكتشف منتوريس', 'home.cta.secondary' => 'عن المؤسِّسة',
            'home.mission' => 'رسالتنا', 'home.mission.text' => 'جعل الإرشاد المهني جزءاً أساسياً من نمو المعالجين والباحثين، حيث تجتمع المعرفة والخبرة وفرص التطور الحقيقية.',
            'home.vision' => 'رؤيتنا', 'home.vision.text' => 'بناء جسر حقيقي بين مجتمع علم النفس في إيران والشبكة الدولية للمتخصصين وفق أحدث المعارف والمعايير المهنية.',
        ],
        'ku' => [
            'language.name' => 'کوردی', 'language.select' => 'هەڵبژاردنی زمان',
            'skip' => 'چوون بۆ ناوەڕۆکی سەرەکی', 'brand.home' => 'مێنتۆریس — سەرەتا',
            'nav.home' => 'سەرەتا', 'nav.academy' => 'ئەکادیمی', 'nav.courses' => 'کۆرسەکان',
            'nav.programs' => 'بەرنامەکان', 'nav.lines' => 'هێڵەکانی ئەکادیمی', 'nav.specializations' => 'پسپۆڕییەکان',
            'nav.events' => 'بۆنەکان', 'nav.community' => 'کۆمەڵگەی پیشەیی', 'nav.mentors' => 'مامۆستایان',
            'nav.about' => 'دەربارەی ئێمە', 'nav.founder' => 'دامەزرێنەر', 'nav.contact' => 'پەیوەندی',
            'nav.login' => 'چوونەژوورەوە / خۆتۆمارکردن', 'nav.account' => 'هەژماری من', 'nav.admin' => 'بەڕێوەبردن',
            'nav.open' => 'کردنەوەی مێنیو', 'theme.toggle' => 'گۆڕینی ڕووکار',
            'footer.tagline' => 'شوێنێک بۆ فێربوون، پەیوەندی و پیشەیی‌تر بوون.',
            'footer.quick' => 'دەستگەیشتنی خێرا', 'footer.contact' => 'پەیوەندی لەگەڵ ئێمە',
            'footer.location' => 'تەورێز، ئێران', 'footer.rights' => 'هەموو مافەکان پارێزراون.',
            'empty.title' => 'بەم زووانە', 'empty.text' => 'ئەم بەشە ئامادە دەکەین. نوێترین هەواڵ لە کەناڵە فەرمییەکانی مێنتۆریس ڕادەگەیەنرێت.',
            'home.badge' => 'ئەکادیمی مێنتۆریس', 'home.title.before' => 'فێربوون، پەیوەندی و', 'home.title.accent' => 'پیشەیی‌تر بوون',
            'home.lead' => 'ڕێگایەکی نوێ بۆ گەشەی پیشەیی لە دەروونناسی و دەرووندرمانی؛ بە پێکەوەبەستنی زانستی، ئەزموون، فیدباک و کۆمەڵگەی پیشەیی.',
            'home.cta.primary' => 'ناسینی مێنتۆریس', 'home.cta.secondary' => 'دەربارەی دامەزرێنەر',
            'home.mission' => 'ئەرکی ئێمە', 'home.mission.text' => 'کردنی ڕێنمایی پیشەیی بە بەشێکی جدی لە گەشەی چارەسەرکاران و توێژەران؛ شوێنێک کە زانست و ئەزموون و دەرفەتی ڕاستەقینەی گەشە پێکەوە دەبن.',
            'home.vision' => 'دیدگای ئێمە', 'home.vision.text' => 'دروستکردنی پردێکی ڕاستەقینە لەنێوان کۆمەڵگەی دەروونناسیی ئێران و تۆڕی نێودەوڵەتیی پسپۆڕان بە پشتیوانی زانستی نوێ.',
        ],
        'en' => [
            'language.name' => 'English', 'language.select' => 'Select language',
            'skip' => 'Skip to main content', 'brand.home' => 'Mentoris — Home',
            'nav.home' => 'Home', 'nav.academy' => 'Academy', 'nav.courses' => 'Courses',
            'nav.programs' => 'Programs', 'nav.lines' => 'Academy lines', 'nav.specializations' => 'Specializations',
            'nav.events' => 'Events', 'nav.community' => 'Community', 'nav.mentors' => 'Experts',
            'nav.about' => 'About', 'nav.founder' => 'Founder', 'nav.contact' => 'Contact',
            'nav.login' => 'Sign in / Join', 'nav.account' => 'My account', 'nav.admin' => 'Admin',
            'nav.open' => 'Open menu', 'theme.toggle' => 'Switch color theme',
            'footer.tagline' => 'A place to learn, connect, and grow professionally.',
            'footer.quick' => 'Quick links', 'footer.contact' => 'Contact us',
            'footer.location' => 'Tabriz, Iran', 'footer.rights' => 'All rights reserved.',
            'empty.title' => 'Coming soon', 'empty.text' => 'We are preparing this section. Updates will be announced through official Mentoris channels.',
            'home.badge' => 'Mentoris Academy', 'home.title.before' => 'Learn, connect, and', 'home.title.accent' => 'grow professionally',
            'home.lead' => 'A new path for professional growth in psychology and psychotherapy, connecting scientific knowledge, experience, feedback, and community.',
            'home.cta.primary' => 'Discover Mentoris', 'home.cta.secondary' => 'Meet the founder',
            'home.mission' => 'Our mission', 'home.mission.text' => 'To make professional mentoring a meaningful part of every therapist’s and researcher’s growth, bringing knowledge, experience, and real opportunities together.',
            'home.vision' => 'Our vision', 'home.vision.text' => 'To build a genuine bridge between Iran’s psychology community and an international network of professionals, guided by current evidence and global standards.',
        ],
    ];

    public static function boot(array $query, array $cookies, array $server): void
    {
        $requested = strtolower((string) ($query['lang'] ?? $cookies['mentoris_locale'] ?? ''));
        $requested = $requested === 'ckb' ? 'ku' : $requested;
        if (!in_array($requested, self::SUPPORTED, true)) {
            $header = strtolower((string) ($server['HTTP_ACCEPT_LANGUAGE'] ?? 'fa'));
            $requested = str_starts_with($header, 'ar') ? 'ar' : ((str_starts_with($header, 'ku') || str_starts_with($header, 'ckb')) ? 'ku' : (str_starts_with($header, 'en') ? 'en' : 'fa'));
        }
        self::$locale = $requested;

        if (($query['lang'] ?? null) === $requested && ($cookies['mentoris_locale'] ?? null) !== $requested && !headers_sent()) {
            $forwardedProto = strtolower(trim(explode(',', (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
            $isSecure = (($server['HTTPS'] ?? '') !== '' && ($server['HTTPS'] ?? '') !== 'off') || $forwardedProto === 'https';
            setcookie('mentoris_locale', $requested, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    public static function locale(): string { return self::$locale; }

    public static function htmlLocale(): string { return self::$locale === 'ku' ? 'ckb' : self::$locale; }

    public static function direction(): string { return self::$locale === 'en' ? 'ltr' : 'rtl'; }

    public static function get(string $key, array $replace = []): string
    {
        $message = self::MESSAGES[self::$locale][$key] ?? self::MESSAGES['fa'][$key] ?? $key;
        foreach ($replace as $name => $value) {
            $message = str_replace(':' . $name, (string) $value, $message);
        }
        return $message;
    }

    public static function localeName(string $locale): string
    {
        return self::MESSAGES[$locale]['language.name'] ?? strtoupper($locale);
    }

    public static function switchUrl(string $locale): string
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $query = $_GET;
        $query['lang'] = in_array($locale, self::SUPPORTED, true) ? $locale : 'fa';
        return $path . '?' . http_build_query($query);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Translator;

final class PublicContentService
{
    public static function founder(): array
    {
        return self::localized([
            'fa' => [
                'name' => 'دکتر مریم حقانی',
                'role' => 'بنیان‌گذار و مدیر آکادمی منتوریس',
                'short_bio' => 'پژوهشگر و مدرس حوزه روان‌شناسی با تمرکز بر پیوند دانش علمی، پژوهش، علوم شناختی و آموزش تخصصی.',
                'paragraphs' => [
                    'دکتر مریم حقانی پژوهشگر و مدرس حوزه روان‌شناسی است که مسیر حرفه‌ای خود را در نقطه تلاقی روان‌شناسی، پژوهش، علوم شناختی و آموزش تخصصی دنبال کرده است.',
                    'او در طول مسیر دانشگاهی و حرفه‌ای خود در حوزه‌هایی از جمله پژوهش‌های روان‌شناختی، کار با روش‌های کمی و شناختی، نورومدولاسیون و توسعه مهارت‌های تخصصی فعالیت داشته و همواره بر ارتباط میان دانش علمی و کاربرد حرفه‌ای تأکید داشته است.',
                    'ایده شکل‌گیری منتوریس از یک دغدغه ساده اما جدی آغاز شد: چگونه می‌توان مسیر رشد یک متخصص روان‌شناسی را از آموزش‌های پراکنده فراتر برد و به مسیری مستمر، علمی و حرفه‌ای تبدیل کرد؟',
                    'منتوریس حاصل همین دغدغه و تلاشی برای ایجاد بستری تازه برای یادگیری، ارتباط، رشد و توسعه حرفه‌ای متخصصان روان‌شناسی و سلامت روان است.',
                ],
                'quote' => 'چگونه می‌توان مسیر رشد یک متخصص روان‌شناسی را از آموزش‌های پراکنده فراتر برد و به مسیری مستمر، علمی و حرفه‌ای تبدیل کرد؟',
            ],
            'ar' => [
                'name' => 'الدكتورة مريم حقاني', 'role' => 'مؤسِّسة ومديرة أكاديمية منتوريس',
                'short_bio' => 'باحثة ومحاضِرة في علم النفس، تعمل عند تقاطع البحث والعلوم المعرفية والتعليم المتخصص.',
                'paragraphs' => [
                    'الدكتورة مريم حقاني باحثة ومحاضِرة في علم النفس، بنت مسيرتها المهنية عند تقاطع علم النفس والبحث والعلوم المعرفية والتعليم المتخصص.',
                    'عملت في البحوث النفسية والمناهج الكمية والمعرفية والتعديل العصبي وتنمية المهارات المتخصصة، مع تأكيد دائم على الصلة بين المعرفة العلمية والتطبيق المهني.',
                    'بدأت فكرة منتوريس من سؤال جاد: كيف يمكن نقل نمو المتخصص النفسي من دورات متفرقة إلى مسار مستمر وعلمي ومهني؟',
                    'منتوريس ثمرة هذا السؤال، ومساحة جديدة للتعلّم والتواصل والنمو والتطور المهني للعاملين في علم النفس والصحة النفسية.',
                ],
                'quote' => 'كيف يمكن تحويل نمو المتخصص النفسي من تعليم متفرق إلى مسار مستمر وعلمي ومهني؟',
            ],
            'ku' => [
                'name' => 'د. مەریەم حەقانی', 'role' => 'دامەزرێنەر و بەڕێوەبەری ئەکادیمی مێنتۆریس',
                'short_bio' => 'توێژەر و مامۆستای دەروونناسی بە جەختکردنەوە لە پەیوەندیی زانست، توێژینەوە، زانستە مەعریفییەکان و فێرکردنی پسپۆڕی.',
                'paragraphs' => [
                    'د. مەریەم حەقانی توێژەر و مامۆستای بواری دەروونناسییە و ڕێگای پیشەیی خۆی لە خاڵی یەکگرتنی دەروونناسی، توێژینەوە، زانستە مەعریفییەکان و فێرکردنی پسپۆڕی بەردەوام کردووە.',
                    'لە توێژینەوەی دەروونی، ڕێبازە ژمارەیی و مەعریفییەکان، نیورۆمۆدیولەیشن و گەشەپێدانی تواناکانی پسپۆڕی کاری کردووە و هەمیشە جەختی لە پەیوەندیی نێوان زانستی و بەکارهێنانی پیشەیی کردووەتەوە.',
                    'بیرۆکەی مێنتۆریس لە پرسیارێکی گرنگەوە دەستی پێکرد: چۆن دەتوانین گەشەی پسپۆڕێکی دەروونناسی لە فێرکاریی پەرتەوازە بگۆڕین بۆ ڕێگایەکی بەردەوام، زانستی و پیشەیی؟',
                    'مێنتۆریس هەوڵێکە بۆ دروستکردنی بوارێکی نوێ بۆ فێربوون، پەیوەندی، گەشە و پەرەپێدانی پیشەیی پسپۆڕانی دەروونناسی و تەندروستی دەروونی.',
                ],
                'quote' => 'چۆن گەشەی پسپۆڕی دەروونناسی دەگۆڕین بۆ ڕێگایەکی بەردەوام، زانستی و پیشەیی؟',
            ],
            'en' => [
                'name' => 'Dr. Maryam Haghani', 'role' => 'Founder and Director of Mentoris Academy',
                'short_bio' => 'Psychology researcher and educator working at the intersection of research, cognitive science, and specialist education.',
                'paragraphs' => [
                    'Dr. Maryam Haghani is a psychology researcher and educator whose professional path sits at the intersection of psychology, research, cognitive science, and specialist education.',
                    'Her academic and professional work spans psychological research, quantitative and cognitive methods, neuromodulation, and specialist skill development, with a consistent focus on connecting scientific knowledge to professional practice.',
                    'Mentoris began with a serious question: how can a psychology professional move beyond fragmented training and follow a continuous, scientific, and genuinely professional path of growth?',
                    'Mentoris is an answer to that concern—a new environment for learning, connection, growth, and professional development across psychology and mental health.',
                ],
                'quote' => 'How can professional growth move beyond fragmented training and become continuous, scientific, and meaningful?',
            ],
        ]) + ['slug' => 'maryam-haghani', 'image' => 'images/founder-maryam-haghani-v1.png', 'tone' => 'violet', 'initials' => 'MH'];
    }

    public static function about(): array
    {
        return self::localized([
            'fa' => [
                'title' => 'درباره منتوریس',
                'lead' => 'منتوریس آکادمی با هدف ساختن یک مسیر تازه برای رشد حرفه‌ای در روان‌شناسی و روان‌درمانی شکل گرفته است.',
                'paragraphs' => [
                    'ما می‌خواهیم مفهوم منتورینگ حرفه‌ای را به بخشی جدی از مسیر رشد درمانگران و پژوهشگران تبدیل کنیم؛ مسیری که در آن آموزش، تجربه، بازخورد، ارتباط با متخصصان و فرصت‌های واقعی رشد در کنار یکدیگر قرار می‌گیرند.',
                    'منتوریس با بهره‌گیری از دانش و دوره‌های معتبر بین‌المللی، متخصصان و مدرسان توانمند و مفاهیم نوین در آموزش و عملکرد حرفه‌ای تلاش می‌کند به تربیت درمانگران ماهرتر، پژوهشگران توانمندتر و ارتقای کیفیت خدمات روان‌شناختی کمک کند.',
                    'اما منتوریس فقط یک بستر آموزشی نیست. ما می‌خواهیم یک شبکه حرفه‌ای از درمانگران و متخصصان حوزه‌های مختلف بسازیم؛ شبکه‌ای برای یادگیری، تبادل تجربه، همکاری و ارتباط میان افرادی که می‌توانند در مسیر حرفه‌ای یکدیگر نقش داشته باشند.',
                    'منتوریس از ایران آغاز می‌کند؛ با نگاه به استانداردها و تجربه‌های حرفه‌ای جهان و با هدف ساختن ارتباطی واقعی میان جامعه روان‌شناسی ایران و شبکه بین‌المللی متخصصان.',
                ],
                'signature' => 'منتوریس؛ جایی برای یادگیری، ارتباط و حرفه‌ای‌تر شدن.',
            ],
            'ar' => [
                'title' => 'عن منتوريس', 'lead' => 'تأسست أكاديمية منتوريس لبناء مسار جديد للنمو المهني في علم النفس والعلاج النفسي.',
                'paragraphs' => [
                    'نريد أن يصبح الإرشاد المهني جزءاً أساسياً من نمو المعالجين والباحثين، في مسار يجمع التعليم والخبرة والتغذية الراجعة والتواصل مع المتخصصين وفرص التطور الحقيقية.',
                    'بالاستفادة من المعرفة والبرامج الدولية الموثوقة والخبراء الأكفاء والمفاهيم الحديثة في التعليم والأداء المهني، تسعى منتوريس إلى دعم معالجين أكثر مهارة وباحثين أكثر قدرة وخدمات نفسية أفضل.',
                    'منتوريس ليست منصة تعليمية فحسب؛ بل نعمل على بناء شبكة مهنية للتعلم وتبادل الخبرات والتعاون بين المتخصصين.',
                    'تنطلق منتوريس من إيران برؤية عالمية، لبناء صلة حقيقية بين مجتمع علم النفس الإيراني والشبكة الدولية للمتخصصين.',
                ], 'signature' => 'منتوريس؛ مكان للتعلّم والتواصل والتطور المهني.',
            ],
            'ku' => [
                'title' => 'دەربارەی مێنتۆریس', 'lead' => 'ئەکادیمی مێنتۆریس بۆ دروستکردنی ڕێگایەکی نوێی گەشەی پیشەیی لە دەروونناسی و دەرووندرمانی دامەزراوە.',
                'paragraphs' => [
                    'دەمانەوێت ڕێنمایی پیشەیی ببێتە بەشێکی گرنگ لە گەشەی چارەسەرکاران و توێژەران؛ ڕێگایەک کە فێرکردن، ئەزموون، فیدباک، پەیوەندی لەگەڵ پسپۆڕان و دەرفەتی ڕاستەقینەی گەشە پێکەوە دەخات.',
                    'مێنتۆریس بە پشتبەستن بە زانست و بەرنامە نێودەوڵەتییە باوەڕپێکراوەکان و مامۆستایانی توانادار، هەوڵ دەدات چارەسەرکاری شارەزاتر و توێژەری بەهێزتر پەروەردە بکات.',
                    'مێنتۆریس تەنها بوارێکی فێرکاری نییە؛ تۆڕێکی پیشەیی بۆ فێربوون، گۆڕینەوەی ئەزموون و هاوکاری دروست دەکەین.',
                    'مێنتۆریس لە ئێرانەوە دەست پێدەکات، بە دیدێکی جیهانی و بۆ پەیوەستکردنی کۆمەڵگەی دەروونناسی ئێران بە تۆڕی نێودەوڵەتیی پسپۆڕان.',
                ], 'signature' => 'مێنتۆریس؛ شوێنێک بۆ فێربوون، پەیوەندی و پیشەیی‌تر بوون.',
            ],
            'en' => [
                'title' => 'About Mentoris', 'lead' => 'Mentoris Academy was founded to create a new path for professional growth in psychology and psychotherapy.',
                'paragraphs' => [
                    'We aim to make professional mentoring a serious part of every therapist’s and researcher’s growth—bringing education, experience, feedback, access to specialists, and real opportunities into one coherent path.',
                    'Drawing on respected international knowledge and programs, skilled educators, and modern approaches to learning and professional performance, Mentoris seeks to support more capable therapists, stronger researchers, and higher-quality psychological services.',
                    'Mentoris is more than an education platform. We are building a professional network for learning, exchanging experience, collaborating, and creating relationships that matter throughout a career.',
                    'Mentoris begins in Iran with a global outlook, building a genuine connection between Iran’s psychology community and an international network of professionals.',
                ], 'signature' => 'Mentoris: learn, connect, and grow professionally.',
            ],
        ]);
    }

    public static function academyLines(): array
    {
        $titles = [
            'fa' => [['research','پژوهش','Research'],['therapist-development','توسعه درمانگر','Therapist Development'],['wellbeing','بهزیستی','Wellbeing'],['therapy','روان‌درمانی','Psychotherapy'],['assessment','ارزیابی','Assessment'],['public-mental-health','سلامت روان عمومی','Public Mental Health'],['professional-development','توسعه حرفه‌ای','Professional Development']],
            'ar' => [['research','البحث','Research'],['therapist-development','تطوير المعالج','Therapist Development'],['wellbeing','الرفاه النفسي','Wellbeing'],['therapy','العلاج النفسي','Psychotherapy'],['assessment','التقييم','Assessment'],['public-mental-health','الصحة النفسية العامة','Public Mental Health'],['professional-development','التطوير المهني','Professional Development']],
            'ku' => [['research','توێژینەوە','Research'],['therapist-development','گەشەپێدانی چارەسەرکار','Therapist Development'],['wellbeing','خۆشگوزەرانی','Wellbeing'],['therapy','دەرووندرمانی','Psychotherapy'],['assessment','هەڵسەنگاندن','Assessment'],['public-mental-health','تەندروستی دەروونی گشتی','Public Mental Health'],['professional-development','گەشەی پیشەیی','Professional Development']],
            'en' => [['research','Research','Research'],['therapist-development','Therapist Development','Therapist Development'],['wellbeing','Wellbeing','Wellbeing'],['therapy','Psychotherapy','Psychotherapy'],['assessment','Assessment','Assessment'],['public-mental-health','Public Mental Health','Public Mental Health'],['professional-development','Professional Development','Professional Development']],
        ];
        $descriptions = [
            'fa' => ['پژوهش‌های روان‌شناختی و تبدیل شواهد به تصمیم حرفه‌ای.','رشد هویت، مهارت و بهزیستی درمانگر در سراسر مسیر حرفه‌ای.','دانش و تمرین برای کیفیت بهتر زندگی و عملکرد پایدار.','یادگیری مسئولانه رویکردها و مهارت‌های روان‌درمانی.','روش‌ها و ابزارهای ارزیابی دقیق و اخلاق‌مدار.','پیشگیری، سواد سلامت روان و مداخلات جامعه‌محور.','مهارت‌های مکمل برای ساختن یک مسیر شغلی پایدار.'],
            'ar' => ['البحث النفسي وتحويل الدليل إلى قرار مهني أفضل.','تنمية هوية المعالج ومهاراته ورفاهه طوال المسار المهني.','معرفة وممارسة لحياة أفضل وأداء مستدام.','تعلّم مسؤول لمناهج العلاج النفسي ومهاراته.','مناهج وأدوات دقيقة وأخلاقية للتقييم النفسي.','الوقاية والثقافة النفسية والممارسة المجتمعية.','مهارات مكملة لبناء مسار مهني مستدام.'],
            'ku' => ['توێژینەوەی دەروونی و گۆڕینی بەڵگە بۆ بڕیاری پیشەیی باشتر.','گەشەپێدانی ناسنامە و تواناکان و خۆشگوزەرانی چارەسەرکار.','زانست و ڕاهێنان بۆ ژیان و کارکردنی بەردەوام.','فێربوونی بەرپرسیارانەی ڕێباز و تواناکانی دەرووندرمانی.','ڕێباز و ئامرازە ورد و ئەخلاقییەکانی هەڵسەنگاندن.','پێشگیری، زانیاریی تەندروستی دەروونی و کاری کۆمەڵایەتی.','توانای تەواوکەر بۆ دروستکردنی ڕێگای پیشەیی بەردەوام.'],
            'en' => ['Psychological research that turns evidence into better professional decisions.','Developing therapist identity, skills, and wellbeing throughout a career.','Knowledge and practice for sustainable wellbeing and performance.','Responsible learning in psychotherapy approaches and skills.','Accurate and ethical psychological assessment methods.','Prevention, mental-health literacy, and community practice.','Complementary skills for a sustainable professional path.'],
        ];
        $locale = Translator::locale();
        $items = $titles[$locale] ?? $titles['fa'];
        $copy = $descriptions[$locale] ?? $descriptions['fa'];
        $tones = ['violet','magenta','teal','blue','amber','rose','indigo'];
        $icons = ['⌁','Ψ','◉','◇','⌕','◎','△'];
        return array_map(static fn (array $item, int $index): array => [
            'slug' => $item[0], 'title' => $item[1], 'en' => $item[2], 'tone' => $tones[$index], 'icon' => $icons[$index],
            'description' => $copy[$index], 'promise' => function_exists('t') ? t('empty.text') : '', 'specializations' => [],
        ], $items, array_keys($items));
    }

    public static function academyLine(string $slug): ?array
    {
        foreach (self::academyLines() as $line) {
            if ($line['slug'] === $slug) return $line + ['programs' => []];
        }
        return null;
    }

    public static function specializations(): array { return []; }
    public static function specialization(string $slug): ?array { return null; }
    public static function programs(): array { return []; }
    public static function program(string $slug): ?array { return null; }
    public static function courses(): array { return []; }
    public static function course(string $slug): ?array { return null; }

    public static function courseCategories(): array
    {
        return array_column(self::academyLines(), 'title', 'slug');
    }

    public static function courseStatusLabels(): array
    {
        return ['active' => 'فعال', 'coming-soon' => function_exists('t') ? t('empty.title') : 'به‌زودی', 'full' => 'تکمیل ظرفیت', 'completed' => 'پایان‌یافته'];
    }

    public static function events(): array
    {
        $copy = self::localized([
            'fa' => ['title' => 'حلقه درمانگران | اولین گردهمایی تخصصی منتوریس', 'type' => 'گردهمایی تخصصی', 'date' => 'جمعه ۲۷ شهریور ۱۴۰۵', 'month' => 'شهریور', 'time' => '۱۸ تا ۲۰', 'location' => 'تبریز', 'short' => 'فضایی برای گفت‌وگوی حرفه‌ای، تبادل تجربه، شبکه‌سازی و گفت‌وگو درباره چالش‌های واقعی مسیر درمانگری.', 'description' => 'آکادمی منتوریس با افتخار آغاز سلسله حلقه‌های تخصصی روان‌شناسی را با نخستین رویداد خود در تبریز اعلام می‌کند. حلقه درمانگران یک کلاس آموزشی معمولی نیست؛ فضایی است برای گفت‌وگوی حرفه‌ای، تبادل تجربه و شکل‌دادن به ارتباط‌های معنادار. این مسیر در ادامه با دانش روز و همراهی متخصصان ملی و بین‌المللی به رشد حرفه‌ای، مراقبت از خود، بهزیستی درمانگران و تمرین عامدانه خواهد پرداخت.', 'note' => 'ثبت فرم اولیه به معنای ثبت‌نام قطعی نیست. فرم‌ها بررسی می‌شوند و برای متقاضیان منتخب جهت تکمیل مراحل ثبت‌نام تماس گرفته خواهد شد.'],
            'ar' => ['title' => 'حلقة المعالجين | أول لقاء تخصصي لمنتوريس', 'type' => 'لقاء تخصصي', 'date' => 'الجمعة، 18 سبتمبر 2026', 'month' => 'سبتمبر', 'time' => '18:00–20:00', 'location' => 'تبريز', 'short' => 'مساحة للحوار المهني وتبادل الخبرات وبناء العلاقات ومناقشة تحديات مسار المعالج.', 'description' => 'تعلن أكاديمية منتوريس انطلاق سلسلة حلقات علم النفس المتخصصة بأول لقاء لها في تبريز. حلقة المعالجين ليست محاضرة تقليدية، بل مساحة للحوار وتبادل الخبرة وبناء علاقات مهنية ذات معنى.', 'note' => 'إرسال النموذج الأولي لا يعني تأكيد التسجيل. ستُراجع الطلبات ويُتواصل مع المتقدمين المختارين لاستكمال التسجيل.'],
            'ku' => ['title' => 'بازنەی چارەسەرکاران | یەکەم کۆبوونەوەی پسپۆڕی مێنتۆریس', 'type' => 'کۆبوونەوەی پسپۆڕی', 'date' => 'هەینی 18ی سێپتەمبەری 2026', 'month' => 'سێپتەمبەر', 'time' => '18–20', 'location' => 'تەورێز', 'short' => 'بوارێک بۆ گفتوگۆی پیشەیی، گۆڕینەوەی ئەزموون و باسکردنی ئالنگارییە ڕاستەقینەکانی ڕێگای چارەسەرکاری.', 'description' => 'ئەکادیمی مێنتۆریس زنجیرە بازنە پسپۆڕییەکانی دەروونناسی بە یەکەم بۆنە لە تەورێز دەست پێدەکات. ئەم بازنەیە وانەیەکی ئاسایی نییە؛ بوارێکە بۆ گفتوگۆ، ئەزموون و پەیوەندیی پیشەیی.', 'note' => 'پڕکردنەوەی فۆڕمی سەرەتایی واتای تۆمارکردنی کۆتایی نییە. داواکارییەکان هەڵسەنگێنرێن و لەگەڵ هەڵبژێردراوان پەیوەندی دەگیرێت.'],
            'en' => ['title' => 'Therapists’ Circle | The First Mentoris Professional Gathering', 'type' => 'Professional gathering', 'date' => 'Friday, 18 September 2026', 'month' => 'September', 'time' => '18:00–20:00', 'location' => 'Tabriz', 'short' => 'A space for professional dialogue, shared experience, networking, and honest conversations about the realities of becoming a therapist.', 'description' => 'Mentoris Academy launches its specialist psychology circles with a first gathering in Tabriz. Therapists’ Circle is not a conventional class; it is a space for meaningful professional conversation, experience exchange, and relationship-building.', 'note' => 'Submitting the initial form does not confirm registration. Applications will be reviewed and selected applicants will be contacted to complete registration.'],
        ]);
        return [[
            'slug' => 'therapists-circle-tabriz', 'day' => '۲۷', 'month' => $copy['month'], 'date' => $copy['date'], 'date_iso' => '2026-09-18', 'time' => $copy['time'],
            'title' => $copy['title'], 'type' => $copy['type'], 'mode' => 'offline', 'location' => $copy['location'], 'tone' => 'violet',
            'status' => 'registration-open', 'capacity' => 0, 'registered' => 0, 'capacity_label' => 'ظرفیت محدود', 'instructor_slug' => 'maryam-haghani', 'line_slug' => 'therapist-development',
            'short_description' => $copy['short'], 'description' => $copy['description'],
            'highlights' => [$copy['short'], $copy['note']], 'registration_note' => $copy['note'],
            'external_registration_url' => 'https://forms.gle/BKUrF5Pddj2r7AyT8',
        ]];
    }

    public static function event(string $slug): ?array
    {
        foreach (self::events() as $event) {
            if ($event['slug'] !== $slug) continue;
            $event['instructor'] = self::mentors()[0] ?? null;
            $event['line'] = self::academyLine($event['line_slug']);
            $event['related_programs'] = [];
            $event['available'] = $event['capacity'] > 0 ? max(0, $event['capacity'] - $event['registered']) : null;
            $event['can_register'] = $event['status'] === 'registration-open';
            $event['status_label'] = self::eventStatusLabels()[$event['status']] ?? $event['status'];
            $event['mode_label'] = self::eventModeLabels()[$event['mode']] ?? $event['mode'];
            return $event;
        }
        return null;
    }

    public static function eventStatusLabels(): array
    {
        return self::localized([
            'fa'=>['upcoming'=>'به‌زودی','registration-open'=>'ثبت‌نام اولیه','full'=>'تکمیل ظرفیت','completed'=>'برگزار شده','canceled'=>'لغو شده'],
            'ar'=>['upcoming'=>'قريباً','registration-open'=>'التسجيل الأولي','full'=>'اكتملت السعة','completed'=>'أُقيمت','canceled'=>'ملغاة'],
            'ku'=>['upcoming'=>'بەم زووانە','registration-open'=>'تۆمارکردنی سەرەتایی','full'=>'شوێن پڕە','completed'=>'بەڕێوەچووە','canceled'=>'هەڵوەشاوەتەوە'],
            'en'=>['upcoming'=>'Coming soon','registration-open'=>'Initial registration','full'=>'Full','completed'=>'Completed','canceled'=>'Canceled'],
        ]);
    }

    public static function eventModeLabels(): array
    {
        return self::localized([
            'fa'=>['online'=>'آنلاین','offline'=>'حضوری','hybrid'=>'ترکیبی'],
            'ar'=>['online'=>'عن بُعد','offline'=>'حضوري','hybrid'=>'هجين'],
            'ku'=>['online'=>'ئۆنلاین','offline'=>'ئامادەبوون','hybrid'=>'تێکەڵ'],
            'en'=>['online'=>'Online','offline'=>'In person','hybrid'=>'Hybrid'],
        ]);
    }

    public static function community(): array
    {
        return ['member_count' => null, 'benefits' => [], 'rules' => []];
    }

    public static function mentors(): array
    {
        $founder = self::founder();
        return [[
            'slug' => $founder['slug'], 'name' => $founder['name'], 'role' => $founder['role'],
            'specialty' => $founder['short_bio'], 'initials' => $founder['initials'], 'tone' => $founder['tone'], 'image' => $founder['image'],
        ]];
    }

    public static function articles(): array { return []; }

    private static function localized(array $variants): array
    {
        return $variants[Translator::locale()] ?? $variants['fa'];
    }
}

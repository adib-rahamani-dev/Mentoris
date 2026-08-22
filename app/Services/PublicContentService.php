<?php

declare(strict_types=1);

namespace App\Services;

final class PublicContentService
{
    public static function academyLines(): array
    {
        return [
            [
                'slug' => 'research', 'title' => 'پژوهش', 'en' => 'Research', 'tone' => 'violet', 'icon' => '⌁',
                'description' => 'تبدیل پرسش‌های واقعی حوزه سلامت روان به پژوهش‌های معتبر، قابل فهم و اثرگذار.',
                'promise' => 'از پرسش تا شواهد؛ از شواهد تا تصمیم بهتر.',
                'specializations' => [
                    ['slug' => 'research-methods', 'title' => 'روش‌های پژوهش', 'description' => 'طراحی مطالعه، نمونه‌گیری و تحلیل داده.'],
                    ['slug' => 'evidence-synthesis', 'title' => 'مرور شواهد', 'description' => 'خواندن نقادانه و ترکیب نتایج پژوهش‌ها.'],
                    ['slug' => 'science-communication', 'title' => 'ارتباطات علمی', 'description' => 'تبدیل یافته‌های تخصصی به محتوای روشن و مسئولانه.'],
                ],
            ],
            [
                'slug' => 'therapist-development', 'title' => 'توسعه درمانگر', 'en' => 'Therapist Development', 'tone' => 'magenta', 'icon' => 'Ψ',
                'description' => 'رشد هویت حرفه‌ای، مهارت‌های بالینی و ظرفیت خودبازتابی درمانگران در طول مسیر شغلی.',
                'promise' => 'درمانگر توانمند، یادگیرنده‌ای مادام‌العمر است.',
                'specializations' => [
                    ['slug' => 'clinical-supervision', 'title' => 'سوپرویژن بالینی', 'description' => 'بازاندیشی ساختاریافته بر فرایند درمان.'],
                    ['slug' => 'therapeutic-alliance', 'title' => 'اتحاد درمانی', 'description' => 'ساخت و ترمیم رابطه حرفه‌ای با مراجع.'],
                    ['slug' => 'therapist-self-care', 'title' => 'خودمراقبتی درمانگر', 'description' => 'پیشگیری از فرسودگی و حفظ کیفیت حضور حرفه‌ای.'],
                ],
            ],
            [
                'slug' => 'wellbeing', 'title' => 'بهزیستی', 'en' => 'Wellbeing', 'tone' => 'teal', 'icon' => '◉',
                'description' => 'دانش و ابزارهای کاربردی برای تنظیم هیجان، معنا، تاب‌آوری و کیفیت بهتر زندگی.',
                'promise' => 'بهزیستی، مهارتی برای ساختن زندگی متعادل‌تر است.',
                'specializations' => [
                    ['slug' => 'mindfulness', 'title' => 'ذهن‌آگاهی', 'description' => 'تمرین حضور، توجه و رابطه متفاوت با تجربه.'],
                    ['slug' => 'resilience', 'title' => 'تاب‌آوری', 'description' => 'سازگاری فعال با فشارها و تغییرات زندگی.'],
                    ['slug' => 'positive-psychology', 'title' => 'روان‌شناسی مثبت', 'description' => 'شناخت نقاط قوت، معنا و شکوفایی.'],
                ],
            ],
            [
                'slug' => 'therapy', 'title' => 'درمان', 'en' => 'Therapy', 'tone' => 'blue', 'icon' => '◇',
                'description' => 'آموزش رویکردهای درمانی مبتنی بر شواهد با تأکید بر مفهوم‌سازی، مهارت و اخلاق حرفه‌ای.',
                'promise' => 'دانش درمانی زمانی ارزشمند است که به تصمیم بالینی دقیق تبدیل شود.',
                'specializations' => [
                    ['slug' => 'schema-therapy', 'title' => 'طرحواره‌درمانی', 'description' => 'مفهوم‌سازی و مداخله بر الگوهای عمیق و پایدار.'],
                    ['slug' => 'cbt', 'title' => 'درمان شناختی رفتاری', 'description' => 'کار ساختاریافته با شناخت، هیجان و رفتار.'],
                    ['slug' => 'act', 'title' => 'درمان مبتنی بر پذیرش و تعهد', 'description' => 'انعطاف‌پذیری روان‌شناختی و عمل ارزش‌محور.'],
                ],
            ],
            [
                'slug' => 'assessment', 'title' => 'ارزیابی', 'en' => 'Assessment', 'tone' => 'amber', 'icon' => '⌕',
                'description' => 'ارزیابی روان‌شناختی دقیق، اخلاق‌مدار و یکپارچه برای تصمیم‌گیری حرفه‌ای بهتر.',
                'promise' => 'ارزیابی خوب، آغاز فهم درست مسئله است.',
                'specializations' => [
                    ['slug' => 'clinical-interview', 'title' => 'مصاحبه بالینی', 'description' => 'جمع‌آوری و سازمان‌دهی اطلاعات بالینی.'],
                    ['slug' => 'psychometrics', 'title' => 'روان‌سنجی', 'description' => 'انتخاب، اجرا و تفسیر ابزارهای معتبر.'],
                    ['slug' => 'case-formulation', 'title' => 'فرمول‌بندی مورد', 'description' => 'یکپارچه‌سازی داده‌ها در یک مدل قابل اقدام.'],
                ],
            ],
            [
                'slug' => 'public-mental-health', 'title' => 'سلامت روان عمومی', 'en' => 'Public Mental Health', 'tone' => 'rose', 'icon' => '◎',
                'description' => 'پیشگیری، ارتقای سواد سلامت روان و طراحی مداخلات اثرگذار در سطح جامعه.',
                'promise' => 'سلامت روان فقط مسئله فرد نیست؛ مسئولیتی اجتماعی است.',
                'specializations' => [
                    ['slug' => 'mental-health-literacy', 'title' => 'سواد سلامت روان', 'description' => 'فهم بهتر نشانه‌ها، خدمات و مسیر کمک‌گرفتن.'],
                    ['slug' => 'prevention', 'title' => 'پیشگیری', 'description' => 'طراحی مداخلات پیشگیرانه برای گروه‌های مختلف.'],
                    ['slug' => 'community-interventions', 'title' => 'مداخلات جامعه‌محور', 'description' => 'پاسخ‌های مشارکتی و متناسب با زمینه اجتماعی.'],
                ],
            ],
            [
                'slug' => 'professional-development', 'title' => 'توسعه حرفه‌ای', 'en' => 'Professional Development', 'tone' => 'indigo', 'icon' => '△',
                'description' => 'مهارت‌های مکمل برای ساختن مسیر شغلی پایدار، اخلاقی و اثرگذار در حرفه‌های یاری‌رسان.',
                'promise' => 'حرفه‌ای‌بودن، ترکیب مهارت، مسئولیت و جهت‌مندی است.',
                'specializations' => [
                    ['slug' => 'career-design', 'title' => 'طراحی مسیر شغلی', 'description' => 'تصمیم‌گیری آگاهانه برای مراحل مختلف حرفه.'],
                    ['slug' => 'ethical-practice', 'title' => 'عمل اخلاقی', 'description' => 'مرزبندی، مسئولیت و تصمیم‌گیری اخلاقی.'],
                    ['slug' => 'leadership', 'title' => 'رهبری حرفه‌ای', 'description' => 'هدایت تیم، پروژه و تغییر در سازمان‌های انسانی.'],
                ],
            ],
        ];
    }

    public static function academyLine(string $slug): ?array
    {
        foreach (self::academyLines() as $line) {
            if ($line['slug'] === $slug) {
                $line['programs'] = array_values(array_filter(self::programs(), fn (array $program): bool => $program['line_slug'] === $slug));
                return $line;
            }
        }
        return null;
    }

    public static function specializations(): array
    {
        $items = [];
        foreach (self::academyLines() as $line) {
            foreach ($line['specializations'] as $specialization) {
                $items[] = [...$specialization, 'line_slug' => $line['slug'], 'line_title' => $line['title'], 'line_tone' => $line['tone']];
            }
        }
        return $items;
    }

    public static function specialization(string $slug): ?array
    {
        foreach (self::specializations() as $specialization) {
            if ($specialization['slug'] === $slug) {
                $specialization['line'] = self::academyLine($specialization['line_slug']);
                return $specialization;
            }
        }
        return null;
    }

    public static function programs(): array
    {
        return [
            [
                'slug' => 'research-practitioner', 'line_slug' => 'research', 'tone' => 'violet', 'title' => 'پژوهشگر عمل‌گرا در سلامت روان', 'subtitle' => 'Research Practitioner Program',
                'short_description' => 'مسیر تبدیل یک پرسش حرفه‌ای به مطالعه‌ای معتبر و قابل انتشار.',
                'description' => 'برنامه‌ای پروژه‌محور برای کسانی که می‌خواهند مسئله‌های واقعی حوزه سلامت روان را با روش علمی بررسی کنند و نتایج را به تصمیم، مقاله یا محصول دانشی تبدیل کنند.',
                'target_audience' => ['دانشجویان تحصیلات تکمیلی روان‌شناسی', 'درمانگران علاقه‌مند به پژوهش', 'اعضای تیم‌های تحقیق و توسعه'],
                'objectives' => ['تعریف پرسش پژوهشی دقیق و قابل آزمون', 'انتخاب طرح و روش تحلیل متناسب', 'خواندن نقادانه شواهد و گزارش شفاف یافته‌ها', 'ساخت یک پروپوزال یا گزارش پژوهشی نهایی'],
                'duration' => '۶ ماه', 'level' => 'میانی تا پیشرفته', 'format' => 'ترکیبی',
                'course_slugs' => ['research-design', 'evidence-review'], 'event_slugs' => ['research-lab'], 'mentor_slugs' => ['mohammad-rezaei'],
            ],
            [
                'slug' => 'therapist-growth', 'line_slug' => 'therapist-development', 'tone' => 'magenta', 'title' => 'مسیر رشد درمانگر', 'subtitle' => 'Therapist Development Path',
                'short_description' => 'رشد یکپارچه مهارت، هویت حرفه‌ای و مراقبت از خود درمانگر.',
                'description' => 'یک مسیر طولی برای درمانگران ابتدای راه و حرفه‌ای که می‌خواهند کیفیت حضور بالینی، رابطه درمانی و تصمیم‌های خود را با سوپرویژن و تمرین هدفمند ارتقا دهند.',
                'target_audience' => ['روان‌شناسان و مشاوران تازه‌کار', 'درمانگران دارای پرونده فعال', 'دانشجویان سال پایانی دوره‌های حرفه‌ای'],
                'objectives' => ['تقویت خودبازتابی و هویت درمانگر', 'بهبود اتحاد و گفتگوی درمانی', 'مدیریت مرزها و موقعیت‌های دشوار', 'طراحی برنامه رشد حرفه‌ای شخصی'],
                'duration' => '۹ ماه', 'level' => 'تمام سطوح', 'format' => 'گروهی + سوپرویژن',
                'course_slugs' => ['clinical-interview', 'therapeutic-alliance'], 'event_slugs' => ['supervision-circle'], 'mentor_slugs' => ['arash-novin', 'sara-mohammadi'],
            ],
            [
                'slug' => 'wellbeing-facilitator', 'line_slug' => 'wellbeing', 'tone' => 'teal', 'title' => 'تسهیل‌گر بهزیستی', 'subtitle' => 'Wellbeing Facilitator',
                'short_description' => 'طراحی و اجرای تجربه‌های ارتقای بهزیستی برای فرد و گروه.',
                'description' => 'برنامه‌ای کاربردی برای یادگیری مبانی بهزیستی، ذهن‌آگاهی و تاب‌آوری و تبدیل آن‌ها به تمرین‌ها و مداخلات ایمن برای گروه‌های مختلف.',
                'target_audience' => ['مربیان و تسهیل‌گران', 'متخصصان منابع انسانی', 'روان‌شناسان علاقه‌مند به مداخلات غیربالینی'],
                'objectives' => ['شناخت مدل‌های معتبر بهزیستی', 'تسهیل تمرین‌های ذهن‌آگاهی', 'طراحی برنامه تاب‌آوری گروهی', 'ارزیابی اثر مداخله‌های بهزیستی'],
                'duration' => '۴ ماه', 'level' => 'مقدماتی تا میانی', 'format' => 'آنلاین',
                'course_slugs' => ['mindfulness', 'resilience-skills'], 'event_slugs' => ['wellbeing-day'], 'mentor_slugs' => ['sara-mohammadi'],
            ],
            [
                'slug' => 'evidence-based-therapist', 'line_slug' => 'therapy', 'tone' => 'blue', 'title' => 'درمانگر مبتنی بر شواهد', 'subtitle' => 'Evidence-Based Therapist',
                'short_description' => 'از مفهوم‌سازی مورد تا انتخاب مداخله و پایش پیشرفت درمان.',
                'description' => 'مسیر تخصصی برای ادغام شواهد پژوهشی، ترجیحات مراجع و تجربه بالینی در تصمیم‌های درمانی؛ با تمرکز بر رویکردهای CBT، ACT و طرحواره‌درمانی.',
                'target_audience' => ['روان‌شناسان بالینی', 'مشاوران و روان‌درمانگران', 'کارورزان دوره‌های درمانی'],
                'objectives' => ['ساخت مفهوم‌سازی منسجم مورد', 'انتخاب مداخله متناسب با مسئله', 'اجرای تکنیک‌های محوری سه رویکرد', 'پایش نتیجه و بازنگری برنامه درمان'],
                'duration' => '۱۲ ماه', 'level' => 'پیشرفته', 'format' => 'ترکیبی + کارورزی',
                'course_slugs' => ['schema-therapy', 'cbt-workshop', 'act-foundations'], 'event_slugs' => ['cbt-intensive', 'schema-open-day'], 'mentor_slugs' => ['arash-novin', 'sara-mohammadi'],
            ],
            [
                'slug' => 'assessment-practitioner', 'line_slug' => 'assessment', 'tone' => 'amber', 'title' => 'متخصص ارزیابی یکپارچه', 'subtitle' => 'Assessment Practitioner',
                'short_description' => 'جمع‌آوری، یکپارچه‌سازی و تفسیر مسئولانه داده‌های روان‌شناختی.',
                'description' => 'یک برنامه مهارت‌محور برای انجام مصاحبه، انتخاب ابزار، تفسیر یافته‌ها و نوشتن گزارش‌هایی که به تصمیم حرفه‌ای و درمانی کمک می‌کنند.',
                'target_audience' => ['روان‌شناسان و مشاوران', 'کارشناسان مراکز ارزیابی', 'دانشجویان روان‌سنجی و بالینی'],
                'objectives' => ['اجرای مصاحبه بالینی ساختاریافته', 'انتخاب ابزار معتبر و متناسب', 'ترکیب داده‌های چندمنبعی', 'نوشتن گزارش روشن و اخلاق‌مدار'],
                'duration' => '۵ ماه', 'level' => 'میانی', 'format' => 'حضوری + تمرین',
                'course_slugs' => ['clinical-interview', 'psychometrics'], 'event_slugs' => ['assessment-clinic'], 'mentor_slugs' => ['narges-kazemi'],
            ],
            [
                'slug' => 'community-mental-health', 'line_slug' => 'public-mental-health', 'tone' => 'rose', 'title' => 'طراح مداخلات سلامت روان جامعه', 'subtitle' => 'Community Mental Health Designer',
                'short_description' => 'طراحی پاسخ‌های پیشگیرانه و جامعه‌محور برای مسائل سلامت روان.',
                'description' => 'مسیر میان‌رشته‌ای برای شناخت نیاز جامعه، طراحی مداخلات پیشگیرانه، مشارکت ذی‌نفعان و سنجش اثر برنامه‌های سلامت روان عمومی.',
                'target_audience' => ['فعالان اجتماعی و سازمان‌های مردم‌نهاد', 'متخصصان سلامت و آموزش', 'مدیران پروژه‌های اجتماعی'],
                'objectives' => ['تحلیل نیاز و بافت جامعه', 'طراحی مداخله مشارکتی', 'تولید محتوای سواد سلامت روان', 'تعریف شاخص‌های ارزیابی اثر'],
                'duration' => '۶ ماه', 'level' => 'میانی', 'format' => 'پروژه‌محور',
                'course_slugs' => ['mental-health-literacy', 'program-evaluation'], 'event_slugs' => ['public-health-forum'], 'mentor_slugs' => ['mohammad-rezaei', 'narges-kazemi'],
            ],
            [
                'slug' => 'professional-leadership', 'line_slug' => 'professional-development', 'tone' => 'indigo', 'title' => 'رهبری و توسعه حرفه‌ای', 'subtitle' => 'Professional Leadership',
                'short_description' => 'ساخت مسیر شغلی پایدار و هدایت پروژه‌های انسانی با مسئولیت حرفه‌ای.',
                'description' => 'برنامه‌ای برای متخصصانی که می‌خواهند فراتر از مهارت تخصصی، توان تصمیم‌گیری اخلاقی، طراحی مسیر شغلی و رهبری تیم و پروژه را توسعه دهند.',
                'target_audience' => ['متخصصان میانی و ارشد', 'مدیران مراکز خدمات انسانی', 'مدرسان و رهبران تیم'],
                'objectives' => ['طراحی نقشه رشد حرفه‌ای', 'تصمیم‌گیری در دوراهی‌های اخلاقی', 'رهبری گفتگوی تیمی و بازخورد', 'ساخت یک پروژه توسعه حرفه‌ای'],
                'duration' => '۴ ماه', 'level' => 'میانی تا ارشد', 'format' => 'هیبرید',
                'course_slugs' => ['career-design', 'ethical-practice'], 'event_slugs' => ['leadership-roundtable'], 'mentor_slugs' => ['arash-novin'],
            ],
        ];
    }

    public static function program(string $slug): ?array
    {
        foreach (self::programs() as $program) {
            if ($program['slug'] === $slug) {
                $program['line'] = self::academyLine($program['line_slug']);
                $program['related_courses'] = self::bySlugs(self::courses(), $program['course_slugs']);
                $program['related_events'] = self::bySlugs(self::events(), $program['event_slugs']);
                $program['related_mentors'] = self::bySlugs(self::mentors(), $program['mentor_slugs']);
                return $program;
            }
        }
        return null;
    }

    public static function courses(): array
    {
        $courses = [
            ['schema-therapy', 'دوره جامع درمان طرحواره', 'Schema Therapy', 'درمان', 'therapy', '۲۴ ساعت', 'پیشرفته', '۴٬۹۰۰٬۰۰۰ تومان', 'violet', 'active', 'هیبرید', 'شنبه‌ها، ۱۷ تا ۲۰', 32, 24, 'arash-novin', 'therapy', 'از مفهوم‌سازی تا طراحی مداخله در طرحواره‌درمانی با تمرین مورد و بازخورد مدرس.'],
            ['cbt-workshop', 'کارگاه درمان شناختی رفتاری', 'CBT Workshop', 'درمان', 'therapy', '۱۲ ساعت', 'متوسط', '۱٬۸۰۰٬۰۰۰ تومان', 'magenta', 'full', 'حضوری', '۲۵ و ۲۶ مهر، ۹ تا ۱۵', 24, 24, 'arash-novin', 'therapy', 'کارگاهی عملی برای مفهوم‌سازی شناختی، ساختار جلسه و انتخاب تکنیک متناسب با مراجع.'],
            ['mindfulness', 'ذهن‌آگاهی و خودشناسی', 'Mindfulness Course', 'بهزیستی', 'wellbeing', '۱۰ ساعت', 'مقدماتی', '۲٬۴۰۰٬۰۰۰ تومان', 'teal', 'active', 'آنلاین', 'دوشنبه‌ها، ۱۹ تا ۲۱', 60, 41, 'sara-mohammadi', 'wellbeing', 'تمرین‌های شواهدمحور ذهن‌آگاهی برای تنظیم هیجان، حضور و مراقبت پایدار از خود.'],
            ['clinical-interview', 'اصول مصاحبه بالینی', 'Clinical Interview', 'ارزیابی', 'assessment', '۱۴ ساعت', 'متوسط', '۱٬۶۰۰٬۰۰۰ تومان', 'amber', 'active', 'هیبرید', 'چهارشنبه‌ها، ۱۷:۳۰ تا ۲۰', 28, 17, 'narges-kazemi', 'assessment', 'ساختار مصاحبه بالینی، جمع‌آوری داده و تبدیل گفتگو به فرضیه‌های قابل بررسی.'],
            ['research-design', 'طراحی پژوهش کاربردی', 'Applied Research Design', 'پژوهش', 'research', '۱۸ ساعت', 'متوسط', '۲٬۲۰۰٬۰۰۰ تومان', 'violet', 'active', 'آنلاین', 'پنجشنبه‌ها، ۱۸ تا ۲۱', 35, 19, 'mohammad-rezaei', 'research', 'از مسئله حرفه‌ای تا پرسش، طراحی مطالعه و یک پروتکل پژوهشی قابل اجرا.'],
            ['evidence-review', 'خواندن و مرور شواهد', 'Evidence Review', 'پژوهش', 'research', '۱۰ ساعت', 'متوسط', '۱٬۴۰۰٬۰۰۰ تومان', 'blue', 'completed', 'آنلاین', 'دوره قبل: تابستان ۱۴۰۵', 45, 39, 'mohammad-rezaei', 'research', 'خواندن نقادانه مقاله و ترکیب شواهد برای تصمیم‌گیری حرفه‌ای و بالینی.'],
            ['therapeutic-alliance', 'اتحاد و رابطه درمانی', 'Therapeutic Alliance', 'توسعه درمانگر', 'therapist-development', '۱۲ ساعت', 'تمام سطوح', '۱٬۹۰۰٬۰۰۰ تومان', 'magenta', 'coming-soon', 'حضوری', 'آذر ۱۴۰۵؛ زمان دقیق به‌زودی', 24, 8, 'sara-mohammadi', 'therapist-development', 'ساخت، پایش و ترمیم رابطه درمانی در موقعیت‌های دشوار و حساس.'],
            ['resilience-skills', 'مهارت‌های تاب‌آوری', 'Resilience Skills', 'بهزیستی', 'wellbeing', '۸ ساعت', 'مقدماتی', '۹۵۰٬۰۰۰ تومان', 'teal', 'active', 'آنلاین', 'جمعه‌ها، ۱۰ تا ۱۲', 80, 53, 'sara-mohammadi', 'wellbeing', 'چارچوبی کاربردی برای بازگشت‌پذیری، انعطاف و مراقبت از انرژی روانی.'],
            ['act-foundations', 'مبانی درمان ACT', 'ACT Foundations', 'درمان', 'therapy', '۱۶ ساعت', 'متوسط', '۲٬۶۰۰٬۰۰۰ تومان', 'blue', 'coming-soon', 'آنلاین', 'دی ۱۴۰۵؛ زمان دقیق به‌زودی', 40, 11, 'arash-novin', 'therapy', 'آشنایی منسجم با انعطاف‌پذیری روان‌شناختی و فرایندهای محوری ACT.'],
            ['psychometrics', 'روان‌سنجی کاربردی', 'Applied Psychometrics', 'ارزیابی', 'assessment', '۲۰ ساعت', 'پیشرفته', '۳٬۱۰۰٬۰۰۰ تومان', 'amber', 'full', 'هیبرید', 'یکشنبه‌ها، ۱۷ تا ۲۰', 22, 22, 'narges-kazemi', 'assessment', 'انتخاب، اجرا و تفسیر ابزارهای سنجش با تمرکز بر اعتبار تصمیم حرفه‌ای.'],
            ['mental-health-literacy', 'سواد سلامت روان', 'Mental Health Literacy', 'سلامت عمومی', 'public-mental-health', '۱۰ ساعت', 'مقدماتی', 'رایگان', 'rose', 'active', 'آنلاین', 'دسترسی خودآموز + جلسه زنده', 250, 187, 'narges-kazemi', 'public-mental-health', 'دانش پایه و زبان مشترک برای شناخت، پیشگیری و ارجاع مسئولانه در سلامت روان.'],
            ['program-evaluation', 'ارزیابی برنامه‌های اجتماعی', 'Program Evaluation', 'سلامت عمومی', 'public-mental-health', '۱۴ ساعت', 'متوسط', '۱٬۷۰۰٬۰۰۰ تومان', 'rose', 'completed', 'آنلاین', 'دوره قبل: بهار ۱۴۰۵', 40, 34, 'mohammad-rezaei', 'public-mental-health', 'طراحی مدل منطقی، شاخص و ارزیابی اثربخشی برای برنامه‌های سلامت و اجتماعی.'],
            ['career-design', 'طراحی مسیر شغلی', 'Career Design', 'توسعه حرفه‌ای', 'professional-development', '۸ ساعت', 'تمام سطوح', '۱٬۲۰۰٬۰۰۰ تومان', 'indigo', 'active', 'آنلاین', 'سه‌شنبه‌ها، ۱۹ تا ۲۱', 70, 46, 'arash-novin', 'professional-development', 'طراحی مسیر حرفه‌ای مبتنی بر ارزش‌ها، توانمندی‌ها و آزمایش‌های کم‌ریسک شغلی.'],
            ['ethical-practice', 'تصمیم‌گیری اخلاقی', 'Ethical Practice', 'توسعه حرفه‌ای', 'professional-development', '۸ ساعت', 'متوسط', '۱٬۲۰۰٬۰۰۰ تومان', 'indigo', 'coming-soon', 'هیبرید', 'بهمن ۱۴۰۵؛ زمان دقیق به‌زودی', 35, 6, 'arash-novin', 'professional-development', 'مدلی روشن برای تحلیل تعارض‌های اخلاقی، مستندسازی و تصمیم مسئولانه.'],
        ];

        $audiences = [
            'therapy' => ['روان‌شناسان و مشاوران دارای آموزش پایه درمان', 'دانشجویان تحصیلات تکمیلی رشته‌های مرتبط', 'درمانگرانی که به دنبال چارچوب عملی هستند'],
            'wellbeing' => ['متخصصان حوزه سلامت و آموزش', 'تسهیل‌گران و مدیران تیم', 'علاقه‌مندان به رشد و مراقبت از خود'],
            'assessment' => ['روان‌شناسان و مشاوران', 'دانشجویان ارزیابی و روان‌سنجی', 'متخصصان مراکز بالینی و آموزشی'],
            'research' => ['پژوهشگران و دانشجویان تحصیلات تکمیلی', 'متخصصان علاقه‌مند به کار شواهدمحور', 'اعضای تیم‌های تحقیق و توسعه'],
            'therapist-development' => ['درمانگران تازه‌کار و باتجربه', 'دانشجویان کارورزی بالینی', 'سوپروایزرها و مدرسان مهارت بالینی'],
            'public-mental-health' => ['فعالان سلامت و سازمان‌های اجتماعی', 'مدیران و طراحان برنامه', 'دانشجویان و علاقه‌مندان سلامت عمومی'],
            'professional-development' => ['متخصصان در حال طراحی مسیر شغلی', 'مدیران و رهبران تیم‌های انسانی', 'مدرسان، مشاوران و فریلنسرها'],
        ];
        $curricula = [
            'therapy' => ['نقشه نظری و فرمول‌بندی مورد', 'ساختار جلسه و رابطه درمانی', 'انتخاب و اجرای مداخله', 'تمرین مورد، بازخورد و برنامه ادامه'],
            'wellbeing' => ['شناخت الگوهای فشار و منابع فردی', 'تمرین‌های تنظیم توجه و هیجان', 'طراحی عادت‌های پایدار', 'برنامه شخصی مراقبت و پیگیری'],
            'assessment' => ['تعریف سؤال و قرارداد ارزیابی', 'مصاحبه، مشاهده و انتخاب ابزار', 'تفسیر چندمنبعی داده‌ها', 'گزارش، بازخورد و تصمیم حرفه‌ای'],
            'research' => ['تعریف مسئله و جستجوی شواهد', 'پرسش، فرضیه و طراحی مطالعه', 'کیفیت داده و تحلیل', 'پروتکل، گزارش و کاربرد یافته‌ها'],
            'therapist-development' => ['هویت و حضور حرفه‌ای درمانگر', 'اتحاد، گسست و ترمیم', 'خودبازتابی و استفاده از بازخورد', 'برنامه رشد و سوپرویژن'],
            'public-mental-health' => ['شناخت مسئله در سطح جمعیت', 'طراحی مداخله و مدل منطقی', 'شاخص‌ها و ارزیابی پیامد', 'ارتباط عمومی و اقدام مسئولانه'],
            'professional-development' => ['ارزش‌ها، نقش‌ها و توانمندی‌ها', 'تحلیل موقعیت و تصمیم اخلاقی', 'طراحی آزمایش حرفه‌ای', 'نقشه اقدام و بازبینی مسیر'],
        ];
        $priceAmounts = [
            'schema-therapy' => 4900000, 'cbt-workshop' => 1800000, 'mindfulness' => 2400000,
            'clinical-interview' => 1600000, 'research-design' => 2200000, 'evidence-review' => 1400000,
            'therapeutic-alliance' => 1900000, 'resilience-skills' => 950000, 'act-foundations' => 2600000,
            'psychometrics' => 3100000, 'mental-health-literacy' => 0, 'program-evaluation' => 1700000,
            'career-design' => 1200000, 'ethical-practice' => 1200000,
        ];

        return array_map(static function (array $item) use ($audiences, $curricula, $priceAmounts): array {
            [$slug, $title, $subtitle, $category, $categorySlug, $duration, $level, $price, $tone, $status, $type, $schedule, $capacity, $enrolled, $instructorSlug, $lineSlug, $description] = $item;
            return compact('slug', 'title', 'subtitle', 'category', 'duration', 'level', 'price', 'tone', 'status', 'type', 'schedule', 'capacity', 'enrolled', 'description') + [
                'category_slug' => $categorySlug,
                'price_amount' => $priceAmounts[$slug],
                'currency' => 'IRT',
                'instructor_slug' => $instructorSlug,
                'line_slug' => $lineSlug,
                'audience' => $audiences[$categorySlug],
                'curriculum' => array_map(static fn (string $title, int $index): array => ['title' => $title, 'duration' => 'جلسه ' . ($index + 1), 'description' => 'آموزش مفهومی، نمونه کاربردی و تمرین هدایت‌شده برای تثبیت این بخش.'], $curricula[$categorySlug], array_keys($curricula[$categorySlug])),
                'faq' => [
                    ['question' => 'آیا این دوره پیش‌نیاز دارد؟', 'answer' => $level === 'مقدماتی' || $level === 'تمام سطوح' ? 'پیش‌نیاز رسمی ندارد و منابع شروع در اختیار شرکت‌کنندگان قرار می‌گیرد.' : 'آشنایی پایه با مفاهیم حوزه و تجربه تحصیلی یا حرفه‌ای مرتبط پیشنهاد می‌شود.'],
                    ['question' => 'جلسات ضبط می‌شوند؟', 'answer' => 'دسترسی به ضبط جلسات آموزشی مطابق سیاست هر دوره و برای مدت محدود در پنل دوره ارائه می‌شود.'],
                    ['question' => 'شرایط دریافت گواهی چیست؟', 'answer' => 'حضور مؤثر، انجام تمرین‌ها و تکمیل ارزیابی پایانی برای صدور گواهی Mentoris ضروری است.'],
                ],
                'certificate' => 'گواهی پایان دوره Mentoris با کد رهگیری داخلی',
                'certificate_available' => true,
            ];
        }, $courses);
    }

    public static function course(string $slug): ?array
    {
        $bios = [
            'arash-novin' => 'روان‌شناس بالینی و مدرس رویکردهای موج دوم و سوم با تمرکز بر تبدیل نظریه به تصمیم‌های قابل اجرا در جلسه.',
            'sara-mohammadi' => 'روان‌درمانگر و تسهیل‌گر یادگیری گروهی با تجربه آموزش رابطه درمانی، ذهن‌آگاهی و مراقبت حرفه‌ای.',
            'mohammad-rezaei' => 'پژوهشگر علوم اعصاب و روش‌شناسی که بر سواد شواهد، طراحی پژوهش و کاربرد یافته‌ها تمرکز دارد.',
            'narges-kazemi' => 'روان‌شناس و متخصص ارزیابی با تجربه کار بالینی، روان‌سنجی و برنامه‌های ارتقای سواد سلامت روان.',
        ];
        foreach (self::courses() as $course) {
            if ($course['slug'] === $slug) {
                $course['instructor'] = self::bySlugs(self::mentors(), [$course['instructor_slug']])[0] ?? null;
                $course['instructor_bio'] = $bios[$course['instructor_slug']] ?? '';
                $course['line'] = self::academyLine($course['line_slug']);
                $course['related_programs'] = array_values(array_filter(self::programs(), fn (array $program): bool => in_array($slug, $program['course_slugs'], true)));
                $course['available'] = max(0, $course['capacity'] - $course['enrolled']);
                $course['status_label'] = self::courseStatusLabels()[$course['status']] ?? $course['status'];
                $course['can_enroll'] = $course['status'] === 'active' && $course['available'] > 0;
                return $course;
            }
        }
        return null;
    }

    public static function courseCategories(): array
    {
        return [
            'therapy' => 'درمان', 'therapist-development' => 'توسعه درمانگر', 'wellbeing' => 'بهزیستی',
            'assessment' => 'ارزیابی', 'research' => 'پژوهش', 'public-mental-health' => 'سلامت عمومی',
            'professional-development' => 'توسعه حرفه‌ای',
        ];
    }

    public static function courseStatusLabels(): array
    {
        return ['active' => 'فعال', 'coming-soon' => 'به‌زودی', 'full' => 'تکمیل ظرفیت', 'completed' => 'پایان‌یافته'];
    }

    public static function events(): array
    {
        return [
            [
                'slug' => 'cbt-intensive', 'day' => '۲۵', 'month' => 'مهر', 'date' => '۲۵ مهر ۱۴۰۵', 'date_iso' => '2026-10-17', 'time' => '۹:۰۰ تا ۱۷:۰۰',
                'title' => 'کارگاه فشرده CBT', 'type' => 'کارگاه تخصصی', 'mode' => 'offline', 'location' => 'تهران، مرکز همایش Mentoris', 'tone' => 'magenta',
                'status' => 'registration-open', 'capacity' => 24, 'registered' => 18, 'instructor_slug' => 'arash-novin', 'line_slug' => 'therapy',
                'short_description' => 'یک روز تمرین فشرده مفهوم‌سازی، طراحی جلسه و تکنیک‌های محوری CBT.',
                'description' => 'این کارگاه برای تبدیل دانش نظری CBT به تصمیم‌های قابل اجرا در جلسه درمان طراحی شده است. شرکت‌کنندگان روی نمونه‌های واقعی کار می‌کنند و بازخورد ساختاریافته می‌گیرند.',
                'highlights' => ['مفهوم‌سازی شناختی یک مورد واقعی', 'تمرین طراحی جلسه و تکلیف', 'بازخورد مستقیم مدرس', 'گواهی حضور Mentoris'],
            ],
            [
                'slug' => 'schema-open-day', 'day' => '۱۸', 'month' => 'آبان', 'date' => '۱۸ آبان ۱۴۰۵', 'date_iso' => '2026-11-09', 'time' => '۱۸:۰۰ تا ۲۰:۰۰',
                'title' => 'روز باز طرحواره‌درمانی', 'type' => 'نشست معرفی', 'mode' => 'online', 'location' => 'آنلاین در Mentoris Live', 'tone' => 'violet',
                'status' => 'upcoming', 'capacity' => 100, 'registered' => 34, 'instructor_slug' => 'arash-novin', 'line_slug' => 'therapy',
                'short_description' => 'آشنایی با مسیر یادگیری طرحواره‌درمانی و پاسخ به پرسش‌های متقاضیان.',
                'description' => 'در این نشست آزاد، ساختار مسیر طرحواره‌درمانی، پیش‌نیازها، تمرین‌ها و شیوه همراهی منتورها معرفی می‌شود. ثبت‌نام به‌زودی باز خواهد شد.',
                'highlights' => ['معرفی نقشه راه یادگیری', 'گفتگو با مدرس Program', 'پاسخ به پرسش‌های ورود', 'دسترسی آنلاین رایگان'],
            ],
            [
                'slug' => 'wellbeing-day', 'day' => '۰۲', 'month' => 'آذر', 'date' => '۲ آذر ۱۴۰۵', 'date_iso' => '2026-11-23', 'time' => '۱۰:۰۰ تا ۱۶:۰۰',
                'title' => 'روز بهزیستی و ذهن‌آگاهی', 'type' => 'همایش تجربه‌محور', 'mode' => 'hybrid', 'location' => 'تهران + پخش آنلاین', 'tone' => 'teal',
                'status' => 'full', 'capacity' => 80, 'registered' => 80, 'instructor_slug' => 'sara-mohammadi', 'line_slug' => 'wellbeing',
                'short_description' => 'یک روز برای تجربه تمرین‌های ذهن‌آگاهی، تاب‌آوری و گفتگوی جمعی.',
                'description' => 'رویدادی تعاملی برای تجربه عملی ابزارهای بهزیستی و آشنایی با کاربرد آن‌ها در زندگی و محیط کار. ظرفیت این دوره تکمیل شده است.',
                'highlights' => ['تمرین‌های هدایت‌شده ذهن‌آگاهی', 'پنل تجربه‌های واقعی', 'شبکه‌سازی با تسهیل‌گران', 'بسته تمرین دیجیتال'],
            ],
            [
                'slug' => 'research-lab', 'day' => '۱۲', 'month' => 'آذر', 'date' => '۱۲ آذر ۱۴۰۵', 'date_iso' => '2026-12-03', 'time' => '۱۶:۰۰ تا ۱۹:۰۰',
                'title' => 'آزمایشگاه ایده پژوهش', 'type' => 'نشست عملی', 'mode' => 'online', 'location' => 'آنلاین در Mentoris Live', 'tone' => 'violet',
                'status' => 'registration-open', 'capacity' => 20, 'registered' => 12, 'instructor_slug' => 'mohammad-rezaei', 'line_slug' => 'research',
                'short_description' => 'ایده خام خود را به پرسش پژوهشی روشن و قابل بررسی تبدیل کنید.',
                'description' => 'در یک گروه کوچک، مسئله حرفه‌ای خود را تعریف می‌کنید، بازخورد می‌گیرید و با یک چارچوب عملی برای ادامه پژوهش رویداد را ترک می‌کنید.',
                'highlights' => ['کلینیک ایده در گروه کوچک', 'بازخورد پژوهشگر', 'قالب تعریف مسئله', 'فرصت معرفی در Research Community'],
            ],
            [
                'slug' => 'supervision-circle', 'day' => '۲۰', 'month' => 'آذر', 'date' => '۲۰ آذر ۱۴۰۵', 'date_iso' => '2026-12-11', 'time' => '۱۷:۰۰ تا ۲۰:۰۰',
                'title' => 'حلقه سوپرویژن درمانگران', 'type' => 'نشست گروهی', 'mode' => 'offline', 'location' => 'تهران، فضای آموزشی Mentoris', 'tone' => 'magenta',
                'status' => 'registration-open', 'capacity' => 16, 'registered' => 13, 'instructor_slug' => 'sara-mohammadi', 'line_slug' => 'therapist-development',
                'short_description' => 'فضایی امن برای مرور مورد، خودبازتابی و یادگیری از تجربه همکاران.',
                'description' => 'نشستی محرمانه و ساختاریافته برای درمانگرانی که می‌خواهند یک موقعیت دشوار بالینی را با همراهی سوپروایزر و گروه بررسی کنند.',
                'highlights' => ['مرور یک مورد منتخب', 'تمرین خودبازتابی', 'چارچوب بازخورد همکارانه', 'ظرفیت محدود گروهی'],
            ],
            [
                'slug' => 'assessment-clinic', 'day' => '۰۵', 'month' => 'دی', 'date' => '۵ دی ۱۴۰۵', 'date_iso' => '2026-12-26', 'time' => '۹:۳۰ تا ۱۳:۳۰',
                'title' => 'کلینیک مورد ارزیابی', 'type' => 'تمرین حضوری', 'mode' => 'offline', 'location' => 'تهران، لابراتوار Assessment', 'tone' => 'amber',
                'status' => 'upcoming', 'capacity' => 20, 'registered' => 5, 'instructor_slug' => 'narges-kazemi', 'line_slug' => 'assessment',
                'short_description' => 'از داده‌های پراکنده تا فرمول‌بندی و گزارش ارزیابی قابل اقدام.',
                'description' => 'یک مورد آموزشی را از مصاحبه تا انتخاب ابزار، تفسیر و گزارش دنبال می‌کنیم. زمان بازشدن ثبت‌نام از طریق خبرنامه اعلام می‌شود.',
                'highlights' => ['مصاحبه نمایشی', 'انتخاب ابزار ارزیابی', 'تفسیر چندمنبعی', 'نمونه گزارش حرفه‌ای'],
            ],
            [
                'slug' => 'public-health-forum', 'day' => '۱۴', 'month' => 'مرداد', 'date' => '۱۴ مرداد ۱۴۰۵', 'date_iso' => '2026-08-05', 'time' => '۱۷:۰۰ تا ۱۹:۰۰',
                'title' => 'فروم سلامت روان جامعه', 'type' => 'گفتگوی تخصصی', 'mode' => 'online', 'location' => 'آرشیو Mentoris Live', 'tone' => 'rose',
                'status' => 'completed', 'capacity' => 250, 'registered' => 212, 'instructor_slug' => 'mohammad-rezaei', 'line_slug' => 'public-mental-health',
                'short_description' => 'گفتگویی میان‌رشته‌ای درباره مسئولیت اجتماعی در سلامت روان.',
                'description' => 'این فروم برگزار شده و نسخه ضبط‌شده آن به‌زودی در کتابخانه Community منتشر می‌شود.',
                'highlights' => ['پنل میان‌رشته‌ای', 'پرسش و پاسخ عمومی', 'مطالعه مورد جامعه‌محور', 'دسترسی آتی به ضبط رویداد'],
            ],
            [
                'slug' => 'leadership-roundtable', 'day' => '۲۸', 'month' => 'دی', 'date' => '۲۸ دی ۱۴۰۵', 'date_iso' => '2027-01-18', 'time' => '۱۷:۰۰ تا ۱۹:۳۰',
                'title' => 'میزگرد رهبری حرفه‌ای', 'type' => 'میزگرد', 'mode' => 'hybrid', 'location' => 'تهران + پخش آنلاین', 'tone' => 'indigo',
                'status' => 'canceled', 'capacity' => 30, 'registered' => 0, 'instructor_slug' => 'arash-novin', 'line_slug' => 'professional-development',
                'short_description' => 'گفتگو درباره رهبری تیم‌ها و پروژه‌های خدمات انسانی.',
                'description' => 'این رویداد به دلیل تغییر در برنامه مدرس لغو شده است. تاریخ جایگزین پس از هماهنگی اعلام خواهد شد.',
                'highlights' => ['رهبری موقعیت‌های پیچیده', 'فرهنگ بازخورد', 'تصمیم‌گیری اخلاقی', 'شبکه‌سازی مدیران'],
            ],
        ];
    }

    public static function event(string $slug): ?array
    {
        foreach (self::events() as $event) {
            if ($event['slug'] === $slug) {
                $event['instructor'] = self::bySlugs(self::mentors(), [$event['instructor_slug']])[0] ?? null;
                $event['line'] = self::academyLine($event['line_slug']);
                $event['related_programs'] = array_values(array_filter(self::programs(), fn (array $program): bool => in_array($slug, $program['event_slugs'], true)));
                $event['available'] = max(0, $event['capacity'] - $event['registered']);
                $event['can_register'] = $event['status'] === 'registration-open' && $event['available'] > 0;
                $event['status_label'] = self::eventStatusLabels()[$event['status']] ?? $event['status'];
                $event['mode_label'] = self::eventModeLabels()[$event['mode']] ?? $event['mode'];
                return $event;
            }
        }
        return null;
    }

    public static function eventStatusLabels(): array
    {
        return ['upcoming' => 'به‌زودی', 'registration-open' => 'ثبت‌نام باز', 'full' => 'تکمیل ظرفیت', 'completed' => 'برگزار شده', 'canceled' => 'لغو شده'];
    }

    public static function eventModeLabels(): array
    {
        return ['online' => 'آنلاین', 'offline' => 'حضوری', 'hybrid' => 'هیبرید'];
    }

    public static function community(): array
    {
        return [
            'member_count' => '۲٬۸۰۰+',
            'benefits' => [
                ['title' => 'یادگیری جمعی', 'description' => 'گفتگو درباره مسئله‌های واقعی و یادگیری از تجربه همکاران.', 'icon' => '◎'],
                ['title' => 'رویدادهای اعضا', 'description' => 'دسترسی زودتر به نشست‌ها، حلقه‌ها و برنامه‌های Community.', 'icon' => '◫'],
                ['title' => 'شبکه حرفه‌ای', 'description' => 'آشنایی با متخصصان هم‌مسیر برای همکاری و تبادل تجربه.', 'icon' => '⌘'],
                ['title' => 'منابع منتخب', 'description' => 'خلاصه پژوهش، ابزار کاربردی و پیشنهادهای یادگیری ماهانه.', 'icon' => '⌁'],
            ],
            'rules' => [
                'با احترام، کنجکاوی و بدون قضاوت گفتگو می‌کنیم.',
                'محرمانگی تجربه‌های حرفه‌ای و شخصی را جدی می‌گیریم.',
                'ادعاهای تخصصی را تا حد امکان به شواهد معتبر پیوند می‌دهیم.',
                'Community جایگزین درمان، سوپرویژن رسمی یا خدمات بحران نیست.',
                'تبلیغ و فروش مستقیم بدون هماهنگی تیم Community مجاز نیست.',
            ],
        ];
    }

    public static function mentors(): array
    {
        return [
            ['slug' => 'arash-novin', 'name' => 'دکتر آرش نوین', 'role' => 'روان‌شناس بالینی', 'specialty' => 'طرحواره‌درمانی', 'initials' => 'آ ن', 'tone' => 'violet'],
            ['slug' => 'sara-mohammadi', 'name' => 'دکتر سارا محمدی', 'role' => 'روان‌درمانگر', 'specialty' => 'سلامت روان', 'initials' => 'س م', 'tone' => 'teal'],
            ['slug' => 'mohammad-rezaei', 'name' => 'دکتر محمد رضایی', 'role' => 'پژوهشگر علوم اعصاب', 'specialty' => 'پژوهش و نوروفیدبک', 'initials' => 'م ر', 'tone' => 'blue'],
            ['slug' => 'narges-kazemi', 'name' => 'دکتر نرگس کاظمی', 'role' => 'روان‌شناس کودک', 'specialty' => 'ارزیابی و سلامت جامعه', 'initials' => 'ن ک', 'tone' => 'rose'],
        ];
    }

    public static function articles(): array
    {
        return [
            ['title' => 'نقش ذهن‌آگاهی در درمان اضطراب', 'type' => 'مقاله', 'read' => '۸ دقیقه', 'tone' => 'violet'],
            ['title' => 'چگونه یک درمانگر مؤثر باشیم؟', 'type' => 'راهنما', 'read' => '۱۲ دقیقه', 'tone' => 'blue'],
            ['title' => 'پرسش‌های تازه از خودشناسی', 'type' => 'پژوهش', 'read' => '۱۰ دقیقه', 'tone' => 'teal'],
            ['title' => 'پادکست: گفتگو با درمانگران موفق', 'type' => 'پادکست', 'read' => '۲۵ دقیقه', 'tone' => 'amber'],
        ];
    }

    private static function bySlugs(array $items, array $slugs): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item['slug']] = $item;
        }
        return array_values(array_filter(array_map(fn (string $slug): ?array => $indexed[$slug] ?? null, $slugs)));
    }
}

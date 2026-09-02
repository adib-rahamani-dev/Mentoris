<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Translator;

final class SeoService
{
    private const LOCALE_MAP = [
        'fa' => 'fa_IR',
        'ar' => 'ar_AR',
        'ku' => 'ku_IQ',
        'en' => 'en_US',
    ];

    private const HREFLANG_MAP = [
        'fa' => 'fa',
        'ar' => 'ar',
        'ku' => 'ckb',
        'en' => 'en',
    ];

    private const INDEXABLE_EXCEPTIONS = [
        '/admin',
        '/api',
        '/checkout',
        '/dashboard',
        '/design-system',
        '/forgot-password',
        '/framework',
        '/login',
        '/my-certificates',
        '/my-courses',
        '/my-events',
        '/notifications',
        '/orders',
        '/payment',
        '/profile',
        '/register',
        '/reset-password',
    ];

    public static function metadata(string $title, string $description, array $options = []): array
    {
        $locale = Translator::locale();
        $path = self::currentPath();
        $baseUrl = self::baseUrl();
        $canonical = self::localizedUrl($path, $locale);
        $image = self::absoluteUrl((string) ($options['image'] ?? '/assets/images/mentoris-hero-v1.png'));
        $indexable = (bool) ($options['indexable'] ?? self::isIndexable($path));

        return [
            'title' => trim($title) !== '' ? trim($title) : 'Mentoris Academy',
            'description' => trim($description) !== '' ? trim($description) : self::defaultDescription($locale),
            'canonical' => $canonical,
            'image' => $image,
            'type' => (string) ($options['type'] ?? 'website'),
            'locale' => self::LOCALE_MAP[$locale] ?? self::LOCALE_MAP['fa'],
            'locale_alternates' => array_values(array_filter(self::LOCALE_MAP, static fn (string $value): bool => $value !== (self::LOCALE_MAP[$locale] ?? self::LOCALE_MAP['fa']))),
            'alternates' => array_map(static fn (string $language): array => [
                'language' => self::HREFLANG_MAP[$language] ?? $language,
                'url' => self::localizedUrl($path, $language),
            ], Translator::SUPPORTED),
            'x_default' => self::localizedUrl($path, 'fa'),
            'robots' => $indexable ? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' : 'noindex, nofollow, noarchive',
            'indexable' => $indexable,
            'base_url' => $baseUrl,
            'path' => $path,
        ];
    }

    public static function structuredData(array $metadata, array $additional = []): array
    {
        $baseUrl = (string) $metadata['base_url'];
        $founderImage = self::absoluteUrl('/assets/images/founder-maryam-haghani-v1.png');
        $locale = Translator::locale();
        $founder = [
            'fa' => ['name' => 'دکتر مریم حقانی', 'role' => 'بنیان‌گذار و مدیر آکادمی منتوریس'],
            'ar' => ['name' => 'الدكتورة مريم حقاني', 'role' => 'مؤسِّسة ومديرة أكاديمية منتوريس'],
            'ku' => ['name' => 'د. مەریەم حەقانی', 'role' => 'دامەزرێنەر و بەڕێوەبەری ئەکادیمی مێنتۆریس'],
            'en' => ['name' => 'Dr. Maryam Haghani', 'role' => 'Founder and Director of Mentoris Academy'],
        ];
        $founder = $founder[$locale] ?? $founder['fa'];

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $baseUrl . '/#organization',
                'name' => 'Mentoris Academy',
                'alternateName' => 'آکادمی منتوریس',
                'url' => $baseUrl . '/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::absoluteUrl('/assets/icons/favicon.svg'),
                ],
                'email' => 'Academichaghani@gmail.com',
                'telephone' => '+989100077611',
                'founder' => ['@id' => $baseUrl . '/founder/#person'],
                'sameAs' => ['https://www.instagram.com/mentoris_academy/'],
            ],
            [
                '@type' => 'Person',
                '@id' => $baseUrl . '/founder/#person',
                'name' => $founder['name'],
                'url' => $baseUrl . '/founder?lang=' . $locale,
                'image' => $founderImage,
                'jobTitle' => $founder['role'],
                'worksFor' => ['@id' => $baseUrl . '/#organization'],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $baseUrl . '/#website',
                'url' => $baseUrl . '/',
                'name' => 'Mentoris Academy',
                'publisher' => ['@id' => $baseUrl . '/#organization'],
                'inLanguage' => ['fa-IR', 'ar', 'ckb', 'en'],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $metadata['canonical'] . '#webpage',
                'url' => $metadata['canonical'],
                'name' => $metadata['title'],
                'description' => $metadata['description'],
                'isPartOf' => ['@id' => $baseUrl . '/#website'],
                'about' => ['@id' => $baseUrl . '/#organization'],
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $metadata['image'],
                ],
                'inLanguage' => self::LOCALE_MAP[$locale] ?? self::LOCALE_MAP['fa'],
            ],
        ];

        foreach ($additional as $item) {
            if (is_array($item) && $item !== []) {
                $graph[] = $item;
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    public static function eventSchema(array $event): array
    {
        $baseUrl = self::baseUrl();
        $startDate = (string) ($event['starts_at'] ?? '2026-09-18T18:00:00+03:30');
        $endDate = (string) ($event['ends_at'] ?? '2026-09-18T20:00:00+03:30');

        return [
            '@type' => 'Event',
            '@id' => self::localizedUrl('/events/' . $event['slug'], Translator::locale()) . '#event',
            'name' => $event['title'],
            'description' => $event['short_description'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => [
                '@type' => 'Place',
                'name' => $event['location'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Tabriz',
                    'addressCountry' => 'IR',
                ],
            ],
            'image' => [self::absoluteUrl('/assets/images/mentoris-hero-v1.png')],
            'organizer' => ['@id' => $baseUrl . '/#organization'],
            'performer' => ['@id' => $baseUrl . '/founder/#person'],
            'url' => self::localizedUrl('/events/' . $event['slug'], Translator::locale()),
        ];
    }

    private static function currentPath(): string
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';
        $path = '/' . ltrim($path, '/');

        return $path !== '/' ? rtrim($path, '/') : '/';
    }

    private static function baseUrl(): string
    {
        $configured = trim((string) env('APP_URL', 'https://mentorisacademy.com'));
        if ($configured === '') {
            $configured = 'https://mentorisacademy.com';
        }
        if (!preg_match('#^https?://#i', $configured)) {
            $configured = 'https://' . $configured;
        }

        return rtrim($configured, '/');
    }

    private static function localizedUrl(string $path, string $locale): string
    {
        return self::baseUrl() . ($path === '/' ? '/' : $path) . '?lang=' . rawurlencode($locale);
    }

    private static function absoluteUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    private static function isIndexable(string $path): bool
    {
        foreach (self::INDEXABLE_EXCEPTIONS as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        return true;
    }

    private static function defaultDescription(string $locale): string
    {
        return match ($locale) {
            'ar' => 'أكاديمية منتوريس؛ مساحة للتعلّم والتواصل والتطور المهني في علم النفس والصحة النفسية.',
            'ku' => 'ئەکادیمی مێنتۆریس؛ بوارێک بۆ فێربوون، پەیوەندی و گەشەی پیشەیی لە دەروونناسی و تەندروستی دەروونی.',
            'en' => 'Mentoris Academy is a space for learning, connection and professional growth in psychology and mental health.',
            default => 'آکادمی منتوریس؛ بستری برای یادگیری، ارتباط و رشد حرفه‌ای در روان‌شناسی و سلامت روان.',
        };
    }
}

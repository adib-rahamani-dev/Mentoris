<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class SitemapController
{
    public function index(Request $request): Response
    {
        $baseUrl = rtrim((string) env('APP_URL', 'https://mentorisacademy.com'), '/');
        if (!preg_match('#^https?://#i', $baseUrl)) $baseUrl = 'https://' . $baseUrl;
        $paths = [
            '/' => ['weekly', '1.0'], '/about' => ['monthly', '0.8'], '/founder' => ['monthly', '0.8'],
            '/academy' => ['monthly', '0.8'], '/programs' => ['weekly', '0.8'], '/courses' => ['weekly', '0.8'],
            '/events' => ['weekly', '0.9'], '/community' => ['monthly', '0.7'], '/mentors' => ['monthly', '0.7'],
            '/articles' => ['weekly', '0.8'], '/contact' => ['yearly', '0.6'],
        ];
        foreach (PublicContentService::academyLines() as $item) $paths['/academy/' . $item['slug']] = ['monthly', '0.7'];
        foreach (PublicContentService::specializations() as $item) $paths['/specializations/' . $item['slug']] = ['monthly', '0.7'];
        foreach (PublicContentService::programs() as $item) $paths['/programs/' . $item['slug']] = ['weekly', '0.7'];
        foreach (PublicContentService::courses() as $item) $paths['/courses/' . $item['slug']] = ['weekly', '0.8'];
        foreach (PublicContentService::events() as $item) $paths['/events/' . $item['slug']] = ['weekly', '0.9'];
        foreach (PublicContentService::mentors() as $item) {
            $paths[$item['slug'] === 'maryam-haghani' ? '/founder' : '/mentors/' . $item['slug']] = ['monthly', '0.7'];
        }
        foreach (PublicContentService::articles() as $item) $paths['/articles/' . $item['slug']] = ['weekly', '0.8'];

        $locales = ['fa' => 'fa', 'ar' => 'ar', 'ku' => 'ckb', 'en' => 'en'];
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($paths as $path => [$changeFrequency, $priority]) {
            foreach ($locales as $locale => $hreflang) {
                $url = $baseUrl . $path . ($path === '/' ? '?' : '?') . 'lang=' . $locale;
                $xml .= '  <url><loc>' . $escape($url) . '</loc><changefreq>' . $changeFrequency . '</changefreq><priority>' . $priority . '</priority>';
                foreach ($locales as $alternateLocale => $alternateHreflang) {
                    $alternateUrl = $baseUrl . $path . ($path === '/' ? '?' : '?') . 'lang=' . $alternateLocale;
                    $xml .= '<xhtml:link rel="alternate" hreflang="' . $alternateHreflang . '" href="' . $escape($alternateUrl) . '"/>';
                }
                $xml .= '<xhtml:link rel="alternate" hreflang="x-default" href="' . $escape($baseUrl . $path . '?lang=fa') . '"/></url>' . "\n";
            }
        }
        $xml .= '</urlset>';

        return Response::html($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

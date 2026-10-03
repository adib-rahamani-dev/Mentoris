<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;
use App\Repositories\ContentRepository;

final class SitemapController
{
    public function index(Request $request): Response
    {
        $baseUrl = rtrim((string) env('APP_URL', 'https://mentorisacademy.com'), '/');
        if (!preg_match('#^https?://#i', $baseUrl)) $baseUrl = 'https://' . $baseUrl;
        $paths = [
            '/' => ['weekly', '1.0'], '/about' => ['monthly', '0.8'], '/founder' => ['monthly', '0.8'],
            '/academy' => ['monthly', '0.8'],
            '/events' => ['weekly', '0.9'], '/community' => ['monthly', '0.7'], '/mentors' => ['monthly', '0.7'],
            '/contact' => ['yearly', '0.6'],
        ];
        if (PublicContentService::programs()) $paths['/programs'] = ['weekly', '0.8'];
        if (PublicContentService::courses()) $paths['/courses'] = ['weekly', '0.8'];
        $paths['/articles'] = ['weekly', '0.8'];
        $paths['/resources'] = ['monthly','0.7'];
        foreach(\App\Services\ResourceService::all() as $slug=>$tool) $paths['/resources/'.$slug]=['monthly','0.6'];
        foreach (PublicContentService::academyLines() as $item) $paths['/academy/' . $item['slug']] = ['monthly', '0.7'];
        foreach (PublicContentService::specializations() as $item) $paths['/specializations/' . $item['slug']] = ['monthly', '0.7'];
        foreach (PublicContentService::programs() as $item) $paths['/programs/' . $item['slug']] = ['weekly', '0.7'];
        foreach (PublicContentService::courses() as $item) $paths['/courses/' . $item['slug']] = ['weekly', '0.8'];
        foreach (PublicContentService::events() as $item) $paths['/events/' . $item['slug']] = ['weekly', '0.9'];
        foreach (PublicContentService::mentors() as $item) {
            $paths[$item['slug'] === 'maryam-haghani' ? '/founder' : '/mentors/' . $item['slug']] = ['monthly', '0.7'];
        }
        $articleRows = \App\Content\InboundArticles::all();
        try { $articleRows = [...$articleRows, ...(new ContentRepository())->publishedSlugs('article')]; } catch (\Throwable) {}
        foreach ($articleRows as $item) $paths['/articles/' . $item['slug']] = ['weekly', '0.8'];

        $locales = ['fa' => 'fa', 'ar' => 'ar', 'ku' => 'ckb', 'en' => 'en'];
        $articleLocales = [];
        $articleModified = [];
        foreach ($articleRows as $item) {
            $articleLocales['/articles/' . $item['slug']] = PublicContentService::articleLocales((string) $item['slug']);
            $articleModified['/articles/' . $item['slug']] = substr((string) ($item['updated_at'] ?? $item['published_at'] ?? ''), 0, 10);
        }
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($paths as $path => $_) {
            $pathLocales = isset($articleLocales[$path]) ? array_intersect_key($locales, array_flip($articleLocales[$path])) : ($path === '/articles' ? array_intersect_key($locales, array_flip(PublicContentService::articleIndexLocales())) : $locales);
            if(str_starts_with($path,'/resources')) $pathLocales=['fa'=>'fa'];
            foreach ($pathLocales as $locale => $hreflang) {
                $url = $baseUrl . $path . ($path === '/' ? '?' : '?') . 'lang=' . $locale;
                $xml .= '  <url><loc>' . $escape($url) . '</loc>';
                if (!empty($articleModified[$path]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $articleModified[$path])) {
                    $xml .= '<lastmod>' . $articleModified[$path] . '</lastmod>';
                }
                foreach ($pathLocales as $alternateLocale => $alternateHreflang) {
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

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;
use App\Services\SeoService;

final class ArticlesController extends Controller
{
    public function index(Request $request): Response
    {
        $articles = PublicContentService::articles();
        return $this->view('pages.articles', [
            'title' => t('nav.articles') . ' | Mentoris Academy',
            'description' => t('articles.lead'),
            'indexable' => $articles !== [],
            'seoImage' => '/assets/images/mentoris-journal-editorial-v1.webp',
            'seoLanguages' => PublicContentService::articleIndexLocales(),
            'structuredData' => [SeoService::articleCollectionSchema($articles)],
            'articles' => $articles,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $article = PublicContentService::article($slug);
        if ($article === null) return Response::html('<h1>404 - Article Not Found</h1>', 404);
        return $this->view('pages.article-details', [
            'title' => $article['title'] . ' | Mentoris',
            'description' => $article['excerpt'],
            'seoType' => 'article',
            'seoLanguages' => PublicContentService::articleLocales($slug),
            'seoImage' => !empty($article['image']) ? '/assets/' . ltrim($article['image'], '/') : null,
            'structuredData' => [SeoService::articleSchema($article), SeoService::breadcrumbSchema([['name'=>t('nav.articles'),'path'=>'/articles'],['name'=>$article['title'],'path'=>'/articles/' . $slug]])],
            'article' => $article,
            'relatedArticles' => array_slice(array_values(array_filter(PublicContentService::articles(), static fn (array $item): bool => $item['slug'] !== $slug)), 0, 3),
        ]);
    }
}

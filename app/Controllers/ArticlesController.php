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
            'seoImage' => !empty($article['image']) ? '/assets/' . ltrim($article['image'], '/') : null,
            'structuredData' => [SeoService::articleSchema($article)],
            'article' => $article,
        ]);
    }
}

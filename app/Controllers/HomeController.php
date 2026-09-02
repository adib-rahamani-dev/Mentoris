<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('pages.public-home', [
            'title' => 'Mentoris Academy | ' . t('home.title.accent'),
            'description' => t('home.lead'),
            'lines' => PublicContentService::academyLines(),
            'events' => PublicContentService::events(),
            'programs' => [],
            'mentors' => PublicContentService::mentors(),
            'articles' => PublicContentService::articles(),
            'founder' => PublicContentService::founder(),
            'about' => PublicContentService::about(),
        ]);
    }

    public function designSystem(Request $request): Response
    {
        return $this->view('pages.home', [
            'title' => 'Mentoris Design System',
            'description' => 'راهنمای زنده زبان بصری و کامپوننت‌های Mentoris',
        ]);
    }
}

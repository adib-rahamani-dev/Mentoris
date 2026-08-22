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
            'title' => 'Mentoris | آکادمی رشد، یادگیری و تأثیرگذاری',
            'description' => 'اکوسیستم علمی و حرفه‌ای برای روان‌شناسان، درمانگران، دانشجویان و علاقه‌مندان به سلامت روان.',
            'lines' => PublicContentService::academyLines(),
            'events' => PublicContentService::events(),
            'programs' => array_slice(PublicContentService::courses(), 0, 3),
            'mentors' => PublicContentService::mentors(),
            'articles' => PublicContentService::articles(),
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

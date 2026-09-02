<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class AboutController extends Controller
{
    public function index(Request $request): Response
    {
        $about = PublicContentService::about();
        return $this->view('pages.about', [
            'title' => $about['title'] . ' | Mentoris Academy',
            'description' => $about['lead'],
            'lines' => PublicContentService::academyLines(),
            'about' => $about,
            'founder' => PublicContentService::founder(),
        ]);
    }
}

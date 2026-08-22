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
        return $this->view('pages.about', [
            'title' => 'درباره Mentoris',
            'description' => 'داستان، مأموریت، چشم‌انداز و ارزش‌های آکادمی Mentoris.',
            'lines' => PublicContentService::academyLines(),
        ]);
    }
}

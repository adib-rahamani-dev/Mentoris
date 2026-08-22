<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class AcademyController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('pages.academy', [
            'title' => 'لاین‌های آکادمی Mentoris',
            'description' => 'هفت لاین تخصصی Mentoris برای آموزش، پژوهش و توسعه حرفه‌ای.',
            'lines' => PublicContentService::academyLines(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $line = PublicContentService::academyLine($slug);
        if ($line === null) {
            return Response::html('<h1>404 - Academy Line Not Found</h1>', 404);
        }

        return $this->view('pages.academy-line', [
            'title' => $line['title'] . ' | Academy Lines',
            'description' => $line['description'],
            'line' => $line,
        ]);
    }
}

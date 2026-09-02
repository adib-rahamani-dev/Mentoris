<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class ProgramsController extends Controller
{
    public function index(Request $request): Response
    {
        $programs = PublicContentService::programs();
        return $this->view('pages.programs', [
            'title' => t('nav.programs') . ' | Mentoris Academy',
            'description' => t('empty.text'),
            'indexable' => $programs !== [],
            'programs' => $programs,
            'lines' => PublicContentService::academyLines(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $program = PublicContentService::program($slug);
        if ($program === null) {
            return Response::html('<h1>404 - Program Not Found</h1>', 404);
        }

        return $this->view('pages.program-details', [
            'title' => $program['title'] . ' | Mentoris',
            'description' => $program['short_description'],
            'program' => $program,
        ]);
    }
}

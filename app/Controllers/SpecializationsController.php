<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class SpecializationsController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('pages.specializations', [
            'title' => t('nav.specializations') . ' | Mentoris Academy',
            'description' => t('empty.text'),
            'indexable' => false,
            'lines' => PublicContentService::academyLines(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $specialization = PublicContentService::specialization($slug);
        if ($specialization === null) {
            return Response::html('<h1>404 - Specialization Not Found</h1>', 404);
        }

        return $this->view('pages.specialization-details', [
            'title' => $specialization['title'] . ' | Mentoris',
            'description' => $specialization['description'],
            'specialization' => $specialization,
        ]);
    }
}

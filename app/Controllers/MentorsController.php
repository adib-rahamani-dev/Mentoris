<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class MentorsController extends Controller
{
    public function index(Request $request): Response
    {
        $mentors = PublicContentService::mentors();
        return $this->view('pages.mentors', [
            'title' => t('nav.mentors') . ' | Mentoris Academy',
            'description' => $mentors[0]['specialty'] ?? t('empty.text'),
            'mentors' => $mentors,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        foreach (PublicContentService::mentors() as $mentor) {
            if (($mentor['slug'] ?? '') !== $slug) continue;
            if ($slug === 'maryam-haghani') return Response::redirect('/founder');
            return $this->view('pages.mentor-details', [
                'title' => $mentor['name'] . ' | ' . t('nav.mentors'),
                'description' => $mentor['specialty'],
                'mentor' => $mentor,
            ]);
        }
        return Response::html('<h1>404 - Mentor Not Found</h1>', 404);
    }
}

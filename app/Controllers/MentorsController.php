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
}

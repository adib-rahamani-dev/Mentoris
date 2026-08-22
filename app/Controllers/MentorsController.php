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
        return $this->view('pages.mentors', [
            'title' => 'اساتید و متخصصان Mentoris',
            'description' => 'با شبکه استادان، درمانگران و پژوهشگران Mentoris آشنا شوید.',
            'mentors' => PublicContentService::mentors(),
        ]);
    }
}

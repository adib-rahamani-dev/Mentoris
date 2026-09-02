<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class FounderController extends Controller
{
    public function index(Request $request): Response
    {
        $founder = PublicContentService::founder();
        return $this->view('pages.founder', [
            'title' => $founder['name'] . ' | ' . t('nav.founder') . ' Mentoris',
            'description' => $founder['short_bio'],
            'seoImage' => '/assets/images/founder-maryam-haghani-v1.png',
            'founder' => $founder,
        ]);
    }
}

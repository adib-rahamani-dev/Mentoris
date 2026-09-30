<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CircleRepository;
use App\Services\PublicContentService;

final class EventCertificateController extends Controller
{
    public function show(Request $request, string $number): Response
    {
        $certificate = (new CircleRepository())->certificate($number);
        if (!$certificate) return Response::html('<h1>گواهی پیدا نشد.</h1>', 404);
        $event = PublicContentService::event($certificate['event_slug']);
        return $this->view('pages.event-certificate', [
            'title' => 'تأیید گواهی نشست | منتوریس', 'indexable' => false,
            'certificate' => $certificate, 'eventTitle' => $event['title'] ?? $certificate['event_slug'],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $allEvents = PublicContentService::events();
        $upcomingEvents = array_values(array_filter($allEvents, static fn (array $event): bool => in_array($event['status'], ['registration-open', 'upcoming', 'full'], true)));
        $pastEvents = array_values(array_filter($allEvents, static fn (array $event): bool => $event['status'] === 'completed'));
        return $this->view('pages.public-home', [
            'title' => (\App\Core\Translator::locale()==='fa' ? 'آکادمی منتوریس' : 'Mentoris Academy') . ' | ' . t('home.title.accent'),
            'description' => t('home.lead'),
            'lines' => PublicContentService::academyLines(),
            'events' => array_slice($upcomingEvents ?: $pastEvents, 0, 3),
            'eventsArchived' => $upcomingEvents === [] && $pastEvents !== [],
            'courses' => array_values(array_filter(PublicContentService::courses(), static fn (array $course): bool => $course['status'] === 'active')),
            'mentors' => PublicContentService::mentors(),
            'articles' => PublicContentService::articles(),
            'founder' => PublicContentService::founder(),
            'about' => PublicContentService::about(),
        ]);
    }

    public function designSystem(Request $request): Response
    {
        return $this->view('pages.home', [
            'title' => 'Mentoris Design System',
            'description' => 'راهنمای زنده زبان بصری و کامپوننت‌های Mentoris',
        ]);
    }
}

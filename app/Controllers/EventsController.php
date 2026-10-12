<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CircleRepository;
use App\Services\AuthService;
use App\Services\PublicContentService;
use App\Services\SeoService;
use RuntimeException;

final class EventsController extends Controller
{
    public function index(Request $request): Response
    {
        $events = array_map(fn (array $event): array => PublicContentService::event($event['slug']) ?? $event, PublicContentService::events());
        return $this->view('pages.events', [
            'title' => t('nav.events') . ' | Mentoris Academy',
            'description' => $events[0]['short_description'] ?? t('empty.text'),
            'events' => $events,
            'statuses' => PublicContentService::eventStatusLabels(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $event = PublicContentService::event($slug);
        if ($event === null) {
            return Response::html('<h1>404 - Event Not Found</h1>', 404);
        }
        return $this->renderEvent($event,success:$event['can_register'] && (int)(new \App\Core\Session())->get('event.receipt.'.$slug,0)>time());
    }

    public function register(Request $request, string $slug): Response
    {
        $event = PublicContentService::event($slug);
        if ($event === null) {
            return Response::html('<h1>404 - Event Not Found</h1>', 404);
        }
        if (!$event['can_register']) {
            return $this->renderEvent($event, [], $request->only(['name', 'phone', 'city']), false, 'درخواست حضور در این رویداد فعلاً فعال نیست.');
        }

        $data = $request->only(['name', 'phone', 'city']);
        foreach($data as $key=>$value) $data[$key]=is_string($value) ? trim($value) : '';
        $data['phone'] = \App\Core\PhoneNumber::normalize($data['phone'] ?? '');
        $validator = new Validator();
        $valid = $validator->validate($data, [
            'name' => 'required|string|min:2|max:80',
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'city' => 'required|string|min:2|max:80',
        ]);
        if (!$valid) {
            return $this->renderEvent($event, $validator->errors(), $data);
        }

        try {
            $user = (new AuthService())->user();
            $circle = new CircleRepository();
            if (!$circle->available()) return $this->renderEvent($event, [], $data, false, 'ثبت درخواست پس از آماده‌سازی پایگاه داده فعال می‌شود.');
            $circle->signup($slug, $data, $user['id'] ?? null);
        } catch (RuntimeException $exception) {
            return $this->renderEvent($event, [], $data, false, $exception->getMessage());
        }
        (new \App\Core\Session())->put('event.receipt.'.$slug,time()+1800);
        return $this->redirect('/events/'.$slug.'#register',303);
    }

    private function renderEvent(array $event, array $errors = [], array $old = [], bool $success = false, ?string $notice = null): Response
    {
        return $this->view('pages.event-details', [
            'title' => $event['title'] . ' | Events',
            'description' => $event['short_description'],
            'seoImage' => '/assets/' . ltrim((string) ($event['image'] ?: 'images/mentoris-hero-sage-v2.png'), '/'),
            'seoType' => 'event',
            'structuredData' => !empty($event['date_iso']) ? [SeoService::eventSchema($event)] : [],
            'event' => $event,
            'registrationReady' => $event['can_register'] && (new CircleRepository())->available(),
            'errors' => $errors,
            'old' => $old,
            'success' => $success,
            'notice' => $notice,
        ])->withHeader('Cache-Control','private, no-store');
    }
}

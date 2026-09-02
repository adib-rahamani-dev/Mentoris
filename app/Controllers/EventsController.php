<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\PublicContentService;
use App\Services\SeoService;

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
        return $this->renderEvent($event);
    }

    public function register(Request $request, string $slug): Response
    {
        $event = PublicContentService::event($slug);
        if ($event === null) {
            return Response::html('<h1>404 - Event Not Found</h1>', 404);
        }
        if (!$event['can_register']) {
            return $this->renderEvent($event, [], $request->only(['name', 'email', 'phone', 'role']), false, 'ثبت‌نام این رویداد در حال حاضر فعال نیست.');
        }

        $data = $request->only(['name', 'email', 'phone', 'role']);
        $validator = new Validator();
        $valid = $validator->validate($data, [
            'name' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:120',
            'phone' => 'required|string|max:20',
            'role' => 'required|string|max:80',
        ]);
        if (!$valid) {
            return $this->renderEvent($event, $validator->errors(), $data);
        }

        $session = new Session();
        $registrationKey = 'event.registration.' . $slug;
        if ($session->has($registrationKey)) {
            return $this->renderEvent($event, [], $data, false, 'درخواست ثبت‌نام شما قبلاً ثبت شده است.');
        }

        $session->put($registrationKey, [...$data, 'registered_at' => date(DATE_ATOM)]);
        $event['registered'] = min($event['capacity'], $event['registered'] + 1);
        $event['available'] = max(0, $event['capacity'] - $event['registered']);
        return $this->renderEvent($event, [], [], true);
    }

    private function renderEvent(array $event, array $errors = [], array $old = [], bool $success = false, ?string $notice = null): Response
    {
        return $this->view('pages.event-details', [
            'title' => $event['title'] . ' | Events',
            'description' => $event['short_description'],
            'seoType' => 'event',
            'structuredData' => [SeoService::eventSchema($event)],
            'event' => $event,
            'errors' => $errors,
            'old' => $old,
            'success' => $success,
            'notice' => $notice,
        ]);
    }
}

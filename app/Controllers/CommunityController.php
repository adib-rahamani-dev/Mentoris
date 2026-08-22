<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\PublicContentService;

final class CommunityController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->renderCommunity();
    }

    public function join(Request $request): Response
    {
        $data = $request->only(['name', 'email', 'role', 'interests', 'accept']);
        $validator = new Validator();
        $valid = $validator->validate($data, [
            'name' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:120',
            'role' => 'required|string|max:80',
            'interests' => 'string|max:500',
            'accept' => 'required',
        ]);
        if (!$valid) {
            return $this->renderCommunity($validator->errors(), $data);
        }

        (new Session())->put('community.membership.' . hash('sha256', strtolower((string) $data['email'])), [
            ...$data,
            'joined_at' => date(DATE_ATOM),
        ]);
        return $this->renderCommunity([], [], true);
    }

    private function renderCommunity(array $errors = [], array $old = [], bool $success = false): Response
    {
        $events = array_slice(array_values(array_filter(
            array_map(fn (array $event): ?array => PublicContentService::event($event['slug']), PublicContentService::events()),
            fn (?array $event): bool => $event !== null && in_array($event['status'], ['registration-open', 'upcoming', 'full'], true)
        )), 0, 3);

        return $this->view('pages.community', [
            'title' => 'Mentoris Community',
            'description' => 'جامعه‌ای حرفه‌ای برای یادگیری، گفتگو و ارتباط متخصصان سلامت روان.',
            'community' => PublicContentService::community(),
            'events' => $events,
            'errors' => $errors,
            'old' => $old,
            'success' => $success,
        ]);
    }
}

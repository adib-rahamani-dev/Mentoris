<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PublicContentService;

final class UserAreaController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->user();
        return $this->page('dashboard', 'داشبورد من', $user, ['recommendations' => array_slice(array_values(array_filter(array_map(fn ($c) => PublicContentService::course($c['slug']), PublicContentService::courses()), fn ($c) => $c && $c['status'] === 'active')), 0, 3)]);
    }

    public function profile(Request $request): Response { return $this->page('profile', 'پروفایل من', $this->user()); }

    public function updateProfile(Request $request): Response
    {
        $user = $this->user();
        $data = $request->only(['name', 'phone', 'role', 'bio']);
        $validator = new Validator();
        $validator->validate($data, ['name' => 'required|string|min:2|max:80', 'phone' => 'string|max:20', 'role' => 'string|max:80', 'bio' => 'string|max:500']);
        if ($validator->fails()) return $this->page('profile', 'پروفایل من', $user, ['errors' => $validator->errors(), 'old' => $data]);
        $updated = (new UserRepository())->updateProfile($user['id'], $data) ?? $user;
        (new AuthService())->refresh($updated);
        return $this->page('profile', 'پروفایل من', UserRepository::publicUser($updated), ['success' => true]);
    }

    public function courses(Request $request): Response
    {
        $user = $this->user();
        return $this->page('my-courses', 'دوره‌های من', $user, ['courses' => $this->resolve(PublicContentService::courses(), $user['courses'] ?? [], 'course')]);
    }

    public function events(Request $request): Response
    {
        $user = $this->user();
        return $this->page('my-events', 'رویدادهای من', $user, ['events' => $this->resolve(PublicContentService::events(), $user['events'] ?? [], 'event')]);
    }

    public function certificates(Request $request): Response { return $this->page('my-certificates', 'گواهی‌های من', $this->user()); }

    public function notifications(Request $request): Response { return $this->page('notifications', 'اعلان‌ها', $this->user()); }

    public function readNotifications(Request $request): Response
    {
        $user = $this->user();
        (new UserRepository())->markNotificationsRead($user['id']);
        return $this->redirect('/notifications');
    }

    private function user(): array { return (new AuthService())->user() ?? []; }

    private function page(string $view, string $title, array $user, array $extra = []): Response
    {
        return $this->view('user.' . $view, ['title' => $title . ' | Mentoris', 'description' => 'ناحیه کاربری Mentoris', 'user' => $user, 'errors' => [], 'old' => [], 'success' => false, ...$extra]);
    }

    private function resolve(array $items, array $slugs, string $method): array
    {
        $available = array_column($items, null, 'slug');
        return array_values(array_filter(array_map(fn ($slug) => isset($available[$slug]) ? PublicContentService::{$method}((string) $slug) : null, $slugs)));
    }
}

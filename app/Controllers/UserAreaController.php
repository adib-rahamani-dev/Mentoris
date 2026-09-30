<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Repositories\CircleRepository;
use App\Services\AuthService;
use App\Services\PublicContentService;

final class UserAreaController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->user();
        return $this->page('dashboard', 'داشبورد من', $user, ['recommendations' => array_slice(array_values(array_filter(array_map(fn ($c) => PublicContentService::course($c['slug']), PublicContentService::courses()), fn ($c) => $c && $c['status'] === 'active')), 0, 3)]);
    }

    public function profile(Request $request): Response { $user = $this->user(); $circle = new CircleRepository(); return $this->page('profile', 'پروفایل من', $user, ['therapist' => $circle->profile($user['id']), 'therapistEnabled' => $circle->therapistAvailable()]); }

    public function updateProfile(Request $request): Response
    {
        $user = $this->user();
        $data = $request->only(['name', 'phone', 'role', 'bio']);
        $validator = new Validator();
        $validator->validate($data, ['name' => 'required|string|min:2|max:80', 'phone' => 'string|max:20', 'role' => 'string|max:80', 'bio' => 'string|max:500']);
        if ($validator->fails()) { $circle = new CircleRepository(); return $this->page('profile', 'پروفایل من', $user, ['errors' => $validator->errors(), 'old' => $data, 'therapist' => $circle->profile($user['id']), 'therapistEnabled' => $circle->therapistAvailable()]); }
        $updated = (new UserRepository())->updateProfile($user['id'], $data) ?? $user;
        (new AuthService())->refresh($updated);
        $circle = new CircleRepository();
        return $this->page('profile', 'پروفایل من', UserRepository::publicUser($updated), ['success' => true, 'therapist' => $circle->profile($user['id']), 'therapistEnabled' => $circle->therapistAvailable()]);
    }

    public function updateTherapistProfile(Request $request): Response
    {
        $user = $this->user();
        if (!(new CircleRepository())->therapistAvailable()) return $this->page('profile', 'پروفایل من', $user, ['therapistEnabled' => false]);
        $data = $request->only(['latin_name','national_id','professional_number','education_level','specialization','university','approaches','practice_areas','experience','social_link']);
        $errors = [];
        $options = [
            'education_level' => ['masters_student','masters','phd_student','phd'],
            'specialization' => ['clinical','health','general','counseling','other'],
            'experience' => ['under_1','1_3','3_7','over_7'],
            'approaches' => ['ACT','CBT','schema','psychodynamic','CFT','other'],
            'practice_areas' => ['adult','trauma','couples','child','other'],
        ];
        foreach (['latin_name'=>120,'professional_number'=>80,'university'=>160,'social_link'=>255] as $field=>$max) {
            if (!is_string($data[$field] ?? '') || mb_strlen(trim((string) ($data[$field] ?? ''))) > $max) $errors[$field] = ['مقدار واردشده معتبر نیست.'];
        }
        if (($data['latin_name'] ?? '') !== '' && !preg_match('/^[A-Za-z][A-Za-z .\x27-]{1,119}$/', trim((string) $data['latin_name']))) $errors['latin_name'] = ['نام انگلیسی را با حروف لاتین وارد کنید.'];
        if (($data['national_id'] ?? '') !== '' && !$this->validNationalId((string) $data['national_id'])) $errors['national_id'] = ['کد ملی معتبر نیست.'];
        foreach (['education_level','specialization','experience'] as $field) if (!in_array((string) ($data[$field] ?? ''), ['', ...$options[$field]], true)) $errors[$field] = ['گزینه معتبر نیست.'];
        foreach (['approaches','practice_areas'] as $field) {
            $values = $data[$field] ?? [];
            if (!is_array($values) || count($values) > count($options[$field]) || array_diff($values, $options[$field])) $errors[$field] = ['گزینه معتبر نیست.'];
            else $data[$field] = array_values(array_unique($values));
        }
        if (($data['social_link'] ?? '') !== '' && !filter_var($data['social_link'], FILTER_VALIDATE_URL)) $errors['social_link'] = ['لینک معتبر وارد کنید.'];
        if ($errors) return $this->page('profile', 'پروفایل من', $user, ['therapist' => $data, 'therapistErrors' => $errors, 'therapistEnabled' => true]);
        (new CircleRepository())->saveProfile($user['id'], $data);
        return $this->page('profile', 'پروفایل من', $user, ['therapist' => (new CircleRepository())->profile($user['id']), 'therapistSuccess' => true, 'therapistEnabled' => true]);
    }

    private function validNationalId(string $value): bool
    {
        $value = CircleRepository::phone($value);
        if (!preg_match('/^[0-9]{10}$/', $value) || preg_match('/^(.)\1{9}$/', $value)) return false;
        $sum = 0;
        for ($i = 0; $i < 9; $i++) $sum += (int) $value[$i] * (10 - $i);
        $remainder = $sum % 11;
        return (int) $value[9] === ($remainder < 2 ? $remainder : 11 - $remainder);
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

    public function certificates(Request $request): Response { $user = $this->user(); return $this->page('my-certificates', 'گواهی‌های من', $user, ['eventCertificates' => (new CircleRepository())->issueEligibleCertificates($user['id'])]); }

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

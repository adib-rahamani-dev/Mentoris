<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Authorization;
use App\Core\Audit;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CommerceRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PublicContentService;

final class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $users = (new UserRepository())->all();
        return $this->adminView('dashboard', 'داشبورد مدیریت', [
            'stats' => $this->stats($users),
            'recentUsers' => array_slice($users, 0, 6),
            'recentOrders' => (new CommerceRepository())->recentOrders(),
        ]);
    }

    public function users(Request $request): Response
    {
        $users = (new UserRepository())->all();
        $query = mb_strtolower(trim((string) $request->query('q', '')));
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'all');
        $users = array_values(array_filter($users, static function (array $user) use ($query, $role, $status): bool {
            $matchesQuery = $query === '' || str_contains(mb_strtolower(($user['name'] ?? '') . ' ' . ($user['email'] ?? '')), $query);
            return $matchesQuery && ($role === 'all' || ($user['account_role'] ?? 'student') === $role) && ($status === 'all' || ($user['status'] ?? 'active') === $status);
        }));
        return $this->adminView('users', 'مدیریت کاربران', ['users' => $users, 'filters' => compact('query', 'role', 'status'), 'roles' => Authorization::ROLES]);
    }

    public function updateUserAccess(Request $request, string $id): Response
    {
        $current = $this->currentUser();
        $repository = new UserRepository();
        $target = $repository->findById($id);
        if ($target === null) return Response::html('<h1>404 - User Not Found</h1>', 404);

        $role = (string) $request->input('account_role', 'student');
        $status = (string) $request->input('status', 'active');
        $currentIsSuperAdmin = Authorization::role($current) === 'super_admin';
        $targetIsSuperAdmin = Authorization::role($target) === 'super_admin';
        if (($targetIsSuperAdmin || $role === 'super_admin') && !$currentIsSuperAdmin) return Response::html('<h1>403 - فقط مدیر کل مجاز است</h1>', 403);
        if ($id === ($current['id'] ?? '') && ($role !== Authorization::role($current) || $status !== 'active')) {
            return Response::redirect('/admin/users?error=self-access');
        }
        $repository->updateAccess($id, $role, $status);
        Audit::record('user.access.updated', 'user', $id, $current['id'] ?? null, ['account_role' => $target['account_role'], 'status' => $target['status']], ['account_role' => $role, 'status' => $status], $request->ip());
        return Response::redirect('/admin/users?updated=1');
    }

    public function content(Request $request): Response
    {
        $modules = [
            ['key' => 'academy', 'title' => 'لاین‌های آکادمی', 'count' => count(PublicContentService::academyLines()), 'status' => 'published', 'url' => '/academy'],
            ['key' => 'events', 'title' => 'رویدادها', 'count' => count(PublicContentService::events()), 'status' => count(PublicContentService::events()) ? 'published' : 'coming-soon', 'url' => '/events'],
            ['key' => 'courses', 'title' => 'دوره‌ها', 'count' => count(PublicContentService::courses()), 'status' => count(PublicContentService::courses()) ? 'published' : 'coming-soon', 'url' => '/courses'],
            ['key' => 'programs', 'title' => 'برنامه‌ها', 'count' => count(PublicContentService::programs()), 'status' => count(PublicContentService::programs()) ? 'published' : 'coming-soon', 'url' => '/programs'],
            ['key' => 'experts', 'title' => 'اساتید و همکاران', 'count' => count(PublicContentService::mentors()), 'status' => 'published', 'url' => '/mentors'],
            ['key' => 'articles', 'title' => 'پژوهش و محتوا', 'count' => count(PublicContentService::articles()), 'status' => count(PublicContentService::articles()) ? 'published' : 'coming-soon', 'url' => '/'],
        ];
        return $this->adminView('content', 'مدیریت محتوا', ['modules' => $modules]);
    }

    public function analytics(Request $request): Response
    {
        $users = (new UserRepository())->all();
        $monthly = [];
        foreach ($users as $user) {
            $month = substr((string) ($user['created_at'] ?? ''), 0, 7);
            if ($month !== '') $monthly[$month] = ($monthly[$month] ?? 0) + 1;
        }
        ksort($monthly);
        return $this->adminView('analytics', 'آمار و گزارش‌ها', ['stats' => $this->stats($users), 'monthly' => array_slice($monthly, -12, null, true)]);
    }

    private function stats(array $users): array
    {
        $roleCounts = array_fill_keys(array_keys(Authorization::ROLES), 0);
        foreach ($users as $user) $roleCounts[Authorization::role($user)]++;
        $commerce = (new CommerceRepository())->summary();
        return [
            'users' => count($users),
            'active_users' => count(array_filter($users, static fn (array $user): bool => ($user['status'] ?? 'active') === 'active')),
            'new_users_30d' => count(array_filter($users, static fn (array $user): bool => strtotime((string) ($user['created_at'] ?? '1970-01-01')) >= strtotime('-30 days'))),
            'roles' => $roleCounts,
            'courses' => count(PublicContentService::courses()),
            'events' => count(PublicContentService::events()),
            ...$commerce,
        ];
    }

    private function currentUser(): array { return (new AuthService())->user() ?? []; }

    private function adminView(string $view, string $title, array $data = []): Response
    {
        return $this->view('admin.' . $view, ['title' => $title . ' | Mentoris Admin', 'admin' => $this->currentUser(), ...$data], 'layouts.admin');
    }
}

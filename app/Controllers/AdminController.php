<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Authorization;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\AdminRepository;
use App\Repositories\CommerceRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PublicContentService;
use RuntimeException;

final class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $repository = new AdminRepository();
        return $this->adminView('dashboard', 'مرکز کنترل', [
            'stats' => $repository->dashboard(),
            'trends' => $repository->monthlyMetrics(8),
            'recentUsers' => array_slice((new UserRepository())->all(), 0, 6),
            'recentOrders' => (new CommerceRepository())->recentOrders(6),
            'recentAudit' => $repository->recentAudit(7),
        ]);
    }

    public function users(Request $request): Response
    {
        $users = (new UserRepository())->all();
        $query = mb_strtolower(trim((string) $request->query('q', '')));
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'all');
        $users = array_values(array_filter($users, static fn (array $user): bool =>
            ($query === '' || str_contains(mb_strtolower(($user['name'] ?? '') . ' ' . ($user['email'] ?? '') . ' ' . ($user['phone'] ?? '')), $query))
            && ($role === 'all' || ($user['account_role'] ?? 'student') === $role)
            && ($status === 'all' || ($user['status'] ?? 'active') === $status)
        ));
        return $this->adminView('users', 'کاربران و نقش‌ها', [
            'users' => $users, 'filters' => compact('query', 'role', 'status'), 'roles' => Authorization::ROLES,
            'summary' => ['total' => count((new UserRepository())->all()), 'active' => count(array_filter($users, static fn ($u) => ($u['status'] ?? '') === 'active')), 'filtered' => count($users)],
        ]);
    }

    public function user(Request $request, string $id): Response
    {
        $user = (new UserRepository())->findById($id);
        if ($user === null) return Response::html('<h1>404 - کاربر پیدا نشد</h1>', 404);
        return $this->adminView('user-details', 'پرونده کاربر', ['user' => $user, 'operations' => (new AdminRepository())->userOperations($id), 'roles' => Authorization::ROLES]);
    }

    public function updateUserAccess(Request $request, string $id): Response
    {
        $current = $this->currentUser(); $repository = new UserRepository(); $target = $repository->findById($id);
        if ($target === null) return Response::html('<h1>404 - کاربر پیدا نشد</h1>', 404);
        $role = (string) $request->input('account_role', 'student'); $status = (string) $request->input('status', 'active');
        $currentIsSuperAdmin = Authorization::role($current) === 'super_admin'; $targetIsSuperAdmin = Authorization::role($target) === 'super_admin';
        if (($targetIsSuperAdmin || $role === 'super_admin') && !$currentIsSuperAdmin) return Response::html('<h1>403 - فقط مدیرکل مجاز است</h1>', 403);
        if ($id === ($current['id'] ?? '') && ($role !== Authorization::role($current) || $status !== 'active')) return Response::redirect('/admin/users?error=self-access');
        $repository->updateAccess($id, $role, $status);
        Audit::record('user.access.updated', 'user', $id, $current['id'] ?? null, ['account_role' => $target['account_role'], 'status' => $target['status']], ['account_role' => $role, 'status' => $status], $request->ip());
        return Response::redirect('/admin/users/' . $id . '?updated=1');
    }

    public function notifyUser(Request $request, string $id): Response
    {
        $user = (new UserRepository())->findById($id);
        if ($user === null) return Response::html('<h1>404</h1>', 404);
        $data = $request->only(['title', 'message']); $validator = new Validator();
        $validator->validate($data, ['title' => 'required|string|min:3|max:190', 'message' => 'required|string|min:5|max:2000']);
        if ($validator->fails()) return Response::redirect('/admin/users/' . $id . '?error=notification');
        (new AdminRepository())->notifyUser($id, (string) $data['title'], (string) $data['message']);
        Audit::record('user.notification.sent', 'user', $id, $this->currentUser()['id'] ?? null, [], ['title' => $data['title']], $request->ip());
        return Response::redirect('/admin/users/' . $id . '?notified=1');
    }

    public function orders(Request $request): Response
    {
        $filters = ['query' => trim((string) $request->query('q', '')), 'status' => (string) $request->query('status', 'all')];
        $result = (new AdminRepository())->orders($filters, (int) $request->query('page', 1));
        return $this->adminView('orders', 'سفارش‌ها و پرداخت‌ها', [...$result, 'filters' => $filters, 'statusCounts' => (new AdminRepository())->orderStatusCounts()]);
    }

    public function order(Request $request, string $id): Response
    {
        $order = (new AdminRepository())->order($id);
        return $order ? $this->adminView('order-details', 'جزئیات سفارش', ['order' => $order]) : Response::html('<h1>404 - سفارش پیدا نشد</h1>', 404);
    }

    public function engagements(Request $request): Response
    {
        $type = (string) $request->query('type', 'messages');
        if (!in_array($type, ['messages','events','community'], true)) $type = 'messages';
        $filters = ['query' => trim((string) $request->query('q', '')), 'status' => (string) $request->query('status', 'all')];
        $result = (new AdminRepository())->engagements($type, $filters, (int) $request->query('page', 1));
        return $this->adminView('engagements', 'مرکز ارتباطات', [...$result, 'filters' => $filters]);
    }

    public function updateEngagement(Request $request, string $type, string $id): Response
    {
        try { (new AdminRepository())->updateEngagementStatus($type, $id, (string) $request->input('status')); }
        catch (RuntimeException) { return Response::redirect('/admin/engagements?type=' . rawurlencode($type) . '&error=status'); }
        Audit::record('engagement.status.updated', $type, $id, $this->currentUser()['id'] ?? null, [], ['status' => $request->input('status')], $request->ip());
        return Response::redirect('/admin/engagements?type=' . rawurlencode($type) . '&updated=1');
    }

    public function content(Request $request): Response
    {
        $modules = [
            ['key'=>'academy','title'=>'لاین‌های آکادمی','count'=>count(PublicContentService::academyLines()),'status'=>'published','url'=>'/academy'],
            ['key'=>'events','title'=>'رویدادها','count'=>count(PublicContentService::events()),'status'=>count(PublicContentService::events())?'published':'coming-soon','url'=>'/events'],
            ['key'=>'courses','title'=>'دوره‌ها','count'=>count(PublicContentService::courses()),'status'=>count(PublicContentService::courses())?'published':'coming-soon','url'=>'/courses'],
            ['key'=>'programs','title'=>'برنامه‌ها','count'=>count(PublicContentService::programs()),'status'=>count(PublicContentService::programs())?'published':'coming-soon','url'=>'/programs'],
            ['key'=>'experts','title'=>'اساتید و همکاران','count'=>count(PublicContentService::mentors()),'status'=>'published','url'=>'/mentors'],
            ['key'=>'articles','title'=>'پژوهش و محتوا','count'=>count(PublicContentService::articles()),'status'=>count(PublicContentService::articles())?'published':'coming-soon','url'=>'/'],
        ];
        return $this->adminView('content', 'مدیریت محتوا', ['modules' => $modules]);
    }

    public function analytics(Request $request): Response
    {
        $repository = new AdminRepository();
        return $this->adminView('analytics', 'تحلیل و گزارش‌ها', ['stats' => $repository->dashboard(), 'trends' => $repository->monthlyMetrics(12), 'roles' => $repository->roleCounts(), 'orderStatuses' => $repository->orderStatusCounts()]);
    }

    public function audit(Request $request): Response
    {
        $filters = ['query' => trim((string) $request->query('q', ''))];
        return $this->adminView('audit', 'گزارش فعالیت مدیران', [...(new AdminRepository())->auditLogs($filters, (int) $request->query('page', 1)), 'filters' => $filters]);
    }

    public function system(Request $request): Response
    {
        return $this->adminView('system', 'امنیت و سلامت سیستم', ['health' => (new AdminRepository())->systemHealth(), 'environment' => ['php' => PHP_VERSION, 'app_env' => env('APP_ENV', 'local'), 'debug' => (bool) env('APP_DEBUG', false), 'session_driver' => env('SESSION_DRIVER', 'files'), 'rate_driver' => env('RATE_LIMIT_DRIVER', 'session'), 'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]]);
    }

    private function currentUser(): array { return (new AuthService())->user() ?? []; }
    private function adminView(string $view, string $title, array $data = []): Response { return $this->view('admin.' . $view, ['title' => $title . ' | Mentoris Admin', 'admin' => $this->currentUser(), ...$data], 'layouts.admin'); }
}

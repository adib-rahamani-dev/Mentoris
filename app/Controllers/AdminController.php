<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Authorization;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
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
        $admin = $this->currentUser();
        return $this->adminView('dashboard', 'مرکز کنترل', [
            'stats' => $repository->dashboard(),
            'trends' => $repository->monthlyMetrics(8),
            'recentUsers' => Authorization::can($admin, 'users.view') ? array_slice((new UserRepository())->all(), 0, 6) : [],
            'recentOrders' => Authorization::can($admin, 'orders.view') ? (new CommerceRepository())->recentOrders(6) : [],
            'recentAudit' => Authorization::can($admin, 'audit.view') ? $repository->recentAudit(7) : [],
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

    public function createUser(Request $request): Response
    {
        return $this->adminView('user-create', 'ساخت حساب جدید', ['roles'=>Authorization::ROLES,'errors'=>[],'old'=>['account_role'=>(string)$request->query('role','instructor'),'status'=>'active']]);
    }

    public function storeUser(Request $request): Response
    {
        $data = $request->only(['name','email','phone','professional_role','bio','password','password_confirmation','account_role','status']);
        $validator = new Validator();
        $validator->validate($data, [
            'name'=>'required|string|min:2|max:120','email'=>'required|email|max:191','phone'=>'string|max:24',
            'professional_role'=>'string|max:120','bio'=>'string|max:5000','password'=>'required|string|min:12|max:128',
            'password_confirmation'=>'required|same:password','account_role'=>'required|string','status'=>'required|string',
        ]);
        $errors = $validator->errors();
        if (!array_key_exists((string)($data['account_role'] ?? ''), Authorization::ROLES)) $errors['account_role'][] = 'نقش انتخاب‌شده معتبر نیست.';
        if (!in_array((string)($data['status'] ?? ''), ['active','suspended'], true)) $errors['status'][] = 'وضعیت حساب معتبر نیست.';
        if (($data['account_role'] ?? '') === 'super_admin' && Authorization::role($this->currentUser()) !== 'super_admin') $errors['account_role'][] = 'فقط مدیرکل می‌تواند مدیرکل دیگری بسازد.';
        if (!isset($errors['password']) && (!preg_match('/[A-Za-z]/', (string)($data['password'] ?? '')) || !preg_match('/\d/', (string)($data['password'] ?? '')))) $errors['password'][] = 'رمز باید حداقل یک حرف و یک عدد داشته باشد.';
        if ($errors) return $this->adminView('user-create', 'ساخت حساب جدید', ['roles'=>Authorization::ROLES,'errors'=>$errors,'old'=>array_diff_key($data,['password'=>1,'password_confirmation'=>1])]);
        try { $created = (new UserRepository())->createManaged($data); }
        catch (RuntimeException $exception) { return $this->adminView('user-create', 'ساخت حساب جدید', ['roles'=>Authorization::ROLES,'errors'=>['form'=>[$exception->getMessage()]],'old'=>array_diff_key($data,['password'=>1,'password_confirmation'=>1])]); }
        Audit::record('user.created.by_admin', 'user', $created['id'], $this->currentUser()['id'] ?? null, [], ['email'=>$created['email'],'account_role'=>$created['account_role']], $request->ip());
        return Response::redirect('/admin/users/' . $created['id'] . '?created=1');
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

    public function updateUserProfile(Request $request, string $id): Response
    {
        $repository = new UserRepository();
        $target = $repository->findById($id);
        if ($target === null) return Response::html('<h1>404 - کاربر پیدا نشد</h1>', 404);
        if (Authorization::role($target) === 'super_admin' && Authorization::role($this->currentUser()) !== 'super_admin') return Response::html('<h1>403 - فقط مدیرکل مجاز است</h1>', 403);
        $data = $request->only(['name','email','phone','professional_role','bio']);
        $validator = new Validator();
        $validator->validate($data, ['name'=>'required|string|min:2|max:120','email'=>'required|email|max:191','phone'=>'string|max:24','professional_role'=>'string|max:120','bio'=>'string|max:5000']);
        if ($validator->fails()) return Response::redirect('/admin/users/' . $id . '?error=profile');
        try { $updated = $repository->updateManagedProfile($id, $data); }
        catch (RuntimeException) { return Response::redirect('/admin/users/' . $id . '?error=email'); }
        Audit::record('user.profile.updated.by_admin', 'user', $id, $this->currentUser()['id'] ?? null, ['name'=>$target['name'],'email'=>$target['email']], ['name'=>$updated['name'] ?? '', 'email'=>$updated['email'] ?? ''], $request->ip());
        return Response::redirect('/admin/users/' . $id . '?profile_updated=1');
    }

    public function updateUserPassword(Request $request, string $id): Response
    {
        $repository = new UserRepository();
        $target = $repository->findById($id);
        if ($target === null) return Response::html('<h1>404 - کاربر پیدا نشد</h1>', 404);
        if (Authorization::role($target) === 'super_admin' && Authorization::role($this->currentUser()) !== 'super_admin') return Response::html('<h1>403 - فقط مدیرکل مجاز است</h1>', 403);
        $data = $request->only(['password','password_confirmation']);
        $validator = new Validator();
        $validator->validate($data, ['password'=>'required|string|min:12|max:128','password_confirmation'=>'required|same:password']);
        $validStrength = preg_match('/[A-Za-z]/', (string)($data['password'] ?? '')) && preg_match('/\d/', (string)($data['password'] ?? ''));
        if ($validator->fails() || !$validStrength) return Response::redirect('/admin/users/' . $id . '?error=password');
        $repository->setManagedPassword($id, (string)$data['password']);
        Audit::record('user.password.reset.by_admin', 'user', $id, $this->currentUser()['id'] ?? null, [], ['sessions_revoked'=>true], $request->ip());
        return Response::redirect('/admin/users/' . $id . '?password_updated=1');
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
            ['key'=>'specializations','title'=>'تخصص‌ها','count'=>count(PublicContentService::specializations()),'status'=>count(PublicContentService::specializations())?'published':'coming-soon','url'=>'/specializations'],
            ['key'=>'events','title'=>'رویدادها','count'=>count(PublicContentService::events()),'status'=>count(PublicContentService::events())?'published':'coming-soon','url'=>'/events'],
            ['key'=>'courses','title'=>'دوره‌ها','count'=>count(PublicContentService::courses()),'status'=>count(PublicContentService::courses())?'published':'coming-soon','url'=>'/courses'],
            ['key'=>'programs','title'=>'برنامه‌ها','count'=>count(PublicContentService::programs()),'status'=>count(PublicContentService::programs())?'published':'coming-soon','url'=>'/programs'],
            ['key'=>'experts','title'=>'اساتید و همکاران','count'=>count(PublicContentService::mentors()),'status'=>'published','url'=>'/mentors'],
            ['key'=>'articles','title'=>'پژوهش و محتوا','count'=>count(PublicContentService::articles()),'status'=>count(PublicContentService::articles())?'published':'coming-soon','url'=>'/'],
        ];
        $filters = ['query' => trim((string) $request->query('q', '')), 'type' => (string) $request->query('type', 'all'), 'status' => (string) $request->query('status', 'all')];
        return $this->adminView('content', 'مدیریت محتوا', ['modules' => $modules, ...((new AdminRepository())->contentEntries($filters, (int) $request->query('page', 1))), 'filters' => $filters, 'types' => $this->contentTypes()]);
    }

    public function createContent(Request $request): Response
    {
        $preset = (string) $request->query('type', 'article');
        if (!array_key_exists($preset, $this->contentTypes())) $preset = 'article';
        return $this->adminView('content-editor', 'محتوای جدید', ['entry' => null, 'types' => $this->contentTypes(), 'errors' => [], 'old' => ['entity_type'=>$preset,'status'=>'draft']]);
    }

    public function editContent(Request $request, string $id): Response
    {
        $entry = (new AdminRepository())->contentEntry($id);
        return $entry ? $this->adminView('content-editor', 'ویرایش محتوا', ['entry' => $entry, 'types' => $this->contentTypes(), 'errors' => [], 'old' => []]) : Response::html('<h1>404 - محتوا پیدا نشد</h1>', 404);
    }

    public function storeContent(Request $request): Response
    {
        return $this->persistContent($request, null);
    }

    public function updateContent(Request $request, string $id): Response
    {
        return $this->persistContent($request, $id);
    }

    public function updateContentStatus(Request $request, string $id): Response
    {
        $status = (string) $request->input('status', 'draft');
        try { $result = (new AdminRepository())->setContentStatus($id, $status); }
        catch (RuntimeException) { return Response::redirect('/admin/content?error=status'); }
        if ($result === null) return Response::html('<h1>404 - محتوا پیدا نشد</h1>', 404);
        Audit::record('content.status.updated', 'content', $id, $this->currentUser()['id'] ?? null, ['status'=>$result['before']['status']], ['status'=>$status], $request->ip());
        return Response::redirect('/admin/content/' . $id . '/edit?status_updated=1');
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
        $roleKeys = array_keys(Authorization::ROLES);
        $permissionMatrix = array_combine($roleKeys, array_map(static fn(string $role): array => Authorization::permissionsForRole($role), $roleKeys));
        return $this->adminView('system', 'امنیت و سلامت سیستم', ['health' => (new AdminRepository())->systemHealth(), 'roles'=>Authorization::ROLES, 'permissionMatrix'=>$permissionMatrix, 'environment' => ['php' => PHP_VERSION, 'app_env' => env('APP_ENV', 'local'), 'debug' => (bool) env('APP_DEBUG', false), 'session_driver' => env('SESSION_DRIVER', 'files'), 'rate_driver' => env('RATE_LIMIT_DRIVER', 'session'), 'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]]);
    }

    private function currentUser(): array { return (new AuthService())->user() ?? []; }
    private function adminView(string $view, string $title, array $data = []): Response { return $this->view('admin.' . $view, ['title' => $title . ' | Mentoris Admin', 'admin' => $this->currentUser(), ...$data], 'layouts.admin'); }

    private function persistContent(Request $request, ?string $id): Response
    {
        $data = [
            'entity_type' => trim((string) $request->input('entity_type')),
            'slug' => strtolower(trim((string) $request->input('slug'))),
            'status' => trim((string) $request->input('status', 'draft')),
            'sort_order' => (int) $request->input('sort_order', 0),
            'translations' => [],
        ];
        foreach (['fa','ar','ku','en'] as $locale) {
            $metadataRaw = trim((string) $request->input("{$locale}_metadata", ''));
            $metadata = $metadataRaw === '' ? [] : json_decode($metadataRaw, true);
            $common = [
                'image'=>trim((string)$request->input('image','')),'category'=>trim((string)$request->input('category','')),
                'duration'=>trim((string)$request->input('duration','')),'schedule'=>trim((string)$request->input('schedule','')),
                'format'=>trim((string)$request->input('format','')),'location'=>trim((string)$request->input('location','')),
                'starts_at'=>trim((string)$request->input('starts_at','')),'ends_at'=>trim((string)$request->input('ends_at','')),
                'price_amount'=>max(0,(int)$request->input('price_amount',0)),'capacity'=>max(0,(int)$request->input('capacity',0)),
                'line_slug'=>trim((string)$request->input('line_slug','')),
                'instructor_slug'=>trim((string)$request->input('instructor_slug','')),
                'content_status'=>trim((string)$request->input('content_status','')),
                'registration_url'=>trim((string)$request->input('registration_url','')),
                'level'=>trim((string)$request->input('level','')),
                'read_time'=>trim((string)$request->input('read_time','')),
                'author'=>trim((string)$request->input('author','')),
                'tone'=>trim((string)$request->input('tone','')),
                'icon'=>trim((string)$request->input('icon','')),
                'promise'=>trim((string)$request->input('promise','')),
                'target_audience'=>$this->textList((string)$request->input('target_audience','')),
                'objectives'=>$this->textList((string)$request->input('objectives','')),
                'audience'=>$this->textList((string)$request->input('audience','')),
                'highlights'=>$this->textList((string)$request->input('highlights','')),
                'related_courses'=>$this->slugList((string)$request->input('related_courses','')),
                'related_events'=>$this->slugList((string)$request->input('related_events','')),
                'related_mentors'=>$this->slugList((string)$request->input('related_mentors','')),
                'featured'=>$request->input('featured') === '1',
            ];
            if ($data['entity_type'] === 'course' && $common['content_status'] !== '') $common['course_status'] = $common['content_status'];
            if ($data['entity_type'] === 'event' && $common['content_status'] !== '') $common['event_status'] = $common['content_status'];
            unset($common['content_status']);
            $common = array_filter($common, static fn ($value): bool => $value !== '' && $value !== false && $value !== 0 && $value !== []);
            $data['translations'][$locale] = ['title'=>(string)$request->input("{$locale}_title",''),'subtitle'=>(string)$request->input("{$locale}_subtitle",''),'excerpt'=>(string)$request->input("{$locale}_excerpt",''),'body'=>(string)$request->input("{$locale}_body",''),'metadata'=>is_array($metadata)?array_replace($metadata,$common):null];
        }
        $errors = [];
        if (!array_key_exists($data['entity_type'], $this->contentTypes())) $errors['entity_type'][] = 'نوع محتوا معتبر نیست.';
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug']) || strlen($data['slug']) > 190) $errors['slug'][] = 'نامک باید انگلیسی، کوتاه و با خط تیره باشد.';
        if (!in_array($data['status'], ['draft','published','archived'], true)) $errors['status'][] = 'وضعیت محتوا معتبر نیست.';
        if (mb_strlen(trim($data['translations']['fa']['title'])) < 3) $errors['fa_title'][] = 'عنوان فارسی الزامی است.';
        foreach ($data['translations'] as $locale => $translation) {
            if (mb_strlen($translation['title']) > 255) $errors[$locale . '_title'][] = 'عنوان حداکثر ۲۵۵ کاراکتر است.';
            if (mb_strlen($translation['subtitle']) > 255) $errors[$locale . '_subtitle'][] = 'زیرعنوان حداکثر ۲۵۵ کاراکتر است.';
            if (mb_strlen($translation['excerpt']) > 3000) $errors[$locale . '_excerpt'][] = 'خلاصه حداکثر ۳۰۰۰ کاراکتر است.';
            if (mb_strlen($translation['body']) > 100000) $errors[$locale . '_body'][] = 'متن محتوا بیش از حد مجاز است.';
            if (mb_strlen((string)$request->input($locale . '_metadata','')) > 50000) $errors[$locale . '_metadata'][] = 'متادیتا بیش از حد مجاز است.';
        }
        $image = trim((string)$request->input('image',''));
        if ($image !== '' && (!preg_match('#^images/[A-Za-z0-9][A-Za-z0-9._/-]*$#', $image) || str_contains($image, '..'))) $errors['image'][] = 'مسیر تصویر باید داخل assets/images و بدون .. باشد.';
        $upload = $request->file('image_upload');
        if (is_array($upload) && (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadError = $this->validateImageUpload($upload);
            if ($uploadError !== null) $errors['image_upload'][] = $uploadError;
        }
        foreach (['line_slug','instructor_slug'] as $slugField) {
            $value = trim((string)$request->input($slugField,''));
            if ($value !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) $errors[$slugField][] = 'این نامک فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.';
        }
        foreach (['related_courses','related_events','related_mentors'] as $listField) {
            foreach ($this->slugList((string)$request->input($listField,'')) as $relatedSlug) {
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $relatedSlug)) { $errors[$listField][] = 'فهرست ارتباط‌ها شامل نامک نامعتبر است.'; break; }
            }
        }
        $registrationUrl = trim((string)$request->input('registration_url',''));
        if ($registrationUrl !== '' && !preg_match('#^(?:https://[^\s]+|/[A-Za-z0-9/_?&=%.-]*)$#i', $registrationUrl)) $errors['registration_url'][] = 'لینک ثبت‌نام باید HTTPS یا یک مسیر داخلی معتبر باشد.';
        $contentStatus = trim((string)$request->input('content_status',''));
        $allowedContentStatuses = $data['entity_type'] === 'course' ? ['','active','coming-soon','full','completed'] : ($data['entity_type'] === 'event' ? ['','upcoming','registration-open','full','completed','canceled'] : ['']);
        if (!in_array($contentStatus, $allowedContentStatuses, true)) $errors['content_status'][] = 'وضعیت عملیاتی برای این نوع محتوا معتبر نیست.';
        if (!in_array(trim((string)$request->input('tone','')), ['','sage','teal','blue','violet','amber','rose','indigo'], true)) $errors['tone'][] = 'رنگ انتخاب‌شده معتبر نیست.';
        if (!in_array(trim((string)$request->input('icon','')), ['','brain','book','users','search','heart','activity','shield','trending','certificate'], true)) $errors['icon'][] = 'آیکون انتخاب‌شده معتبر نیست.';
        foreach ($data['translations'] as $locale => $translation) if ($translation['metadata'] === null) $errors[$locale . '_metadata'][] = 'JSON متادیتا معتبر نیست.';
        if ($errors) return $this->adminView('content-editor', $id ? 'ویرایش محتوا' : 'محتوای جدید', ['entry'=>$id ? (new AdminRepository())->contentEntry($id) : null,'types'=>$this->contentTypes(),'errors'=>$errors,'old'=>$data]);
        $uploadedPath = null;
        if (is_array($upload) && (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try { $uploadedPath = $this->storeImageUpload($upload); }
            catch (RuntimeException $exception) { return $this->adminView('content-editor', $id ? 'ویرایش محتوا' : 'محتوای جدید', ['entry'=>$id ? (new AdminRepository())->contentEntry($id) : null,'types'=>$this->contentTypes(),'errors'=>['image_upload'=>[$exception->getMessage()]],'old'=>$data]); }
            foreach ($data['translations'] as &$translation) $translation['metadata']['image'] = $uploadedPath;
            unset($translation);
        }
        try { $saved = (new AdminRepository())->saveContent($id, $data, (string) ($this->currentUser()['id'] ?? '')); }
        catch (\Throwable $exception) {
            if ($uploadedPath !== null) { $uploadedAbsolute = base_path('public/assets/' . $uploadedPath); if (is_file($uploadedAbsolute)) unlink($uploadedAbsolute); }
            return $this->adminView('content-editor', $id ? 'ویرایش محتوا' : 'محتوای جدید', ['entry'=>$id ? (new AdminRepository())->contentEntry($id) : null,'types'=>$this->contentTypes(),'errors'=>['form'=>['ذخیره انجام نشد؛ نامک تکراری یا اتصال دیتابیس را بررسی کنید.']],'old'=>$data]);
        }
        Audit::record($id ? 'content.updated' : 'content.created', 'content', $saved['id'], $this->currentUser()['id'] ?? null, $saved['before'] ?? [], $saved['after'] ?? [], $request->ip());
        return Response::redirect('/admin/content/' . $saved['id'] . '/edit?saved=1');
    }

    private function contentTypes(): array
    {
        return ['academy_line'=>'لاین آکادمی','specialization'=>'تخصص','program'=>'برنامه','course'=>'دوره','event'=>'رویداد','mentor'=>'مدرس/منتور','article'=>'مقاله'];
    }

    private function slugList(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (string $item): string => strtolower(trim($item)),
            preg_split('/[\r\n,]+/', $value) ?: []
        ))));
    }

    private function textList(string $value): array
    {
        return array_values(array_filter(array_map(static fn(string $item): string => trim($item), preg_split('/[\r\n]+/', $value) ?: [])));
    }

    private function validateImageUpload(array $file): ?string
    {
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return 'بارگذاری تصویر کامل نشد.';
        $size = (int)($file['size'] ?? 0);
        if ($size < 1 || $size > 5 * 1024 * 1024) return 'حجم تصویر باید کمتر از ۵ مگابایت باشد.';
        $temporary = (string)($file['tmp_name'] ?? '');
        if ($temporary === '' || !is_uploaded_file($temporary)) return 'فایل بارگذاری‌شده معتبر نیست.';
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary) ?: '';
        if (!array_key_exists($mime, ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'])) return 'فقط تصویر واقعی JPEG، PNG یا WebP پذیرفته می‌شود.';
        $dimensions = @getimagesize($temporary);
        if (!is_array($dimensions) || ($dimensions[0] ?? 0) < 64 || ($dimensions[1] ?? 0) < 64 || ($dimensions[0] ?? 0) > 8000 || ($dimensions[1] ?? 0) > 8000) return 'ابعاد تصویر معتبر نیست یا بیش از حد بزرگ است.';
        return null;
    }

    private function storeImageUpload(array $file): string
    {
        $error = $this->validateImageUpload($file);
        if ($error !== null) throw new RuntimeException($error);
        $temporary = (string)$file['tmp_name'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary) ?: '';
        $extension = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
        if ($extension === null) throw new RuntimeException('فرمت تصویر قابل ذخیره نیست.');
        $directory = base_path('public/assets/images/uploads');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('پوشه امن تصاویر قابل ساخت نیست.');
        $filename = 'content-' . Security::randomToken(12) . '.' . $extension;
        $target = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporary, $target)) throw new RuntimeException('ذخیره تصویر انجام نشد.');
        return 'images/uploads/' . $filename;
    }
}

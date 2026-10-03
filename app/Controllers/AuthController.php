<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\RateLimiter;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use RuntimeException;

final class AuthController extends Controller
{
    public function registerForm(Request $request): Response { return $this->authView('register'); }

    public function register(Request $request): Response
    {
        $data = $request->only(['name', 'phone', 'email', 'password', 'password_confirmation', 'accept', 'member_type', 'marketing_consent']);
        $data['phone'] = is_string($data['phone'] ?? null) && strlen($data['phone']) <= 240 ? \App\Core\PhoneNumber::normalize($data['phone']) : '';
        $validator = new Validator();
        $validator->validate($data, ['name' => 'required|string|min:2|max:80', 'email' => 'required|email|max:120', 'password' => 'required|string|min:8|max:128', 'password_confirmation' => 'required|same:password', 'accept' => 'required']);
        $errors = $validator->errors();
        if (!preg_match('/^09[0-9]{9}$/', $data['phone'])) $errors['phone'] = ['شماره موبایل معتبر وارد کنید؛ فرمت ۰۹ یا ‎+۹۸ پذیرفته می‌شود.'];
        if (!is_string($data['member_type'] ?? null) || !isset(\App\Services\MemberProfileService::OPTIONS['member_type'][$data['member_type']])) $errors['member_type'] = ['نوع عضویت را انتخاب کنید.'];
        if (($data['accept'] ?? '') !== '1') $errors['accept'] = ['پذیرش قوانین ضروری است.'];
        if (!isset($errors['password']) && (!preg_match('/[A-Za-z]/', (string) ($data['password'] ?? '')) || !preg_match('/\d/', (string) ($data['password'] ?? '')))) {
            $errors['password'][] = 'رمز عبور باید حداقل یک حرف و یک عدد داشته باشد.';
        }
        if ($errors) return $this->authView('register', $errors, $this->safeOld($data));

        try {
            (new AuthService())->register($data);
        } catch (RuntimeException $exception) {
            return $this->authView('register', [$exception->getCode() === 409 ? 'phone' : 'email' => [$exception->getMessage()]], $this->safeOld($data));
        }
        return $this->redirect('/profile?welcome=1');
    }

    public function loginForm(Request $request): Response { return $this->authView('login', extra:['loginWithEmail'=>$request->query('method')==='email']); }

    public function login(Request $request): Response
    {
        $raw=$request->input('identifier',$request->input('phone',$request->input('email','')));
        $identifier=is_string($raw) && strlen($raw)<=240 ? AuthService::normalizeLoginIdentifier($raw) : '';
        $data=['identifier'=>$identifier,'password'=>$request->input('password')];
        $old=['identifier'=>$identifier];
        $extra=['loginWithEmail'=>$request->input('login_method')==='email' || str_contains($identifier,'@')];
        $validator = new Validator();
        $validator->validate($data, ['identifier' => 'required|string|max:120', 'password' => 'required|string|max:128']);
        $errors=$validator->errors();
        if (!preg_match('/^09[0-9]{9}$/',$identifier) && !filter_var($identifier,FILTER_VALIDATE_EMAIL)) $errors['identifier']=['شمارهٔ موبایل معتبر وارد کنید؛ برای استفاده از ایمیل، گزینهٔ ورود با ایمیل را انتخاب کنید.'];
        if ($errors) return $this->authView('login', $errors, $old, extra:$extra);
        $limiter = new RateLimiter();
        $limitKeys=['login-account|'.$identifier]; $limited=false;
        $allowed=(new AuthService())->attempt($identifier,(string)$data['password'],static function(?string $userId) use ($limiter,&$limitKeys,&$limited): bool {
            if($userId!==null) $limitKeys[]='login-user|'.$userId;
            $limits=array_map(static fn(string $key): array=>['key'=>$key,'max'=>10,'seconds'=>900],$limitKeys);
            $reservation=$limiter->reserveMany($limits,static function(\PDO $pdo): void {});
            $limited=!$reservation['allowed']; return !$limited;
        });
        if ($limited) return $this->authView('login', ['credentials' => ['تلاش‌های ورود بیش از حد مجاز بود؛ ۱۵ دقیقه دیگر دوباره امتحان کنید.']], $old, extra:$extra);
        if (!$allowed) {
            return $this->authView('login', ['credentials' => ['شماره، ایمیل یا رمز عبور صحیح نیست. در صورت نیاز، ورود با ایمیل را امتحان کنید.']], $old, extra:$extra);
        }
        foreach($limitKeys as $key) $limiter->clear($key);
        return $this->redirect($this->intended());
    }

    public function logout(Request $request): Response
    {
        (new AuthService())->logout();
        return $this->redirect('/login');
    }

    public function forgotForm(Request $request): Response { return $this->authView('forgot-password'); }

    public function forgot(Request $request): Response
    {
        $data = $request->only(['email']);
        $validator = new Validator();
        $validator->validate($data, ['email' => 'required|email|max:120']);
        if ($validator->fails()) return $this->authView('forgot-password', $validator->errors(), $data);
        $token = (new UserRepository())->issueResetToken((string) $data['email']);
        if ($token !== null) (new \App\Services\EmailService())->send((string)$data['email'], 'بازیابی رمز منتوریس', 'برای تنظیم رمز جدید تا یک ساعت آینده از این لینک استفاده کنید: '.rtrim((string)env('APP_URL','https://mentorisacademy.com'),'/').'/reset-password/'.$token);
        $preview = $token !== null && env('APP_ENV', 'local') === 'local' ? '/reset-password/' . $token : null;
        return $this->authView('forgot-password', [], [], true, ['resetPreview' => $preview]);
    }

    public function resetForm(Request $request, string $token): Response
    {
        return $this->resetView($token, strlen($token) === 64 && (new UserRepository())->findByResetToken($token) !== null);
    }

    public function reset(Request $request, string $token): Response
    {
        $repository = new UserRepository();
        if (strlen($token) !== 64 || $repository->findByResetToken($token) === null) return $this->resetView($token, false);
        $data = $request->only(['password', 'password_confirmation']);
        $validator = new Validator();
        $validator->validate($data, ['password' => 'required|string|min:8|max:128', 'password_confirmation' => 'required|same:password']);
        $errors = $validator->errors();
        if (!isset($errors['password']) && (!preg_match('/[A-Za-z]/', (string) ($data['password'] ?? '')) || !preg_match('/\d/', (string) ($data['password'] ?? '')))) $errors['password'][] = 'رمز عبور باید حداقل یک حرف و یک عدد داشته باشد.';
        if ($errors) return $this->resetView($token, true, $errors);
        if (!$repository->resetPassword($token, (string) $data['password'])) return $this->resetView($token, false);
        return $this->resetView($token, false, [], true);
    }

    private function authView(string $page, array $errors = [], array $old = [], bool $success = false, array $extra = []): Response
    {
        $titles = ['register' => 'ساخت حساب کاربری', 'login' => 'ورود به Mentoris', 'forgot-password' => 'بازیابی رمز عبور'];
        return $this->view('auth.' . $page, ['title' => $titles[$page], 'errors' => $errors, 'old' => $old, 'success' => $success, ...$extra]);
    }

    private function resetView(string $token, bool $valid, array $errors = [], bool $success = false): Response
    {
        return $this->view('auth.reset-password', ['title' => 'تنظیم رمز عبور جدید', 'token' => $token, 'valid' => $valid, 'errors' => $errors, 'success' => $success]);
    }

    private function safeOld(array $data): array { return array_intersect_key($data, array_flip(['name', 'email', 'phone', 'member_type', 'accept', 'marketing_consent'])); }

    private function intended(): string
    {
        $path = (string) (new Session())->pull('auth.intended', '/dashboard');
        return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : '/dashboard';
    }
}

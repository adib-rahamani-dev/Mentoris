<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
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
        $data = $request->only(['name', 'email', 'password', 'password_confirmation', 'accept']);
        $validator = new Validator();
        $validator->validate($data, ['name' => 'required|string|min:2|max:80', 'email' => 'required|email|max:120', 'password' => 'required|string|min:12|max:128', 'password_confirmation' => 'required|same:password', 'accept' => 'required']);
        $errors = $validator->errors();
        if (!isset($errors['password']) && (!preg_match('/[A-Za-z]/', (string) ($data['password'] ?? '')) || !preg_match('/\d/', (string) ($data['password'] ?? '')))) {
            $errors['password'][] = 'رمز عبور باید حداقل یک حرف و یک عدد داشته باشد.';
        }
        if ($errors) return $this->authView('register', $errors, $this->safeOld($data));

        try {
            (new AuthService())->register($data);
        } catch (RuntimeException $exception) {
            return $this->authView('register', ['email' => [$exception->getMessage()]], $this->safeOld($data));
        }
        return $this->redirect($this->intended());
    }

    public function loginForm(Request $request): Response { return $this->authView('login'); }

    public function login(Request $request): Response
    {
        $data = $request->only(['email', 'password']);
        $validator = new Validator();
        $validator->validate($data, ['email' => 'required|email|max:120', 'password' => 'required|string|max:128']);
        if ($validator->fails()) return $this->authView('login', $validator->errors(), ['email' => $data['email'] ?? '']);
        if (!(new AuthService())->attempt((string) $data['email'], (string) $data['password'])) {
            return $this->authView('login', ['credentials' => ['ایمیل یا رمز عبور صحیح نیست.']], ['email' => $data['email']]);
        }
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
        $validator->validate($data, ['password' => 'required|string|min:12|max:128', 'password_confirmation' => 'required|same:password']);
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

    private function safeOld(array $data): array { return array_intersect_key($data, array_flip(['name', 'email', 'accept'])); }

    private function intended(): string
    {
        $path = (string) (new Session())->pull('auth.intended', '/dashboard');
        return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : '/dashboard';
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Security;
use App\Core\Session;
use App\Core\CSRF;
use App\Repositories\UserRepository;

final class AuthService
{
    private static ?string $dummyHash = null;
    public function __construct(private readonly UserRepository $users = new UserRepository(), private readonly Session $session = new Session()) {}

    public function register(array $data): array
    {
        $user = $this->users->create($data);
        $this->loginUser($user);
        try { (new MailQueueService())->enqueue($user['email'], 'به منتوریس خوش آمدید', $user['name'].' عزیز، حساب شما ایجاد شد. پروفایل خود را اینجا تکمیل کنید: '.rtrim((string)env('APP_URL','https://mentorisacademy.com'),'/').'/profile'); }
        catch (\Throwable) { error_log('Welcome mail could not be queued. Account creation succeeded.'); }
        return UserRepository::publicUser($user);
    }

    public static function normalizeLoginIdentifier(string $identifier): string
    {
        $identifier=trim($identifier);
        if (str_contains($identifier,'@')) return mb_strtolower($identifier);
        return \App\Core\PhoneNumber::normalize($identifier);
    }

    public function attempt(string $identifier, string $password, ?callable $beforePassword = null): bool
    {
        $user = $this->users->findByLoginIdentifier(self::normalizeLoginIdentifier($identifier));
        if ($beforePassword !== null && !$beforePassword($user['id'] ?? null)) return false;
        $hash = $user !== null ? (string) $user['password_hash'] : (self::$dummyHash ??= Security::hashPassword('not-a-real-password-' . Security::randomToken(8)));
        $valid = Security::verifyPassword($password, $hash);
        if ($user === null || ($user['status'] ?? 'active') !== 'active' || !$valid) return false;
        if (Security::passwordNeedsRehash((string) $user['password_hash'])) $this->users->rehashPassword($user['id'], $password);
        $this->users->recordLogin((string) $user['id']);
        $this->loginUser($user);
        return true;
    }

    public function user(): ?array
    {
        $identity = $this->session->get('auth.user');
        if (!is_array($identity) || !isset($identity['id'])) return null;
        $user = $this->users->findById((string) $identity['id']);
        if ($user === null || ($user['status'] ?? '') !== 'active' || (int) ($identity['auth_version'] ?? 0) !== (int) ($user['auth_version'] ?? 1)) {
            $this->logout();
            return null;
        }
        return UserRepository::publicUser($user);
    }

    public function refresh(array $user): void
    {
        if (!isset($user['auth_version'])) $user = $this->users->findById($user['id']) ?? $user;
        $this->session->put('auth.user', ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'account_role' => $user['account_role'] ?? 'student', 'auth_version' => (int) ($user['auth_version'] ?? 1)]);
    }

    public function logout(): void { $this->session->invalidate(); }

    private function loginUser(array $user): void
    {
        $this->session->regenerate();
        (new CSRF($this->session))->regenerate();
        $this->refresh($user);
    }
}

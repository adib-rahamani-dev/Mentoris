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
        return UserRepository::publicUser($user);
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
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

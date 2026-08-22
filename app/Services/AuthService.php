<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Security;
use App\Core\Session;
use App\Repositories\UserRepository;

final class AuthService
{
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
        if ($user === null || !Security::verifyPassword($password, (string) $user['password_hash'])) return false;
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) $this->users->rehashPassword($user['id'], $password);
        $this->loginUser($user);
        return true;
    }

    public function user(): ?array
    {
        $identity = $this->session->get('auth.user');
        if (!is_array($identity) || !isset($identity['id'])) return null;
        $user = $this->users->findById((string) $identity['id']);
        return $user ? UserRepository::publicUser($user) : null;
    }

    public function refresh(array $user): void
    {
        $this->session->put('auth.user', ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']]);
    }

    public function logout(): void { $this->session->invalidate(); }

    private function loginUser(array $user): void
    {
        $this->session->regenerate();
        $this->refresh($user);
    }
}

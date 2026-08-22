<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Security;
use RuntimeException;

final class UserRepository
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $configuredPath = function_exists('env') ? env('USERS_STORAGE_PATH') : null;
        $this->path = $path ?? (is_string($configuredPath) && $configuredPath !== '' ? $configuredPath : (defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 2) . '/storage') . '/data/users.json');
    }

    public function findById(string $id): ?array
    {
        return $this->find(static fn (array $user): bool => $user['id'] === $id);
    }

    public function findByEmail(string $email): ?array
    {
        $email = self::normalizeEmail($email);
        return $this->find(static fn (array $user): bool => $user['email'] === $email);
    }

    public function create(array $attributes): array
    {
        return $this->mutate(function (array &$users) use ($attributes): array {
            $email = self::normalizeEmail((string) $attributes['email']);
            foreach ($users as $user) {
                if ($user['email'] === $email) {
                    throw new RuntimeException('این ایمیل قبلاً ثبت شده است.');
                }
            }
            $now = date(DATE_ATOM);
            $user = [
                'id' => Security::randomToken(12),
                'name' => trim((string) $attributes['name']),
                'email' => $email,
                'password_hash' => Security::hashPassword((string) $attributes['password']),
                'phone' => '', 'role' => '', 'bio' => '',
                'courses' => [], 'events' => [], 'certificates' => [],
                'notifications' => [[
                    'id' => Security::randomToken(8), 'title' => 'به Mentoris خوش آمدید',
                    'message' => 'پروفایل خود را کامل کنید و مسیر یادگیری مناسب را انتخاب کنید.',
                    'created_at' => $now, 'read_at' => null,
                ]],
                'reset_token_hash' => null, 'reset_token_expires_at' => null,
                'created_at' => $now, 'updated_at' => $now,
            ];
            $users[] = $user;
            return $user;
        });
    }

    public function updateProfile(string $id, array $attributes): ?array
    {
        return $this->mutate(function (array &$users) use ($id, $attributes): ?array {
            foreach ($users as &$user) {
                if ($user['id'] !== $id) continue;
                foreach (['name', 'phone', 'role', 'bio'] as $field) {
                    if (array_key_exists($field, $attributes)) $user[$field] = trim((string) $attributes[$field]);
                }
                $user['updated_at'] = date(DATE_ATOM);
                return $user;
            }
            return null;
        });
    }

    public function rehashPassword(string $id, string $password): void
    {
        $this->mutate(function (array &$users) use ($id, $password): bool {
            foreach ($users as &$user) {
                if ($user['id'] === $id) {
                    $user['password_hash'] = Security::hashPassword($password);
                    $user['updated_at'] = date(DATE_ATOM);
                    return true;
                }
            }
            return false;
        });
    }

    public function issueResetToken(string $email, int $ttlSeconds = 3600): ?string
    {
        return $this->mutate(function (array &$users) use ($email, $ttlSeconds): ?string {
            $email = self::normalizeEmail($email);
            foreach ($users as &$user) {
                if ($user['email'] !== $email) continue;
                $token = Security::randomToken(32);
                $user['reset_token_hash'] = hash('sha256', $token);
                $user['reset_token_expires_at'] = time() + $ttlSeconds;
                $user['updated_at'] = date(DATE_ATOM);
                return $token;
            }
            return null;
        });
    }

    public function findByResetToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        return $this->find(static fn (array $user): bool => is_string($user['reset_token_hash'] ?? null)
            && ($user['reset_token_expires_at'] ?? 0) >= time()
            && Security::hashEquals($user['reset_token_hash'], $hash));
    }

    public function resetPassword(string $token, string $password): bool
    {
        $hash = hash('sha256', $token);
        return $this->mutate(function (array &$users) use ($hash, $password): bool {
            foreach ($users as &$user) {
                if (!is_string($user['reset_token_hash'] ?? null) || ($user['reset_token_expires_at'] ?? 0) < time()) continue;
                if (!Security::hashEquals($user['reset_token_hash'], $hash)) continue;
                $user['password_hash'] = Security::hashPassword($password);
                $user['reset_token_hash'] = null;
                $user['reset_token_expires_at'] = null;
                $user['updated_at'] = date(DATE_ATOM);
                return true;
            }
            return false;
        });
    }

    public function markNotificationsRead(string $id): void
    {
        $this->mutate(function (array &$users) use ($id): bool {
            foreach ($users as &$user) {
                if ($user['id'] !== $id) continue;
                foreach ($user['notifications'] as &$notification) $notification['read_at'] ??= date(DATE_ATOM);
                $user['updated_at'] = date(DATE_ATOM);
                return true;
            }
            return false;
        });
    }

    public function addCourse(string $id, string $courseSlug): ?array
    {
        return $this->mutate(function (array &$users) use ($id, $courseSlug): ?array {
            foreach ($users as &$user) {
                if ($user['id'] !== $id) continue;
                if (!in_array($courseSlug, $user['courses'], true)) {
                    $user['courses'][] = $courseSlug;
                    $user['notifications'][] = [
                        'id' => Security::randomToken(8), 'title' => 'ثبت‌نام دوره نهایی شد',
                        'message' => 'دوره جدید به بخش «دوره‌های من» اضافه شد.', 'created_at' => date(DATE_ATOM), 'read_at' => null,
                    ];
                }
                $user['updated_at'] = date(DATE_ATOM);
                return $user;
            }
            return null;
        });
    }

    public static function publicUser(array $user): array
    {
        return array_diff_key($user, array_flip(['password_hash', 'reset_token_hash', 'reset_token_expires_at']));
    }

    private function find(callable $predicate): ?array
    {
        foreach ($this->read() as $user) if ($predicate($user)) return $user;
        return null;
    }

    private function read(): array
    {
        if (!is_file($this->path)) return [];
        $handle = fopen($this->path, 'rb');
        if ($handle === false) throw new RuntimeException('امکان خواندن مخزن کاربران وجود ندارد.');
        try {
            flock($handle, LOCK_SH);
            $decoded = json_decode(stream_get_contents($handle) ?: '[]', true);
            return is_array($decoded) ? $decoded : [];
        } finally {
            flock($handle, LOCK_UN); fclose($handle);
        }
    }

    private function mutate(callable $callback): mixed
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('امکان ساخت پوشه داده وجود ندارد.');
        $handle = fopen($this->path, 'c+b');
        if ($handle === false) throw new RuntimeException('امکان نوشتن در مخزن کاربران وجود ندارد.');
        try {
            if (!flock($handle, LOCK_EX)) throw new RuntimeException('امکان قفل‌کردن مخزن کاربران وجود ندارد.');
            rewind($handle);
            $decoded = json_decode(stream_get_contents($handle) ?: '[]', true);
            $users = is_array($decoded) ? $decoded : [];
            $result = $callback($users);
            $json = json_encode($users, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            rewind($handle); ftruncate($handle, 0); fwrite($handle, $json); fflush($handle);
            return $result;
        } finally {
            flock($handle, LOCK_UN); fclose($handle);
        }
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}

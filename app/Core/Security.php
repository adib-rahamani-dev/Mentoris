<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    private static ?string $cspNonce = null;
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function sanitize(string $value): string
    {
        return trim(strip_tags($value));
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function hashPassword(string $password): string
    {
        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $options = $algorithm === PASSWORD_ARGON2ID
            ? ['memory_cost' => max(32 * 1024, (int) env('PASSWORD_MEMORY_COST', 65536)), 'time_cost' => max(2, (int) env('PASSWORD_TIME_COST', 4)), 'threads' => max(1, (int) env('PASSWORD_THREADS', 2))]
            : ['cost' => max(10, (int) env('PASSWORD_BCRYPT_COST', 12))];
        $hash = password_hash($password, $algorithm, $options);
        if (!is_string($hash)) throw new \RuntimeException('Password hashing failed.');
        return $hash;
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function hashEquals(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    public static function passwordNeedsRehash(string $hash): bool
    {
        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        return password_needs_rehash($hash, $algorithm);
    }

    public static function cspNonce(): string
    {
        return self::$cspNonce ??= base64_encode(random_bytes(18));
    }
}

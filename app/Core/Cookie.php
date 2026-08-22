<?php

declare(strict_types=1);

namespace App\Core;

final class Cookie
{
    public static function get(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function set(
        string $name,
        string $value,
        int $minutes = 60,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): bool {
        return setcookie($name, $value, [
            'expires' => time() + ($minutes * 60),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);
    }

    public static function forget(string $name): bool
    {
        unset($_COOKIE[$name]);
        return setcookie($name, '', ['expires' => time() - 3600, 'path' => '/']);
    }
}

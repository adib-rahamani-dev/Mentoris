<?php

declare(strict_types=1);

use App\Core\CSRF;
use App\Core\Security;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        return rtrim($base, DIRECTORY_SEPARATOR) . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}

if (!function_exists('view_path')) {
    function view_path(string $path = ''): string
    {
        return base_path('app/Views' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Security::escape($value);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return (new CSRF())->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return (new CSRF())->field();
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

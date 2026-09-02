<?php

declare(strict_types=1);

use App\Core\CSRF;
use App\Core\Security;
use App\Core\Translator;

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

if (!function_exists('t')) {
    function t(string $key, array $replace = []): string
    {
        return Translator::get($key, $replace);
    }
}

if (!function_exists('locale')) {
    function locale(): string
    {
        return Translator::locale();
    }
}

if (!function_exists('locale_direction')) {
    function locale_direction(): string
    {
        return Translator::direction();
    }
}

if (!function_exists('locale_html')) {
    function locale_html(): string
    {
        return Translator::htmlLocale();
    }
}

if (!function_exists('locale_url')) {
    function locale_url(string $locale): string
    {
        return Translator::switchUrl($locale);
    }
}

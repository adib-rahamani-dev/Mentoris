<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/constants.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void { if (!$condition) throw new RuntimeException("FAILED: {$message}"); $passed++; };
$variables = file_get_contents(BASE_PATH . '/public/assets/css/variables.css');
$themeCss = file_get_contents(BASE_PATH . '/public/assets/css/theme.css');
$themeJs = file_get_contents(BASE_PATH . '/public/assets/js/components/theme.js');
$layout = file_get_contents(BASE_PATH . '/app/Views/layouts/main.php');
$navbar = file_get_contents(BASE_PATH . '/app/Views/components/navbar.php');

$assert(str_contains($variables, '[data-theme="light"]'), 'Light theme token scope exists.');
$assert(str_contains($variables, 'color-scheme: light'), 'Light theme declares browser color scheme.');
$assert(str_contains($variables, 'color-scheme: dark'), 'Dark theme remains available.');
$assert(str_contains($themeJs, "localStorage.setItem(Theme.key"), 'Theme preference persists locally.');
$assert(str_contains($themeJs, "meta[name=\"theme-color\"]"), 'Theme updates browser chrome color.');
$assert(str_contains($layout, "prefers-color-scheme: light"), 'Initial theme respects operating-system preference.');
$assert(str_contains($navbar, 'data-theme-toggle'), 'Navbar exposes an accessible theme control.');
$assert(str_contains($themeCss, '.theme-toggle'), 'Theme toggle has component styles.');

echo "Theme: {$passed} assertions passed." . PHP_EOL;

<?php

declare(strict_types=1);

use App\Core\Application;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/autoload.php';

$environmentFile = BASE_PATH . '/.env';
if (is_file($environmentFile)) {
    foreach (file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            $_ENV[$key] = $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

$app = new Application(BASE_PATH, (bool) env('APP_DEBUG', false));
$router = $app->router();

foreach (['web', 'auth', 'payment', 'admin', 'api'] as $routeFile) {
    $path = BASE_PATH . '/routes/' . $routeFile . '.php';
    if (is_file($path)) {
        require $path;
    }
}

return $app;

<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Core\Router;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index'], ['rate:120,60']);

$router->get('/framework/{name:[A-Za-z0-9_-]+}', static function (\App\Core\Request $request, string $name): array {
    return [
        'framework' => 'Mentoris',
        'hello' => $name,
        'pipeline' => ['Router', 'Middleware', 'Controller', 'View', 'Response'],
    ];
}, ['rate:60,60']);

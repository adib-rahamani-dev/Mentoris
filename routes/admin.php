<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Core\Router;

/** @var Router $router */
$router->get('/admin', [AdminController::class, 'dashboard'], ['auth', 'admin', 'rate:120,60']);
$router->get('/admin/users', [AdminController::class, 'users'], ['auth', 'can:users.view', 'rate:120,60']);
$router->post('/admin/users/{id:[a-f0-9]+}/access', [AdminController::class, 'updateUserAccess'], ['auth', 'can:users.manage', 'csrf', 'rate:30,60']);
$router->get('/admin/content', [AdminController::class, 'content'], ['auth', 'can:content.view', 'rate:120,60']);
$router->get('/admin/analytics', [AdminController::class, 'analytics'], ['auth', 'can:analytics.view', 'rate:120,60']);

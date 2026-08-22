<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\UserAreaController;
use App\Core\Router;

/** @var Router $router */
$router->get('/register', [AuthController::class, 'registerForm'], ['guest', 'rate:60,60']);
$router->post('/register', [AuthController::class, 'register'], ['guest', 'csrf', 'rate:3,60']);
$router->get('/login', [AuthController::class, 'loginForm'], ['guest', 'rate:60,60']);
$router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf', 'rate:5,60']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf', 'rate:5,60']);
$router->get('/forgot-password', [AuthController::class, 'forgotForm'], ['guest', 'rate:30,60']);
$router->post('/forgot-password', [AuthController::class, 'forgot'], ['guest', 'csrf', 'rate:3,60']);
$router->get('/reset-password/{token:[a-f0-9]+}', [AuthController::class, 'resetForm'], ['guest', 'rate:30,60']);
$router->post('/reset-password/{token:[a-f0-9]+}', [AuthController::class, 'reset'], ['guest', 'csrf', 'rate:5,60']);

$router->get('/dashboard', [UserAreaController::class, 'dashboard'], ['auth', 'rate:120,60']);
$router->get('/profile', [UserAreaController::class, 'profile'], ['auth', 'rate:120,60']);
$router->post('/profile', [UserAreaController::class, 'updateProfile'], ['auth', 'csrf', 'rate:10,60']);
$router->get('/my-courses', [UserAreaController::class, 'courses'], ['auth', 'rate:120,60']);
$router->get('/my-events', [UserAreaController::class, 'events'], ['auth', 'rate:120,60']);
$router->get('/my-certificates', [UserAreaController::class, 'certificates'], ['auth', 'rate:120,60']);
$router->get('/notifications', [UserAreaController::class, 'notifications'], ['auth', 'rate:120,60']);
$router->post('/notifications/read-all', [UserAreaController::class, 'readNotifications'], ['auth', 'csrf', 'rate:10,60']);

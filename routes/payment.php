<?php

declare(strict_types=1);

use App\Controllers\PaymentController;
use App\Core\Router;

/** @var Router $router */
$router->get('/checkout/course/{slug:[a-z0-9-]+}', [PaymentController::class, 'checkout'], ['auth', 'rate:60,60']);
$router->post('/checkout/course/{slug:[a-z0-9-]+}', [PaymentController::class, 'pay'], ['auth', 'csrf', 'rate:5,60']);
$router->get('/payment/callback', [PaymentController::class, 'callback'], ['rate:30,60']);
$router->get('/payment/sandbox/{authority:[A-Za-z0-9_]+}', [PaymentController::class, 'sandbox'], ['rate:30,60']);
$router->post('/payment/sandbox/{authority:[A-Za-z0-9_]+}', [PaymentController::class, 'sandboxSubmit'], ['csrf', 'rate:10,60']);
$router->get('/payment/result', [PaymentController::class, 'result'], ['auth', 'rate:60,60']);
$router->get('/orders', [PaymentController::class, 'orders'], ['auth', 'rate:120,60']);
$router->get('/orders/{id:[a-f0-9]+}', [PaymentController::class, 'order'], ['auth', 'rate:120,60']);

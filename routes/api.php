<?php

declare(strict_types=1);

use App\Core\Response;
use App\Core\Router;

/** @var Router $router */
$router->get('/api/health', static fn (): Response => Response::json([
    'status' => 'ok',
    'framework' => 'Mentoris',
]), ['rate:30,60']);

<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AdminQrController;
use App\Core\Router;

/** @var Router $router */
$router->get('/admin', [AdminController::class, 'dashboard'], ['auth', 'admin', 'rate:120,60']);
$router->get('/admin/users', [AdminController::class, 'users'], ['auth', 'can:users.view', 'rate:120,60']);
$router->get('/admin/users/new', [AdminController::class, 'createUser'], ['auth', 'can:users.manage', 'rate:60,60']);
$router->post('/admin/users', [AdminController::class, 'storeUser'], ['auth', 'can:users.manage', 'csrf', 'rate:15,60']);
$router->get('/admin/users/{id:[a-f0-9]+}', [AdminController::class, 'user'], ['auth', 'can:users.view', 'rate:120,60']);
$router->post('/admin/users/{id:[a-f0-9]+}/member-profile', [\App\Controllers\MemberProfileController::class, 'adminSave'], ['auth', 'can:users.manage', 'csrf', 'rate:30,60']);
$router->post('/admin/users/{id:[a-f0-9]+}/access', [AdminController::class, 'updateUserAccess'], ['auth', 'can:users.manage', 'csrf', 'rate:30,60']);
$router->post('/admin/users/{id:[a-f0-9]+}/notify', [AdminController::class, 'notifyUser'], ['auth', 'can:notifications.manage', 'csrf', 'rate:30,60']);
$router->post('/admin/users/{id:[a-f0-9]+}/profile', [AdminController::class, 'updateUserProfile'], ['auth', 'can:users.manage', 'csrf', 'rate:30,60']);
$router->post('/admin/users/{id:[a-f0-9]+}/password', [AdminController::class, 'updateUserPassword'], ['auth', 'can:users.manage', 'csrf', 'rate:10,60']);
$router->get('/admin/orders', [AdminController::class, 'orders'], ['auth', 'can:orders.view', 'rate:120,60']);
$router->get('/admin/orders/{id:[a-f0-9]+}', [AdminController::class, 'order'], ['auth', 'can:orders.view', 'rate:120,60']);
$router->get('/admin/engagements', [AdminController::class, 'engagements'], ['auth', 'can:engagements.view', 'rate:120,60']);
$router->post('/admin/engagements/{type:[a-z]+}/{id:[a-f0-9]+}', [AdminController::class, 'updateEngagement'], ['auth', 'can:engagements.manage', 'csrf', 'rate:60,60']);
$router->get('/admin/content', [AdminController::class, 'content'], ['auth', 'can:content.view', 'rate:120,60']);
$router->get('/admin/qr', [AdminQrController::class, 'index'], ['auth', 'can:content.view', 'rate:120,60']);
$router->get('/admin/feedback', [\App\Controllers\AdminFeedbackController::class, 'index'], ['auth', 'can:engagements.view', 'rate:120,60']);
$router->get('/admin/telegram', [\App\Controllers\AdminTelegramController::class, 'index'], ['auth', 'can:engagements.view', 'rate:120,60']);
$router->post('/admin/telegram/questions/{id:[a-f0-9]+}/answer', [\App\Controllers\AdminTelegramController::class, 'answer'], ['auth', 'can:engagements.manage', 'csrf', 'rate:30,60']);
$router->post('/admin/telegram/requests/{id:[a-f0-9]+}', [\App\Controllers\AdminTelegramController::class, 'updateRequest'], ['auth', 'can:engagements.manage', 'csrf', 'rate:30,60']);
$router->post('/admin/telegram/retry', [\App\Controllers\AdminTelegramController::class, 'retry'], ['auth', 'can:engagements.manage', 'csrf', 'rate:5,60']);
$router->get('/admin/content/new', [AdminController::class, 'createContent'], ['auth', 'can:content.manage', 'rate:120,60']);
$router->post('/admin/content', [AdminController::class, 'storeContent'], ['auth', 'can:content.manage', 'csrf', 'rate:30,60']);
$router->get('/admin/content/{id:[a-f0-9]+}/edit', [AdminController::class, 'editContent'], ['auth', 'can:content.manage', 'rate:120,60']);
$router->post('/admin/content/{id:[a-f0-9]+}', [AdminController::class, 'updateContent'], ['auth', 'can:content.manage', 'csrf', 'rate:30,60']);
$router->post('/admin/content/{id:[a-f0-9]+}/status', [AdminController::class, 'updateContentStatus'], ['auth', 'can:content.manage', 'csrf', 'rate:30,60']);
$router->get('/admin/analytics', [AdminController::class, 'analytics'], ['auth', 'can:analytics.view', 'rate:120,60']);
$router->get('/admin/audit', [AdminController::class, 'audit'], ['auth', 'can:audit.view', 'rate:120,60']);
$router->get('/admin/system', [AdminController::class, 'system'], ['auth', 'can:system.view', 'rate:120,60']);

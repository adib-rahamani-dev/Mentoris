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
$router->get('/admin/requests/{type:circle|courses}/{id:[a-f0-9]+}', [\App\Controllers\AdminRequestReviewController::class,'show'], ['auth','can:engagements.view','rate:90,60']);
$router->post('/admin/requests/{type:circle|courses}/{id:[a-f0-9]+}', [\App\Controllers\AdminRequestReviewController::class,'review'], ['auth','can:engagements.manage','csrf','rate:20,60']);
$router->post('/admin/engagements/{type:[a-z]+}/{id:[a-f0-9]+}', [AdminController::class, 'updateEngagement'], ['auth', 'can:engagements.manage', 'csrf', 'rate:60,60']);
$router->get('/admin/content', [AdminController::class, 'content'], ['auth', 'can:content.view', 'rate:120,60']);
$router->get('/admin/qr', [AdminQrController::class, 'index'], ['auth', 'can:content.view', 'rate:120,60']);
$router->get('/admin/feedback', [\App\Controllers\AdminFeedbackController::class, 'index'], ['auth', 'can:engagements.view', 'rate:120,60']);
$router->post('/admin/feedback/{slug:[a-z-]+}/schedule', [\App\Controllers\AdminFeedbackController::class, 'schedule'], ['auth', 'can:engagements.manage', 'csrf', 'rate:20,60']);
$router->get('/admin/telegram', [\App\Controllers\AdminTelegramController::class, 'index'], ['auth', 'can:engagements.view', 'rate:120,60']);
$router->get('/admin/communications', [\App\Controllers\CommunicationController::class, 'admin'], ['auth','can:engagements.view','rate:120,60']);
$router->get('/admin/manual-payments', [\App\Controllers\ManualPaymentController::class,'admin'], ['auth','can:payments.manage','rate:60,60']);
$router->get('/admin/manual-payments/{id:[a-f0-9]+}', [\App\Controllers\ManualPaymentController::class,'detail'], ['auth','can:payments.manage','rate:90,60']);
$router->get('/admin/discounts', [\App\Controllers\DiscountCodeController::class,'index'], ['auth','can:payments.manage','rate:60,60']);
$router->post('/admin/discounts', [\App\Controllers\DiscountCodeController::class,'create'], ['auth','can:payments.manage','csrf','rate:10,60']);
$router->post('/admin/discounts/{id:[a-f0-9]+}/status', [\App\Controllers\DiscountCodeController::class,'status'], ['auth','can:payments.manage','csrf','rate:20,60']);
$router->post('/admin/manual-payments/{id:[a-f0-9]+}', [\App\Controllers\ManualPaymentController::class,'review'], ['auth','can:payments.manage','csrf','rate:10,60']);
$router->post('/admin/manual-payments/{id:[a-f0-9]+}/refund', [\App\Controllers\ManualPaymentController::class,'refund'], ['auth','can:payments.manage','csrf','rate:5,60']);
$router->get('/admin/circle-applications/{id}/resume', [\App\Controllers\EventsController::class, 'resume'], ['auth','can:engagements.view','rate:30,60']);
$router->post('/admin/communications/retry', [\App\Controllers\CommunicationController::class, 'retry'], ['auth','can:engagements.manage','csrf','rate:3,60']);
$router->get('/admin/support', [\App\Controllers\SupportController::class, 'adminIndex'], ['auth','can:engagements.view','rate:120,60']);
$router->get('/admin/support/{id:[a-f0-9]+}', [\App\Controllers\SupportController::class, 'adminShow'], ['auth','can:engagements.view','rate:90,60']);
$router->post('/admin/support/{id:[a-f0-9]+}/reply', [\App\Controllers\SupportController::class, 'adminReply'], ['auth','can:engagements.manage','csrf','rate:10,60']);
$router->post('/admin/support/{id:[a-f0-9]+}/close', [\App\Controllers\SupportController::class, 'close'], ['auth','can:engagements.manage','csrf','rate:10,60']);
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
$router->get('/admin/sms', [\App\Controllers\AdminSmsController::class, 'index'], ['auth', 'can:system.view', 'rate:120,60']);
$router->get('/admin/seo', [AdminController::class, 'seo'], ['auth', 'can:content.view', 'rate:120,60']);
// Separate URLs, fixed entity types and dedicated field sets for each workspace.
$router->get('/admin/{module:articles|events|courses|programs|mentors|academy-lines|specializations}', [AdminController::class, 'workspace'], ['auth', 'can:content.view', 'rate:120,60']);
$router->get('/admin/{module:articles|events|courses|programs|mentors|academy-lines|specializations}/new', [AdminController::class, 'workspaceNew'], ['auth', 'can:content.manage', 'rate:120,60']);
$router->post('/admin/{module:articles|events|courses|programs|mentors|academy-lines|specializations}', [AdminController::class, 'workspaceStore'], ['auth', 'can:content.manage', 'csrf', 'rate:30,60']);
$router->get('/admin/{module:articles|events|courses|programs|mentors|academy-lines|specializations}/{id:[a-f0-9]+}/edit', [AdminController::class, 'workspaceEdit'], ['auth', 'can:content.manage', 'rate:120,60']);
$router->post('/admin/{module:articles|events|courses|programs|mentors|academy-lines|specializations}/{id:[a-f0-9]+}', [AdminController::class, 'workspaceUpdate'], ['auth', 'can:content.manage', 'csrf', 'rate:30,60']);

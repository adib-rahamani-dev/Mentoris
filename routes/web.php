<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\AboutController;
use App\Controllers\ContactController;
use App\Controllers\FounderController;
use App\Controllers\MentorsController;
use App\Controllers\ProgramsController;
use App\Controllers\AcademyController;
use App\Controllers\SpecializationsController;
use App\Controllers\EventsController;
use App\Controllers\CommunityController;
use App\Controllers\CoursesController;
use App\Core\Router;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index'], ['rate:120,60']);
$router->get('/about', [AboutController::class, 'index'], ['rate:120,60']);
$router->get('/founder', [FounderController::class, 'index'], ['rate:120,60']);
$router->get('/programs', [ProgramsController::class, 'index'], ['rate:120,60']);
$router->get('/programs/{slug:[a-z0-9-]+}', [ProgramsController::class, 'show'], ['rate:120,60']);
$router->get('/academy', [AcademyController::class, 'index'], ['rate:120,60']);
$router->get('/academy/{slug:[a-z0-9-]+}', [AcademyController::class, 'show'], ['rate:120,60']);
$router->get('/specializations', [SpecializationsController::class, 'index'], ['rate:120,60']);
$router->get('/specializations/{slug:[a-z0-9-]+}', [SpecializationsController::class, 'show'], ['rate:120,60']);
$router->get('/courses', [CoursesController::class, 'index'], ['rate:120,60']);
$router->get('/courses/{slug:[a-z0-9-]+}', [CoursesController::class, 'show'], ['rate:120,60']);
$router->get('/events', [EventsController::class, 'index'], ['rate:120,60']);
$router->get('/events/{slug:[a-z0-9-]+}', [EventsController::class, 'show'], ['rate:120,60']);
$router->post('/events/{slug:[a-z0-9-]+}/register', [EventsController::class, 'register'], ['csrf', 'rate:5,60']);
$router->get('/community', [CommunityController::class, 'index'], ['rate:120,60']);
$router->post('/community/join', [CommunityController::class, 'join'], ['csrf', 'rate:3,60']);
$router->get('/mentors', [MentorsController::class, 'index'], ['rate:120,60']);
$router->get('/contact', [ContactController::class, 'index'], ['rate:60,60']);
$router->post('/contact', [ContactController::class, 'store'], ['csrf', 'rate:5,60']);
$router->get('/design-system', [HomeController::class, 'designSystem'], ['rate:60,60']);

$router->get('/framework/{name:[A-Za-z0-9_-]+}', static function (\App\Core\Request $request, string $name): array {
    return [
        'framework' => 'Mentoris',
        'hello' => $name,
        'pipeline' => ['Router', 'Middleware', 'Controller', 'View', 'Response'],
    ];
}, ['rate:60,60']);

<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Security;
use App\Core\Validator;

require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void {
    if (!$condition) {
        throw new RuntimeException("FAILED: {$message}");
    }
    $passed++;
};

$request = static fn (string $method, string $uri, array $body = [], array $headers = []): Request => new Request(
    [],
    $body,
    [],
    [],
    ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri, ...$headers]
);

$events = [];
$trace = static function (Request $request, callable $next) use (&$events): Response {
    $events[] = 'before';
    $response = $next($request);
    $events[] = 'after';
    return $response->withHeader('X-Test-Middleware', 'passed');
};

$router = new Router();
$router->get('/users/{id:\\d+}', static function (Request $request, string $id) use (&$events): array {
    $events[] = 'controller';
    return ['id' => (int) $id, 'route_id' => $request->route('id')];
}, [$trace]);

$response = $router->dispatch($request('GET', '/users/42'));
$payload = json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR);
$assert($response->status() === 200, 'Router returns status 200.');
$assert($payload === ['id' => 42, 'route_id' => '42'], 'Route parameters reach the controller.');
$assert($events === ['before', 'controller', 'after'], 'Middleware wraps the controller in order.');
$assert(($response->headers()['X-Test-Middleware'] ?? null) === 'passed', 'Middleware can alter the response.');
$assert($router->dispatch($request('POST', '/users/42'))->status() === 405, 'Router detects method not allowed.');
$assert($router->dispatch($request('GET', '/missing'))->status() === 404, 'Router detects missing routes.');

$validator = new Validator();
$assert($validator->validate(['email' => 'user@example.com', 'age' => 18], [
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
]), 'Validator accepts valid input.');
$assert(!$validator->validate(['email' => 'invalid'], ['email' => 'required|email']), 'Validator rejects invalid input.');
$assert(Security::escape('<script>') === '&lt;script&gt;', 'HTML escaping is active.');

/** @var App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$home = $app->handle($request('GET', '/'));
$assert($home->status() === 200 && str_contains($home->content(), 'دکتر مریم حقانی') && str_contains($home->content(), 'Mentoris Academy'), 'Controller and View render approved launch content through Application.');
$health = $app->handle($request('GET', '/api/health', [], ['HTTP_ACCEPT' => 'application/json']));
$assert($health->status() === 200 && str_contains($health->content(), '"status":"ok"'), 'API route returns JSON response.');

echo "Core Engine: {$passed} assertions passed." . PHP_EOL;

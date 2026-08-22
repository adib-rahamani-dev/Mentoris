<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RateLimitMiddleware;
use Throwable;

final class Application
{
    private Router $router;

    public function __construct(private readonly string $basePath, private readonly bool $debug = false)
    {
        $this->router = new Router();
        $this->router
            ->alias('auth', AuthMiddleware::class)
            ->alias('guest', GuestMiddleware::class)
            ->alias('csrf', CsrfMiddleware::class)
            ->alias('rate', RateLimitMiddleware::class);
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function basePath(string $path = ''): string
    {
        return rtrim($this->basePath, '/\\') . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            if ($request->expectsJson()) {
                return Response::json([
                    'message' => 'Internal Server Error',
                    ...($this->debug ? ['exception' => $exception->getMessage()] : []),
                ], 500);
            }

            $message = $this->debug ? Security::escape($exception->getMessage()) : 'خطایی در اجرای برنامه رخ داد.';
            return Response::html("<h1>500 - Internal Server Error</h1><p>{$message}</p>", 500);
        }
    }

    public function run(): void
    {
        $this->handle(Request::capture())->send();
    }
}

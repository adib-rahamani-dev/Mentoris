<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\PermissionMiddleware;
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
            ->alias('rate', RateLimitMiddleware::class)
            ->alias('admin', AdminMiddleware::class)
            ->alias('can', PermissionMiddleware::class);
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
            return $this->withSecurityHeaders($this->router->dispatch($request));
        } catch (Throwable $exception) {
            error_log((string) $exception);
            if ($request->expectsJson()) {
                return $this->withSecurityHeaders(Response::json([
                    'message' => 'Internal Server Error',
                    ...($this->debug ? ['exception' => $exception->getMessage()] : []),
                ], 500));
            }

            $message = $this->debug ? Security::escape($exception->getMessage()) : 'خطایی در اجرای برنامه رخ داد.';
            return $this->withSecurityHeaders(Response::html("<h1>500 - Internal Server Error</h1><p>{$message}</p>", 500));
        }
    }

    private function withSecurityHeaders(Response $response): Response
    {
        $nonce = Security::cspNonce();
        $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; frame-src 'none'; manifest-src 'self'";
        if (env('APP_ENV', 'production') === 'production') $csp .= '; upgrade-insecure-requests';
        $response = $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->withHeader('X-Permitted-Cross-Domain-Policies', 'none');

        if ($response->status() >= 400) $response = $response->withHeader('X-Robots-Tag', 'noindex, nofollow');

        $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https') {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    public function run(): void
    {
        $this->handle(Request::capture())->send();
    }
}

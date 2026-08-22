<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CSRF $csrf = new CSRF())
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        if (!$this->csrf->validate(is_string($token) ? $token : null)) {
            return $request->expectsJson()
                ? Response::json(['message' => 'CSRF token mismatch'], 419)
                : Response::html('<h1>419 - Page Expired</h1>', 419);
        }
        return $next($request);
    }
}

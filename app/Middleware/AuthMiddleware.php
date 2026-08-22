<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Session $session = new Session())
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->session->has('auth.user')) {
            if (!$request->expectsJson() && $request->isMethod('GET')) {
                $query = $request->query();
                $this->session->put('auth.intended', $request->uri() . ($query ? '?' . http_build_query($query) : ''));
            }
            return $request->expectsJson()
                ? Response::json(['message' => 'Unauthenticated'], 401)
                : Response::redirect('/login');
        }
        return $next($request);
    }
}

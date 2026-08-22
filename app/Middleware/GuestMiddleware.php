<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Session $session = new Session())
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        return $this->session->has('auth.user') ? Response::redirect('/dashboard') : $next($request);
    }
}

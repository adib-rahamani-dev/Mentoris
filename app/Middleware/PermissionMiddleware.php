<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Authorization;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $permission = 'admin.access') {}

    public function handle(Request $request, callable $next): Response
    {
        $user = (new AuthService())->user();
        if ($user === null) return $request->expectsJson() ? Response::json(['message' => 'Unauthenticated'], 401) : Response::redirect('/login');
        if (!Authorization::can($user, $this->permission)) {
            return $request->expectsJson() ? Response::json(['message' => 'Forbidden'], 403) : Response::html('<h1>403 - دسترسی مجاز نیست</h1>', 403);
        }
        return $next($request);
    }
}

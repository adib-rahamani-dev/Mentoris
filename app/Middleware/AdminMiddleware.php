<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Authorization;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $user = (new AuthService())->user();
        if ($user === null) return Response::redirect('/login');
        return Authorization::can($user, 'admin.access') ? $next($request) : Response::html('<h1>403 - دسترسی مجاز نیست</h1>', 403);
    }
}

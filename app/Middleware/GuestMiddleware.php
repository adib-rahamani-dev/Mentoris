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
        if($this->session->has('auth.user') && ($user=(new \App\Services\AuthService())->user())){
            if($request->expectsJson() && $request->isMethod('POST') && $request->input('recover')==='1')return $next($request);
            \App\Services\EnrollmentReturnService::remember($request->input('next'));
            return $request->expectsJson()?Response::json(['message'=>'شما وارد حساب هستید.','user_id'=>$user['id']]):Response::redirect(\App\Services\EnrollmentReturnService::afterLogin($user)??'/dashboard');
        }
        return $next($request);
    }
}

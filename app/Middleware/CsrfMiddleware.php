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

        $origin = trim((string) $request->header('Origin', ''));
        $localOrigin=(string)env('APP_ENV','production')==='local' && in_array((string)parse_url($origin,PHP_URL_HOST),['localhost','127.0.0.1','[::1]'],true) && $this->sameOrigin($origin,'http://'.$request->header('Host',''));
        if ($origin !== '' && !$localOrigin && !$this->sameOrigin($origin, (string) env('APP_URL', ''))) {
            return $this->rejected($request);
        }

        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        if (!$this->csrf->validate(is_string($token) ? $token : null)) {
            return $this->rejected($request);
        }
        return $next($request);
    }

    private function sameOrigin(string $origin, string $applicationUrl): bool
    {
        $left = parse_url($origin);
        $right = parse_url($applicationUrl);
        if (!is_array($left) || !is_array($right)) return false;
        $port = static fn (array $url): int => (int) ($url['port'] ?? (($url['scheme'] ?? '') === 'https' ? 443 : 80));
        return strtolower((string) ($left['scheme'] ?? '')) === strtolower((string) ($right['scheme'] ?? ''))
            && strtolower((string) ($left['host'] ?? '')) === strtolower((string) ($right['host'] ?? ''))
            && $port($left) === $port($right);
    }

    private function rejected(Request $request): Response
    {
        return $request->expectsJson()
            ? Response::json(['message' => 'اعتبار فرم تازه شود و دوباره ذخیره کنید.'], 419)
            : Response::html('<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>نشست منقضی شد</title><body><main><h1>نشست منقضی شد</h1><p>صفحه را تازه کنید و دوباره تلاش کنید.</p></main></body></html>', 419);
    }
}

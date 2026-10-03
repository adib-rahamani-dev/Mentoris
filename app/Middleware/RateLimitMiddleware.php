<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\RateLimiter;
use App\Core\FileRateLimiter;
use App\Core\Response;
use App\Core\Session;
use Throwable;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly int $maxAttempts = 60,
        private readonly int $decaySeconds = 60,
        private readonly Session $session = new Session()
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        // Route parameters and query strings must not create fresh buckets.
        $path = $request->routePattern();
        $identity = $request->ip() . '|' . $request->method() . '|' . $path;
        try {
                $limiter = env('RATE_LIMIT_DRIVER', 'files') === 'database' ? new RateLimiter() : new FileRateLimiter();
                $bucket = $limiter->hit($identity, $this->maxAttempts, $this->decaySeconds);
        } catch (Throwable) {
            error_log('Request rate limiter unavailable; request refused.');
            return ($request->expectsJson() ? Response::json(['message'=>'سرویس موقتاً در دسترس نیست. کمی بعد دوباره تلاش کنید.'],503) : Response::html('سرویس موقتاً در دسترس نیست. کمی بعد دوباره تلاش کنید.',503))->withHeader('Retry-After','60');
        }
        if (!$bucket['allowed']) return $this->blocked($request, (int) $bucket['retry_after']);
        return $next($request)->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $bucket['remaining'])->withHeader('X-RateLimit-Reset', (string) $bucket['reset']);
    }

    private function blocked(Request $request, int $retryAfter): Response
    {
        $response = $request->expectsJson()
            ? Response::json(['message' => 'Too Many Requests'], 429)
            : Response::html('<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>درخواست بیش از حد</title><body><main><h1>کمی صبر کنید</h1><p>تعداد درخواست‌ها بیش از حد مجاز است. چند لحظه دیگر دوباره تلاش کنید.</p></main></body></html>', 429);
        return $response->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', '0');
    }
}

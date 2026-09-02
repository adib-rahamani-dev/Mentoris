<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\RateLimiter;
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
        $identity = $request->ip() . '|' . $request->method() . '|' . $request->uri();
        if (env('RATE_LIMIT_DRIVER', 'session') === 'database') {
            try {
                $bucket = (new RateLimiter())->hit($identity, $this->maxAttempts, $this->decaySeconds);
                if (!$bucket['allowed']) return $this->blocked($request, (int) $bucket['retry_after']);
                return $next($request)
                    ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
                    ->withHeader('X-RateLimit-Remaining', (string) $bucket['remaining'])
                    ->withHeader('X-RateLimit-Reset', (string) $bucket['reset']);
            } catch (Throwable $exception) {
                if (env('APP_ENV', 'production') === 'production') throw $exception;
                error_log('Database rate limiter fallback: ' . $exception->getMessage());
            }
        }

        $key = '_rate_limit.' . hash('sha256', $identity);
        $now = time();
        $bucket = $this->session->get($key, ['hits' => 0, 'reset' => $now + $this->decaySeconds]);

        if (!is_array($bucket) || ($bucket['reset'] ?? 0) <= $now) {
            $bucket = ['hits' => 0, 'reset' => $now + $this->decaySeconds];
        }
        $bucket['hits']++;
        $this->session->put($key, $bucket);

        $remaining = max(0, $this->maxAttempts - $bucket['hits']);
        if ($bucket['hits'] > $this->maxAttempts) {
            return $this->blocked($request, max(1, $bucket['reset'] - $now));
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
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

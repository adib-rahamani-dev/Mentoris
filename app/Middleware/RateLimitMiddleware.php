<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

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
        $key = '_rate_limit.' . hash('sha256', $request->ip() . '|' . $request->method() . '|' . $request->uri());
        $now = time();
        $bucket = $this->session->get($key, ['hits' => 0, 'reset' => $now + $this->decaySeconds]);

        if (!is_array($bucket) || ($bucket['reset'] ?? 0) <= $now) {
            $bucket = ['hits' => 0, 'reset' => $now + $this->decaySeconds];
        }
        $bucket['hits']++;
        $this->session->put($key, $bucket);

        $remaining = max(0, $this->maxAttempts - $bucket['hits']);
        if ($bucket['hits'] > $this->maxAttempts) {
            return Response::json(['message' => 'Too Many Requests'], 429)
                ->withHeader('Retry-After', (string) max(1, $bucket['reset'] - $now));
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }
}

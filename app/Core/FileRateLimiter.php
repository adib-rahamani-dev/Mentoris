<?php
declare(strict_types=1);
namespace App\Core;

use RuntimeException;

/** Cookie-independent limits for single-host deployments, with atomic file locks. */
final class FileRateLimiter
{
    public function __construct(private readonly ?string $directory = null) {}

    public function hit(string $key, int $maxAttempts, int $decaySeconds): array
    {
        $directory = $this->directory ?? STORAGE_PATH . '/data/rate-limits';
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Rate limiter unavailable.');
        $handle = @fopen($directory . '/' . Crypto::keyedHash($key) . '.json', 'c+');
        if (!$handle) throw new RuntimeException('Rate limiter unavailable.');
        try {
            if (!flock($handle, LOCK_EX)) throw new RuntimeException('Rate limiter unavailable.');
            $bucket = json_decode((string) stream_get_contents($handle, 2048), true);
            $now = time();
            $reset = is_array($bucket) && (int) ($bucket['reset'] ?? 0) > $now ? (int) $bucket['reset'] : $now + max(1, $decaySeconds);
            $hits = is_array($bucket) && (int) ($bucket['reset'] ?? 0) > $now ? min($maxAttempts + 1, (int) ($bucket['hits'] ?? 0) + 1) : 1;
            $payload = json_encode(['hits'=>$hits, 'reset'=>$reset], JSON_THROW_ON_ERROR);
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $payload) !== strlen($payload) || !fflush($handle)) throw new RuntimeException('Rate limiter unavailable.');
            return ['allowed'=>$hits <= $maxAttempts, 'remaining'=>max(0,$maxAttempts-$hits), 'reset'=>$reset, 'retry_after'=>max(1,$reset-$now)];
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }
}

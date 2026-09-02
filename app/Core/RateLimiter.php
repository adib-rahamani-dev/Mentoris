<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class RateLimiter
{
    public function __construct(private readonly ?PDO $database = null) {}

    public function hit(string $key, int $maxAttempts, int $decaySeconds): array
    {
        return Database::transaction($this->pdo(), function (PDO $pdo) use ($key, $maxAttempts, $decaySeconds): array {
            $hash = Crypto::keyedHash($key);
            $now = Database::now();
            $suffix = Database::driver($pdo) === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $pdo->prepare('SELECT hits,reset_at FROM rate_limits WHERE key_hash=:key LIMIT 1' . $suffix);
            $statement->execute(['key' => $hash]);
            $bucket = $statement->fetch();
            $resetTimestamp = is_array($bucket) ? strtotime((string) $bucket['reset_at'] . ' UTC') : 0;
            $hits = $resetTimestamp > time() ? (int) $bucket['hits'] + 1 : 1;
            if ($resetTimestamp <= time()) $resetTimestamp = time() + max(1, $decaySeconds);
            $values = ['key' => $hash, 'hits' => $hits, 'reset' => gmdate('Y-m-d H:i:s', $resetTimestamp), 'now' => $now];
            $sql = Database::driver($pdo) === 'mysql'
                ? 'INSERT INTO rate_limits (key_hash,hits,reset_at,updated_at) VALUES (:key,:hits,:reset,:now) ON DUPLICATE KEY UPDATE hits=VALUES(hits),reset_at=VALUES(reset_at),updated_at=VALUES(updated_at)'
                : 'INSERT INTO rate_limits (key_hash,hits,reset_at,updated_at) VALUES (:key,:hits,:reset,:now) ON CONFLICT(key_hash) DO UPDATE SET hits=excluded.hits,reset_at=excluded.reset_at,updated_at=excluded.updated_at';
            $pdo->prepare($sql)->execute($values);
            return ['allowed' => $hits <= max(1, $maxAttempts), 'hits' => $hits, 'remaining' => max(0, $maxAttempts - $hits), 'reset' => $resetTimestamp, 'retry_after' => max(1, $resetTimestamp - time())];
        });
    }

    public function clear(string $key): void
    {
        $this->pdo()->prepare('DELETE FROM rate_limits WHERE key_hash=:key')->execute(['key' => Crypto::keyedHash($key)]);
    }

    private function pdo(): PDO { return $this->database ?? Database::connection(); }
}

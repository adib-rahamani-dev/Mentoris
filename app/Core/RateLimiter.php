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
            // Create before locking so concurrent first requests cannot overwrite each other.
            $thisInsert = $pdo->prepare((Database::driver($pdo)==='mysql' ? 'INSERT IGNORE' : 'INSERT OR IGNORE').' INTO rate_limits (key_hash,hits,reset_at,updated_at) VALUES (:key,0,:reset,:now)');
            $thisInsert->execute(['key'=>$hash,'reset'=>gmdate('Y-m-d H:i:s',time()+max(1,$decaySeconds)),'now'=>$now]);
            $suffix = Database::driver($pdo) === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $pdo->prepare('SELECT hits,reset_at FROM rate_limits WHERE key_hash=:key LIMIT 1' . $suffix);
            $statement->execute(['key' => $hash]);
            $bucket = $statement->fetch();
            $resetTimestamp = is_array($bucket) ? strtotime((string) $bucket['reset_at'] . ' UTC') : 0;
            $hits = $resetTimestamp > time() ? min(max(1,$maxAttempts)+1,(int) $bucket['hits'] + 1) : 1;
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

    /** Reserve all budgets together. Callback is DB-only and may be retried. */
    public function reserveMany(array $limits, callable $reserved): array
    {
        return Database::transaction($this->pdo(), function (PDO $pdo) use ($limits, $reserved): array {
            $buckets=[]; $now=time(); $driver=Database::driver($pdo);
            foreach ($limits as $limit) $buckets[Crypto::keyedHash($limit['key'])]=$limit;
            ksort($buckets, SORT_STRING);
            $insert=$pdo->prepare(($driver==='mysql' ? 'INSERT IGNORE' : 'INSERT OR IGNORE').' INTO rate_limits (key_hash,hits,reset_at,updated_at) VALUES (:key,0,:reset,:updated)');
            $select=$pdo->prepare('SELECT hits,reset_at FROM rate_limits WHERE key_hash=:key'.($driver==='mysql' ? ' FOR UPDATE' : ''));
            $states=[]; $retry=0;
            foreach ($buckets as $hash=>$limit) {
                $insert->execute(['key'=>$hash,'reset'=>gmdate('Y-m-d H:i:s',$now+$limit['seconds']),'updated'=>Database::now()]);
                $select->execute(['key'=>$hash]); $row=$select->fetch();
                $reset=strtotime($row['reset_at'].' UTC'); $hits=(int)$row['hits'];
                if ($reset <= $now) { $reset=$now+$limit['seconds']; $hits=0; }
                if ($hits >= $limit['max']) $retry=max($retry,$reset-$now);
                $states[$hash]=['hits'=>$hits,'reset'=>$reset];
            }
            if ($retry>0) return ['allowed'=>false,'retry_after'=>$retry];
            $update=$pdo->prepare('UPDATE rate_limits SET hits=:hits,reset_at=:reset,updated_at=:updated WHERE key_hash=:key');
            foreach ($states as $hash=>$state) $update->execute(['key'=>$hash,'hits'=>$state['hits']+1,'reset'=>gmdate('Y-m-d H:i:s',$state['reset']),'updated'=>Database::now()]);
            $reserved($pdo);
            return ['allowed'=>true,'retry_after'=>0];
        });
    }

    private function pdo(): PDO { return $this->database ?? Database::connection(); }
}

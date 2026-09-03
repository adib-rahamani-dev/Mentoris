<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Audit
{
    public static function record(string $action, string $subjectType, ?string $subjectId, ?string $actorId, array $oldValues, array $newValues, string $ip): void
    {
        $statement = Database::connection()->prepare('INSERT INTO audit_logs (id,actor_id,action,subject_type,subject_id,old_values,new_values,ip_hash,created_at) VALUES (:id,:actor,:action,:type,:subject,:old,:new,:ip,:now)');
        $statement->execute([
            'id' => Security::randomToken(16), 'actor' => $actorId, 'action' => $action, 'type' => $subjectType, 'subject' => $subjectId,
            'old' => json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'new' => json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'ip' => Crypto::keyedHash($ip), 'now' => Database::now(),
        ]);
    }
}

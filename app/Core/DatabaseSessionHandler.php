<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use SessionHandlerInterface;

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function __construct(private readonly PDO $database, private readonly int $lifetimeSeconds) {}
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $statement = $this->database->prepare('SELECT payload FROM sessions WHERE id_hash=:id AND expires_at>:now LIMIT 1');
        $statement->execute(['id' => Crypto::keyedHash($id), 'now' => Database::now()]);
        $payload = $statement->fetchColumn();
        if (!is_string($payload)) return '';
        return Crypto::decrypt($payload) ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $values = ['id' => Crypto::keyedHash($id), 'payload' => Crypto::encrypt($data), 'now' => Database::now(), 'expires' => gmdate('Y-m-d H:i:s', time() + $this->lifetimeSeconds)];
        $sql = Database::driver($this->database) === 'mysql'
            ? 'INSERT INTO sessions (id_hash,payload,last_activity,expires_at) VALUES (:id,:payload,:now,:expires) ON DUPLICATE KEY UPDATE payload=VALUES(payload),last_activity=VALUES(last_activity),expires_at=VALUES(expires_at)'
            : 'INSERT INTO sessions (id_hash,payload,last_activity,expires_at) VALUES (:id,:payload,:now,:expires) ON CONFLICT(id_hash) DO UPDATE SET payload=excluded.payload,last_activity=excluded.last_activity,expires_at=excluded.expires_at';
        return $this->database->prepare($sql)->execute($values);
    }

    public function destroy(string $id): bool
    {
        return $this->database->prepare('DELETE FROM sessions WHERE id_hash=:id')->execute(['id' => Crypto::keyedHash($id)]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $statement = $this->database->prepare('DELETE FROM sessions WHERE expires_at<=:now');
        $statement->execute(['now' => Database::now()]);
        return $statement->rowCount();
    }
}

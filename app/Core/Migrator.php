<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Migrator
{
    public function __construct(
        private readonly PDO $database,
        private readonly string $migrationPath
    ) {
    }

    public function migrate(): array
    {
        $this->ensureRepository();
        $driver = Database::driver($this->database);
        $files = glob(rtrim($this->migrationPath, '/\\') . '/*.' . $driver . '.sql') ?: [];
        sort($files, SORT_STRING);
        $ran = $this->ran();
        $applied = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (isset($ran[$name])) {
                continue;
            }
            $sql = file_get_contents($file);
            if (!is_string($sql)) {
                throw new RuntimeException("Cannot read migration {$name}.");
            }
            $apply = function (PDO $pdo) use ($sql, $name): void {
                foreach ($this->statements($sql) as $statement) {
                    $pdo->exec($statement);
                }
                $insert = $pdo->prepare('INSERT INTO migrations (migration, executed_at) VALUES (:migration, :executed_at)');
                $insert->execute(['migration' => $name, 'executed_at' => Database::now()]);
            };
            if ($driver === 'mysql') {
                // MySQL implicitly commits DDL. Each CREATE statement is idempotent,
                // so a partially interrupted migration can safely be executed again.
                $apply($this->database);
            } else {
                Database::transaction($this->database, $apply, 1);
            }
            $applied[] = $name;
        }

        return $applied;
    }

    public function status(): array
    {
        $this->ensureRepository();
        $driver = Database::driver($this->database);
        $files = glob(rtrim($this->migrationPath, '/\\') . '/*.' . $driver . '.sql') ?: [];
        sort($files, SORT_STRING);
        $ran = $this->ran();
        return array_map(static fn (string $file): array => [
            'migration' => basename($file),
            'applied' => isset($ran[basename($file)]),
        ], $files);
    }

    private function ensureRepository(): void
    {
        $this->database->exec('CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(255) PRIMARY KEY, executed_at VARCHAR(32) NOT NULL)');
    }

    private function ran(): array
    {
        $rows = $this->database->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        return array_fill_keys(array_map('strval', $rows ?: []), true);
    }

    private function statements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        return array_values(array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [])));
    }
}

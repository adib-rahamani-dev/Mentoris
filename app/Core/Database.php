<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        return self::$connection ??= self::connect(self::environmentConfig());
    }

    public static function connect(array $config): PDO
    {
        $driver = strtolower((string) ($config['driver'] ?? 'mysql'));
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];

        if ($driver === 'sqlite') {
            $path = (string) ($config['database'] ?? ':memory:');
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException('Database driver must be mysql or sqlite.');
        }

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 3306);
        $database = (string) ($config['database'] ?? 'mentoris');
        $charset = (string) ($config['charset'] ?? 'utf8mb4');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException('Invalid database name.');
        }

        if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
        }
        $sslCa = trim((string) ($config['ssl_ca'] ?? ''));
        if ($sslCa !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        $pdo = new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), $options);
        $pdo->exec("SET SESSION time_zone = '+00:00'");
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        return $pdo;
    }

    public static function use(?PDO $connection): void
    {
        self::$connection = $connection;
    }

    public static function driver(PDO $connection): string
    {
        return (string) $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function transaction(PDO $connection, callable $callback, int $attempts = 3): mixed
    {
        $attempt = 0;
        retry:
        $attempt++;
        try {
            $connection->beginTransaction();
            $result = $callback($connection);
            $connection->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ($attempt < max(1, $attempts) && self::isRetryable($exception)) {
                usleep(random_int(10_000, 40_000) * $attempt);
                goto retry;
            }
            throw $exception;
        }
    }

    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public static function environmentConfig(): array
    {
        return [
            'driver' => (string) env('DB_CONNECTION', 'mysql'),
            'host' => (string) env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'database' => (string) env('DB_DATABASE', 'mentoris'),
            'username' => (string) env('DB_USERNAME', 'mentoris_app'),
            'password' => (string) env('DB_PASSWORD', ''),
            'charset' => (string) env('DB_CHARSET', 'utf8mb4'),
            'ssl_ca' => (string) env('DB_SSL_CA', ''),
        ];
    }

    public static function ping(?PDO $connection = null): bool
    {
        try {
            ($connection ?? self::connection())->query('SELECT 1')->fetchColumn();
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    private static function isRetryable(Throwable $exception): bool
    {
        if (!$exception instanceof PDOException) {
            return false;
        }
        $sqlState = (string) $exception->getCode();
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        return $sqlState === '40001' || in_array($driverCode, [1205, 1213], true);
    }
}

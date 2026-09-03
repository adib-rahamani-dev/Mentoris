<?php

declare(strict_types=1);

use App\Core\Crypto;
use App\Core\Database;
use App\Core\DatabaseSessionHandler;
use App\Core\Migrator;
use App\Core\RateLimiter;
use App\Repositories\UserRepository;

require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';
putenv('APP_KEY=base64:' . base64_encode(str_repeat('k', 32)));

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void { if (!$condition) throw new RuntimeException("FAILED: {$message}"); $passed++; };
$pdo = Database::connect(['driver' => 'sqlite', 'database' => ':memory:']);
$migrator = new Migrator($pdo, BASE_PATH . '/database/migrations');
$assert($migrator->migrate() === ['001_core.sqlite.sql'], 'Core schema migration applies once.');
$assert($migrator->migrate() === [], 'Migrations are idempotent.');
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['users','orders','payment_transactions','enrollments','sessions','rate_limits','audit_logs','content_entities'] as $table) $assert(in_array($table, $tables, true), "{$table} table exists.");

$users = new UserRepository($pdo);
$user = $users->create(['name' => 'Security Test', 'email' => 'safe@example.test', 'password' => 'StrongPassword123']);
$assert($users->findByEmail("' OR 1=1 --") === null, 'Prepared statements reject SQL injection payloads.');
$assert(!str_contains((string) $user['password_hash'], 'StrongPassword123'), 'Passwords are stored only as adaptive hashes.');

$ciphertext = Crypto::encrypt('sensitive-session-data');
$assert(!str_contains($ciphertext, 'sensitive-session-data') && Crypto::decrypt($ciphertext) === 'sensitive-session-data', 'Session payload encryption is authenticated and reversible.');
$handler = new DatabaseSessionHandler($pdo, 3600);
$assert($handler->write('session-id', 'payload|s:5:"value";'), 'Encrypted database session writes successfully.');
$raw = $pdo->query('SELECT payload FROM sessions')->fetchColumn();
$assert(is_string($raw) && !str_contains($raw, 'value'), 'Raw session row contains no plaintext session value.');
$assert($handler->read('session-id') === 'payload|s:5:"value";', 'Session handler reads and decrypts valid payload.');

$limiter = new RateLimiter($pdo);
$assert($limiter->hit('login|127.0.0.1', 2, 60)['allowed'], 'First rate-limit attempt is allowed.');
$assert($limiter->hit('login|127.0.0.1', 2, 60)['allowed'], 'Second rate-limit attempt is allowed.');
$assert(!$limiter->hit('login|127.0.0.1', 2, 60)['allowed'], 'Excess attempt is blocked.');
$storedKey = $pdo->query('SELECT key_hash FROM rate_limits')->fetchColumn();
$assert(is_string($storedKey) && $storedKey !== 'login|127.0.0.1', 'Rate-limit identities are HMAC protected.');

echo "Database security: {$passed} assertions passed." . PHP_EOL;

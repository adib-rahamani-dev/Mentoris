<?php

declare(strict_types=1);

use App\Core\Security;
use App\Core\Authorization;
use App\Core\Database;
use App\Core\Migrator;
use App\Repositories\UserRepository;
require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void {
    if (!$condition) throw new RuntimeException("FAILED: {$message}");
    $passed++;
};

$database = Database::connect(['driver' => 'sqlite', 'database' => ':memory:']);
(new Migrator($database, BASE_PATH . '/database/migrations'))->migrate();
$repository = new UserRepository($database);

try {
    $user = $repository->create(['name' => 'کاربر تست', 'email' => 'USER@example.test', 'password' => 'StrongPass123']);
    $assert(isset($user['id']) && strlen($user['id']) === 24, 'User receives a random identifier.');
    $assert($user['email'] === 'user@example.test', 'Email is normalized.');
    $assert($user['password_hash'] !== 'StrongPass123', 'Plain password is never stored.');
    $assert(Security::verifyPassword('StrongPass123', $user['password_hash']), 'Password hash verifies.');
    $assert(!Security::verifyPassword('WrongPass123', $user['password_hash']), 'Wrong password is rejected.');
    $assert(count($user['notifications']) === 1, 'New user receives a welcome notification.');
    $assert($user['account_role'] === 'student' && $user['status'] === 'active', 'New account receives safe default access.');
    $assert(!Authorization::can($user, 'admin.access'), 'Regular user cannot access administration.');

    $found = $repository->findByEmail('User@Example.Test');
    $assert($found !== null && $found['id'] === $user['id'], 'Email lookup is case-insensitive.');
    $assert($repository->findById($user['id']) !== null, 'User resolves by identifier.');

    $duplicateRejected = false;
    try { $repository->create(['name' => 'Duplicate', 'email' => 'user@example.test', 'password' => 'Another123']); } catch (RuntimeException) { $duplicateRejected = true; }
    $assert($duplicateRejected, 'Duplicate email is rejected.');

    $updated = $repository->updateProfile($user['id'], ['name' => 'نام تازه', 'phone' => '09120000000', 'role' => 'پژوهشگر', 'bio' => 'معرفی کوتاه']);
    $assert($updated['name'] === 'نام تازه', 'Profile name updates.');
    $assert($updated['phone'] === '09120000000', 'Profile phone updates.');
    $assert($updated['role'] === 'پژوهشگر', 'Profile role updates.');
    $assert($updated['bio'] === 'معرفی کوتاه', 'Profile biography updates.');

    $token = $repository->issueResetToken('user@example.test', 3600);
    $assert(is_string($token) && strlen($token) === 64, 'Reset token has sufficient entropy.');
    $stored = $repository->findByEmail('user@example.test');
    $assert($stored['reset_token_hash'] !== $token, 'Reset token is stored only as a hash.');
    $assert($repository->findByResetToken($token)['id'] === $user['id'], 'Valid reset token resolves user.');
    $assert($repository->findByResetToken(str_repeat('a', 64)) === null, 'Invalid reset token is rejected.');
    $assert($repository->resetPassword($token, 'NewStrong456'), 'Password reset succeeds once.');
    $assert($repository->findByResetToken($token) === null, 'Reset token is consumed after use.');
    $assert(!$repository->resetPassword($token, 'ReuseAttempt789'), 'Consumed reset token cannot be reused.');
    $assert(Security::verifyPassword('NewStrong456', $repository->findById($user['id'])['password_hash']), 'New password verifies.');

    $repository->markNotificationsRead($user['id']);
    $readUser = $repository->findById($user['id']);
    $assert($readUser['notifications'][0]['read_at'] !== null, 'Notifications can be marked read.');
    $public = UserRepository::publicUser($readUser);
    $assert(!isset($public['password_hash'], $public['reset_token_hash'], $public['reset_token_expires_at']), 'Public user excludes secrets.');
    $admin = $repository->updateAccess($user['id'], 'admin', 'active');
    $assert($admin['account_role'] === 'admin' && Authorization::can($admin, 'users.manage'), 'Administrator role grants user management permission.');
    $repository->recordLogin($user['id']);
    $assert($repository->findById($user['id'])['last_login_at'] !== null, 'Last login can be recorded for analytics.');
} finally {}

echo "Authentication: {$passed} assertions passed." . PHP_EOL;

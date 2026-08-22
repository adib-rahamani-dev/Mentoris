<?php

declare(strict_types=1);

use App\Payments\SandboxIranianGateway;
use App\Repositories\CommerceRepository;
use App\Repositories\UserRepository;
use App\Services\PaymentService;
use App\Services\PublicContentService;

require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void {
    if (!$condition) throw new RuntimeException("FAILED: {$message}");
    $passed++;
};

$directory = STORAGE_PATH . '/testing-payment';
$usersPath = $directory . '/users.json';
$commercePath = $directory . '/commerce.json';
$users = new UserRepository($usersPath);
$commerce = new CommerceRepository($commercePath);
$payments = new PaymentService(new SandboxIranianGateway(), $commerce, $users);

try {
    $user = $users->create(['name' => 'خریدار تست', 'email' => 'buyer@example.test', 'password' => 'SecurePass123']);
    $secondUser = $users->create(['name' => 'خریدار دوم', 'email' => 'buyer2@example.test', 'password' => 'SecurePass123']);
    $course = PublicContentService::course('schema-therapy');
    $assert($course['price_amount'] === 4900000 && $course['currency'] === 'IRT', 'Course exposes a numeric toman price.');

    $first = $payments->checkoutCourse($course, $user);
    $assert($first['order']['status'] === 'pending', 'Checkout creates a pending order.');
    $assert($first['transaction']['status'] === 'initiated', 'Checkout creates an initiated transaction.');
    $assert(str_starts_with($first['transaction']['authority'], 'sandbox_'), 'Sandbox creates an unguessable authority.');
    $assert(str_starts_with($first['redirect_url'], '/payment/sandbox/'), 'Gateway returns its own redirect URL.');
    $assert($first['transaction']['amount'] === $first['order']['amount'], 'Transaction amount matches order amount.');

    $canceled = $payments->verify($first['transaction']['authority'], 'NOK');
    $assert(!$canceled['successful'], 'Canceled gateway return does not enroll user.');
    $assert($payments->order($first['order']['id'])['status'] === 'canceled', 'Canceled payment updates order.');
    $assert(!in_array($course['slug'], $users->findById($user['id'])['courses'], true), 'Canceled payment does not add course.');

    $second = $payments->checkoutCourse($course, $user);
    $verified = $payments->verify($second['transaction']['authority'], 'OK');
    $assert($verified['successful'], 'Successful callback verifies payment.');
    $assert($verified['order']['status'] === 'paid', 'Verified payment marks order paid.');
    $verifiedTransaction = $payments->transaction($second['transaction']['authority']);
    $assert($verifiedTransaction['status'] === 'verified', 'Verified payment marks transaction verified.');
    $assert(str_starts_with($verifiedTransaction['reference_id'], 'SBX-'), 'Verification stores reference identifier.');
    $updatedUser = $users->findById($user['id']);
    $assert(in_array($course['slug'], $updatedUser['courses'], true), 'Verified payment creates user enrollment.');
    $assert(count($updatedUser['notifications']) === 2, 'Enrollment creates one notification.');

    $again = $payments->verify($second['transaction']['authority'], 'OK');
    $assert($again['successful'], 'Verification callback is idempotent.');
    $assert(count($users->findById($user['id'])['courses']) === 1, 'Idempotent callback does not duplicate course.');
    $assert(count($users->findById($user['id'])['notifications']) === 2, 'Idempotent callback does not duplicate notification.');

    $duplicateRejected = false;
    try { $payments->checkoutCourse($course, $user); } catch (RuntimeException) { $duplicateRejected = true; }
    $assert($duplicateRejected, 'Already enrolled user cannot purchase the course again.');

    $freeCourse = PublicContentService::course('mental-health-literacy');
    $free = $payments->checkoutCourse($freeCourse, $user);
    $assert($free['order']['status'] === 'paid', 'Free checkout completes without gateway redirect.');
    $assert(str_starts_with($free['redirect_url'], '/payment/result'), 'Free checkout redirects to payment result.');
    $assert(in_array($freeCourse['slug'], $users->findById($user['id'])['courses'], true), 'Free checkout creates enrollment.');

    $limited = $course;
    $limited['slug'] = 'limited-test-course'; $limited['title'] = 'دوره ظرفیت محدود'; $limited['available'] = 1; $limited['price_amount'] = 1000;
    $held = $payments->checkoutCourse($limited, $secondUser);
    $assert($held['order']['status'] === 'pending', 'First checkout reserves limited seat.');
    $thirdUser = $users->create(['name' => 'خریدار سوم', 'email' => 'buyer3@example.test', 'password' => 'SecurePass123']);
    $capacityRejected = false;
    try { $payments->checkoutCourse($limited, $thirdUser); } catch (RuntimeException) { $capacityRejected = true; }
    $assert($capacityRejected, 'Pending reservation prevents overselling capacity.');

    $unknownRejected = false;
    try { $payments->verify('sandbox_unknown', 'OK'); } catch (RuntimeException) { $unknownRejected = true; }
    $assert($unknownRejected, 'Unknown authority is rejected.');
    $assert(count($payments->userOrders($user['id'])) === 3, 'User order history contains canceled, paid, and free orders.');
    $assert(count($payments->orderTransactions($second['order']['id'])) === 1, 'Order exposes its transaction history.');
} finally {
    if (is_file($usersPath)) unlink($usersPath);
    if (is_file($commercePath)) unlink($commercePath);
    if (is_dir($directory) && (scandir($directory) ?: []) === ['.', '..']) rmdir($directory);
}

echo "Payment & Enrollment: {$passed} assertions passed." . PHP_EOL;

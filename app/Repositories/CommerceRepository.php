<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Security;
use RuntimeException;

final class CommerceRepository
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $configured = function_exists('env') ? env('COMMERCE_STORAGE_PATH') : null;
        $this->path = $path ?? (is_string($configured) && $configured !== '' ? $configured : (defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 2) . '/storage') . '/data/commerce.json');
    }

    public function createCourseOrder(array $attributes, int $availableSeats): array
    {
        return $this->mutate(function (array &$data) use ($attributes, $availableSeats): array {
            $now = time();
            foreach ($data['enrollments'] as $enrollment) {
                if ($enrollment['user_id'] === $attributes['user_id'] && $enrollment['course_slug'] === $attributes['item_id'] && $enrollment['status'] === 'active') throw new RuntimeException('شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
            }
            foreach ($data['orders'] as $order) {
                if ($order['user_id'] === $attributes['user_id'] && $order['item_id'] === $attributes['item_id'] && $order['status'] === 'pending' && strtotime($order['expires_at']) > $now) throw new RuntimeException('یک سفارش پرداخت‌نشده برای این دوره دارید.');
            }
            $reserved = count(array_filter($data['orders'], static fn (array $order): bool => $order['item_id'] === $attributes['item_id'] && $order['status'] === 'pending' && strtotime($order['expires_at']) > $now));
            $enrolled = count(array_filter($data['enrollments'], static fn (array $enrollment): bool => $enrollment['course_slug'] === $attributes['item_id'] && $enrollment['status'] === 'active'));
            if ($availableSeats <= 0 || $reserved + $enrolled >= $availableSeats) throw new RuntimeException('ظرفیت قابل فروش این دوره تکمیل شده است.');
            $createdAt = date(DATE_ATOM);
            $order = [
                'id' => Security::randomToken(16), 'number' => 'MTR-' . date('ymd') . '-' . strtoupper(substr(Security::randomToken(4), 0, 8)),
                'user_id' => $attributes['user_id'], 'customer_name' => $attributes['customer_name'], 'customer_email' => $attributes['customer_email'],
                'item_type' => 'course', 'item_id' => $attributes['item_id'], 'item_title' => $attributes['item_title'],
                'amount' => (int) $attributes['amount'], 'currency' => $attributes['currency'] ?? 'IRT', 'status' => 'pending',
                'created_at' => $createdAt, 'updated_at' => $createdAt, 'expires_at' => date(DATE_ATOM, $now + 900), 'paid_at' => null,
            ];
            $data['orders'][] = $order;
            return $order;
        });
    }

    public function createTransaction(array $attributes): array
    {
        return $this->mutate(function (array &$data) use ($attributes): array {
            $transaction = [
                'id' => Security::randomToken(16), 'order_id' => $attributes['order_id'], 'gateway' => $attributes['gateway'],
                'authority' => $attributes['authority'], 'amount' => (int) $attributes['amount'], 'currency' => $attributes['currency'] ?? 'IRT',
                'status' => $attributes['status'] ?? 'initiated', 'reference_id' => $attributes['reference_id'] ?? null,
                'message' => $attributes['message'] ?? null, 'gateway_response' => $attributes['gateway_response'] ?? [],
                'created_at' => date(DATE_ATOM), 'verified_at' => null,
            ];
            $data['transactions'][] = $transaction;
            return $transaction;
        });
    }

    public function findOrder(string $id): ?array { return $this->find('orders', static fn ($item) => $item['id'] === $id); }
    public function findTransactionByAuthority(string $authority): ?array { return $this->find('transactions', static fn ($item) => $item['authority'] === $authority); }

    public function ordersForUser(string $userId): array
    {
        return array_values(array_filter($this->read()['orders'], static fn (array $order): bool => $order['user_id'] === $userId));
    }

    public function transactionsForOrder(string $orderId): array
    {
        return array_values(array_filter($this->read()['transactions'], static fn (array $transaction): bool => $transaction['order_id'] === $orderId));
    }

    public function markFailed(string $orderId, string $transactionId, string $status, string $message, array $response = []): void
    {
        $this->mutate(function (array &$data) use ($orderId, $transactionId, $status, $message, $response): bool {
            foreach ($data['orders'] as &$order) if ($order['id'] === $orderId && $order['status'] !== 'paid') { $order['status'] = $status; $order['updated_at'] = date(DATE_ATOM); }
            foreach ($data['transactions'] as &$transaction) if ($transaction['id'] === $transactionId && $transaction['status'] !== 'verified') { $transaction['status'] = $status; $transaction['message'] = $message; $transaction['gateway_response'] = $response; }
            return true;
        });
    }

    public function finalizePayment(string $orderId, string $transactionId, string $referenceId, array $response = []): array
    {
        return $this->mutate(function (array &$data) use ($orderId, $transactionId, $referenceId, $response): array {
            $orderResult = null;
            foreach ($data['orders'] as &$order) {
                if ($order['id'] !== $orderId) continue;
                $order['status'] = 'paid'; $order['paid_at'] ??= date(DATE_ATOM); $order['updated_at'] = date(DATE_ATOM); $orderResult = $order;
            }
            if ($orderResult === null) throw new RuntimeException('سفارش پیدا نشد.');
            foreach ($data['transactions'] as &$transaction) {
                if ($transaction['id'] !== $transactionId) continue;
                $transaction['status'] = 'verified'; $transaction['reference_id'] = $referenceId; $transaction['gateway_response'] = $response; $transaction['verified_at'] ??= date(DATE_ATOM);
            }
            $exists = false;
            foreach ($data['enrollments'] as $enrollment) if ($enrollment['user_id'] === $orderResult['user_id'] && $enrollment['course_slug'] === $orderResult['item_id'] && $enrollment['status'] === 'active') $exists = true;
            if (!$exists) $data['enrollments'][] = ['id' => Security::randomToken(16), 'user_id' => $orderResult['user_id'], 'course_slug' => $orderResult['item_id'], 'order_id' => $orderResult['id'], 'status' => 'active', 'enrolled_at' => date(DATE_ATOM)];
            return $orderResult;
        });
    }

    private function find(string $collection, callable $predicate): ?array
    {
        foreach ($this->read()[$collection] as $item) if ($predicate($item)) return $item;
        return null;
    }

    private function read(): array
    {
        if (!is_file($this->path)) return $this->emptyData();
        $handle = fopen($this->path, 'rb');
        if ($handle === false) throw new RuntimeException('امکان خواندن اطلاعات فروش وجود ندارد.');
        try { flock($handle, LOCK_SH); $decoded = json_decode(stream_get_contents($handle) ?: '{}', true); return $this->normalize($decoded); }
        finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    private function mutate(callable $callback): mixed
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('امکان ساخت پوشه فروش وجود ندارد.');
        $handle = fopen($this->path, 'c+b');
        if ($handle === false) throw new RuntimeException('امکان نوشتن اطلاعات فروش وجود ندارد.');
        try {
            if (!flock($handle, LOCK_EX)) throw new RuntimeException('امکان قفل‌کردن اطلاعات فروش وجود ندارد.');
            rewind($handle); $data = $this->normalize(json_decode(stream_get_contents($handle) ?: '{}', true));
            $result = $callback($data); $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            rewind($handle); ftruncate($handle, 0); fwrite($handle, $json); fflush($handle); return $result;
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    private function normalize(mixed $data): array
    {
        return is_array($data) ? ['orders' => is_array($data['orders'] ?? null) ? $data['orders'] : [], 'transactions' => is_array($data['transactions'] ?? null) ? $data['transactions'] : [], 'enrollments' => is_array($data['enrollments'] ?? null) ? $data['enrollments'] : []] : $this->emptyData();
    }

    private function emptyData(): array { return ['orders' => [], 'transactions' => [], 'enrollments' => []]; }
}

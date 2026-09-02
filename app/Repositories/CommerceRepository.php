<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Security;
use PDO;
use RuntimeException;

final class CommerceRepository
{
    public function __construct(private readonly ?PDO $database = null) {}

    public function createCourseOrder(array $attributes, int $availableSeats): array
    {
        return Database::transaction($this->pdo(), function (PDO $pdo) use ($attributes, $availableSeats): array {
            $now = Database::now();
            $itemId = (string) $attributes['item_id'];
            $userId = (string) $attributes['user_id'];
            $driver = Database::driver($pdo);
            $upsert = $driver === 'mysql'
                ? 'INSERT INTO inventory_locks (item_type,item_id,updated_at) VALUES (:type,:item,:now) ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at)'
                : 'INSERT INTO inventory_locks (item_type,item_id,updated_at) VALUES (:type,:item,:now) ON CONFLICT(item_type,item_id) DO UPDATE SET updated_at=excluded.updated_at';
            $pdo->prepare($upsert)->execute(['type' => 'course', 'item' => $itemId, 'now' => $now]);
            $lock = $pdo->prepare('SELECT item_id FROM inventory_locks WHERE item_type=:type AND item_id=:item' . ($driver === 'mysql' ? ' FOR UPDATE' : ''));
            $lock->execute(['type' => 'course', 'item' => $itemId]);
            $pdo->prepare("UPDATE orders SET status='expired',updated_at=:now WHERE status='pending' AND expires_at<=:now")->execute(['now' => $now]);

            $enrollment = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id=:user AND course_slug=:item AND status IN ('active','completed') LIMIT 1");
            $enrollment->execute(['user' => $userId, 'item' => $itemId]);
            if ($enrollment->fetchColumn() !== false) throw new RuntimeException('شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
            $pending = $pdo->prepare("SELECT 1 FROM orders WHERE user_id=:user AND item_type='course' AND item_id=:item AND status='pending' AND expires_at>:now LIMIT 1");
            $pending->execute(['user' => $userId, 'item' => $itemId, 'now' => $now]);
            if ($pending->fetchColumn() !== false) throw new RuntimeException('یک سفارش پرداخت‌نشده برای این دوره دارید.');
            $reserved = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE item_type='course' AND item_id=:item AND status='pending' AND expires_at>:now");
            $reserved->execute(['item' => $itemId, 'now' => $now]);
            $enrolled = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_slug=:item AND status IN ('active','completed')");
            $enrolled->execute(['item' => $itemId]);
            if ($availableSeats <= 0 || (int) $reserved->fetchColumn() + (int) $enrolled->fetchColumn() >= $availableSeats) throw new RuntimeException('ظرفیت قابل فروش این دوره تکمیل شده است.');

            $order = [
                'id' => Security::randomToken(16), 'number' => 'MTR-' . gmdate('ymd') . '-' . strtoupper(substr(Security::randomToken(4), 0, 8)),
                'user_id' => $userId, 'customer_name' => trim((string) $attributes['customer_name']), 'customer_email' => mb_strtolower(trim((string) $attributes['customer_email'])),
                'item_type' => 'course', 'item_id' => $itemId, 'item_title' => (string) $attributes['item_title'],
                'amount' => max(0, (int) $attributes['amount']), 'currency' => strtoupper((string) ($attributes['currency'] ?? 'IRT')), 'status' => 'pending',
                'expires_at' => gmdate('Y-m-d H:i:s', time() + 900), 'paid_at' => null, 'created_at' => $now, 'updated_at' => $now,
            ];
            $statement = $pdo->prepare('INSERT INTO orders (id,order_number,user_id,customer_name,customer_email,item_type,item_id,item_title,amount,currency,status,expires_at,paid_at,created_at,updated_at) VALUES (:id,:number,:user_id,:customer_name,:customer_email,:item_type,:item_id,:item_title,:amount,:currency,:status,:expires_at,:paid_at,:created_at,:updated_at)');
            $statement->execute($order);
            return $order;
        });
    }

    public function createTransaction(array $attributes): array
    {
        $now = Database::now();
        $transaction = [
            'id' => Security::randomToken(16), 'order_id' => (string) $attributes['order_id'], 'gateway' => (string) $attributes['gateway'],
            'authority' => (string) $attributes['authority'], 'amount' => max(0, (int) $attributes['amount']), 'currency' => strtoupper((string) ($attributes['currency'] ?? 'IRT')),
            'status' => (string) ($attributes['status'] ?? 'initiated'), 'reference_id' => $attributes['reference_id'] ?? null, 'message' => $attributes['message'] ?? null,
            'gateway_response' => json_encode($attributes['gateway_response'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'verified_at' => null, 'created_at' => $now, 'updated_at' => $now,
        ];
        $statement = $this->pdo()->prepare('INSERT INTO payment_transactions (id,order_id,gateway,authority,amount,currency,status,reference_id,message,gateway_response,verified_at,created_at,updated_at) VALUES (:id,:order_id,:gateway,:authority,:amount,:currency,:status,:reference_id,:message,:gateway_response,:verified_at,:created_at,:updated_at)');
        $statement->execute($transaction);
        $transaction['gateway_response'] = $attributes['gateway_response'] ?? [];
        return $transaction;
    }

    public function findOrder(string $id): ?array
    {
        return $this->findOrderOn($this->pdo(), $id);
    }

    public function findTransactionByAuthority(string $authority): ?array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM payment_transactions WHERE authority=:authority LIMIT 1');
        $statement->execute(['authority' => $authority]);
        $row = $statement->fetch();
        return is_array($row) ? $this->normalizeTransaction($row) : null;
    }

    public function ordersForUser(string $userId): array
    {
        $statement = $this->pdo()->prepare($this->orderSelect() . ' WHERE user_id=:user_id ORDER BY created_at ASC');
        $statement->execute(['user_id' => $userId]);
        return array_map($this->normalizeOrder(...), $statement->fetchAll() ?: []);
    }

    public function transactionsForOrder(string $orderId): array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM payment_transactions WHERE order_id=:order_id ORDER BY created_at ASC');
        $statement->execute(['order_id' => $orderId]);
        return array_map($this->normalizeTransaction(...), $statement->fetchAll() ?: []);
    }

    public function summary(): array
    {
        $row = $this->pdo()->query("SELECT COUNT(*) AS orders,SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_orders,COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END),0) AS revenue FROM orders")->fetch() ?: [];
        return ['orders' => (int) ($row['orders'] ?? 0), 'paid_orders' => (int) ($row['paid_orders'] ?? 0), 'revenue' => (int) ($row['revenue'] ?? 0),
            'transactions' => (int) $this->pdo()->query('SELECT COUNT(*) FROM payment_transactions')->fetchColumn(),
            'enrollments' => (int) $this->pdo()->query("SELECT COUNT(*) FROM enrollments WHERE status IN ('active','completed')")->fetchColumn()];
    }

    public function recentOrders(int $limit = 8): array
    {
        $statement = $this->pdo()->prepare($this->orderSelect() . ' ORDER BY created_at DESC LIMIT :limit');
        $statement->bindValue('limit', max(1, min(100, $limit)), PDO::PARAM_INT);
        $statement->execute();
        return array_map($this->normalizeOrder(...), $statement->fetchAll() ?: []);
    }

    public function markFailed(string $orderId, string $transactionId, string $status, string $message, array $response = []): void
    {
        if (!in_array($status, ['failed', 'canceled', 'expired'], true)) throw new RuntimeException('وضعیت تراکنش نامعتبر است.');
        Database::transaction($this->pdo(), function (PDO $pdo) use ($orderId, $transactionId, $status, $message, $response): void {
            $now = Database::now();
            $pdo->prepare("UPDATE orders SET status=:status,updated_at=:now WHERE id=:id AND status<>'paid'")->execute(['status' => $status, 'now' => $now, 'id' => $orderId]);
            $pdo->prepare("UPDATE payment_transactions SET status=:status,message=:message,gateway_response=:response,updated_at=:now WHERE id=:id AND status<>'verified'")
                ->execute(['status' => $status, 'message' => mb_substr($message, 0, 500), 'response' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), 'now' => $now, 'id' => $transactionId]);
        });
    }

    public function finalizePayment(string $orderId, string $transactionId, string $referenceId, array $response = []): array
    {
        return Database::transaction($this->pdo(), function (PDO $pdo) use ($orderId, $transactionId, $referenceId, $response): array {
            $suffix = Database::driver($pdo) === 'mysql' ? ' FOR UPDATE' : '';
            $orderStatement = $pdo->prepare($this->orderSelect() . ' WHERE id=:id LIMIT 1' . $suffix);
            $orderStatement->execute(['id' => $orderId]);
            $order = $orderStatement->fetch();
            if (!is_array($order)) throw new RuntimeException('سفارش پیدا نشد.');
            $transactionStatement = $pdo->prepare('SELECT * FROM payment_transactions WHERE id=:id LIMIT 1' . $suffix);
            $transactionStatement->execute(['id' => $transactionId]);
            $transaction = $transactionStatement->fetch();
            if (!is_array($transaction) || $transaction['order_id'] !== $orderId) throw new RuntimeException('تراکنش متعلق به این سفارش نیست.');
            if ((int) $transaction['amount'] !== (int) $order['amount'] || $transaction['currency'] !== $order['currency']) throw new RuntimeException('مبلغ یا ارز تراکنش با سفارش تطابق ندارد.');
            if ($order['status'] === 'paid') return $this->normalizeOrder($order);
            if ($order['status'] !== 'pending' || strtotime((string) $order['expires_at'] . ' UTC') <= time()) throw new RuntimeException('این سفارش دیگر قابل پرداخت نیست.');

            $now = Database::now();
            $pdo->prepare("UPDATE orders SET status='paid',paid_at=:now,updated_at=:now WHERE id=:id AND status<>'paid'")->execute(['now' => $now, 'id' => $orderId]);
            $pdo->prepare("UPDATE payment_transactions SET status='verified',reference_id=:reference,gateway_response=:response,verified_at=:now,updated_at=:now WHERE id=:id")
                ->execute(['reference' => $referenceId !== '' ? $referenceId : null, 'response' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), 'now' => $now, 'id' => $transactionId]);
            $sql = Database::driver($pdo) === 'mysql'
                ? 'INSERT IGNORE INTO enrollments (id,user_id,course_slug,order_id,status,enrolled_at) VALUES (:id,:user,:course,:order_id,:status,:now)'
                : 'INSERT OR IGNORE INTO enrollments (id,user_id,course_slug,order_id,status,enrolled_at) VALUES (:id,:user,:course,:order_id,:status,:now)';
            $enrollment = $pdo->prepare($sql);
            $enrollment->execute(['id' => Security::randomToken(16), 'user' => $order['user_id'], 'course' => $order['item_id'], 'order_id' => $orderId, 'status' => 'active', 'now' => $now]);
            if ($enrollment->rowCount() === 1) {
                $pdo->prepare('INSERT INTO notifications (id,user_id,title,message,created_at) VALUES (:id,:user,:title,:message,:now)')
                    ->execute(['id' => Security::randomToken(8), 'user' => $order['user_id'], 'title' => 'ثبت‌نام دوره نهایی شد', 'message' => 'دوره جدید به بخش «دوره‌های من» اضافه شد.', 'now' => $now]);
            }
            return $this->findOrderOn($pdo, $orderId) ?? throw new RuntimeException('سفارش پیدا نشد.');
        });
    }

    private function findOrderOn(PDO $pdo, string $id): ?array
    {
        $statement = $pdo->prepare($this->orderSelect() . ' WHERE id=:id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $this->normalizeOrder($row) : null;
    }

    private function orderSelect(): string { return 'SELECT id,order_number AS number,user_id,customer_name,customer_email,item_type,item_id,item_title,amount,currency,status,expires_at,paid_at,created_at,updated_at FROM orders'; }
    private function normalizeOrder(array $row): array { $row['amount'] = (int) $row['amount']; return $row; }
    private function normalizeTransaction(array $row): array { $row['amount'] = (int) $row['amount']; $data = json_decode((string) ($row['gateway_response'] ?? '{}'), true); $row['gateway_response'] = is_array($data) ? $data : []; return $row; }
    private function pdo(): PDO { return $this->database ?? Database::connection(); }
}

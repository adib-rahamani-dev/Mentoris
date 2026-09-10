<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Security;
use PDO;
use RuntimeException;

final class AdminRepository
{
    public function __construct(private readonly ?PDO $database = null) {}

    public function dashboard(): array
    {
        $pdo = $this->pdo();
        return [
            'users' => $this->scalar('SELECT COUNT(*) FROM users'),
            'active_users' => $this->scalar("SELECT COUNT(*) FROM users WHERE status='active'"),
            'new_users_30d' => $this->scalar("SELECT COUNT(*) FROM users WHERE created_at>=UTC_TIMESTAMP()-INTERVAL 30 DAY"),
            'orders' => $this->scalar('SELECT COUNT(*) FROM orders'),
            'paid_orders' => $this->scalar("SELECT COUNT(*) FROM orders WHERE status='paid'"),
            'pending_orders' => $this->scalar("SELECT COUNT(*) FROM orders WHERE status='pending' AND expires_at>UTC_TIMESTAMP()"),
            'revenue' => $this->scalar("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='paid'"),
            'revenue_30d' => $this->scalar("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='paid' AND paid_at>=UTC_TIMESTAMP()-INTERVAL 30 DAY"),
            'enrollments' => $this->scalar("SELECT COUNT(*) FROM enrollments WHERE status IN ('active','completed')"),
            'event_requests' => $this->scalar("SELECT COUNT(*) FROM event_registrations WHERE status='pending'"),
            'community_requests' => $this->scalar("SELECT COUNT(*) FROM community_memberships WHERE status='pending'"),
            'unread_messages' => $this->scalar("SELECT COUNT(*) FROM contact_messages WHERE status='new'"),
            'active_sessions' => $this->scalar('SELECT COUNT(*) FROM sessions WHERE expires_at>UTC_TIMESTAMP()'),
            'blocked_buckets' => $this->scalar('SELECT COUNT(*) FROM rate_limits WHERE reset_at>UTC_TIMESTAMP() AND hits>5'),
            'failed_transactions_24h' => $this->scalar("SELECT COUNT(*) FROM payment_transactions WHERE status='failed' AND updated_at>=UTC_TIMESTAMP()-INTERVAL 1 DAY"),
        ];
    }

    public function monthlyMetrics(int $months = 12): array
    {
        $months = max(3, min(24, $months));
        $users = $this->pdo()->query("SELECT DATE_FORMAT(created_at,'%Y-%m') period,COUNT(*) total FROM users WHERE created_at>=UTC_TIMESTAMP()-INTERVAL {$months} MONTH GROUP BY period ORDER BY period")->fetchAll() ?: [];
        $revenue = $this->pdo()->query("SELECT DATE_FORMAT(paid_at,'%Y-%m') period,COALESCE(SUM(amount),0) total FROM orders WHERE status='paid' AND paid_at>=UTC_TIMESTAMP()-INTERVAL {$months} MONTH GROUP BY period ORDER BY period")->fetchAll() ?: [];
        return ['users' => $this->keyValues($users), 'revenue' => $this->keyValues($revenue)];
    }

    public function roleCounts(): array
    {
        $rows = $this->pdo()->query('SELECT account_role label,COUNT(*) total FROM users GROUP BY account_role ORDER BY total DESC')->fetchAll() ?: [];
        return $this->keyValues($rows, 'label');
    }

    public function orderStatusCounts(): array
    {
        $rows = $this->pdo()->query('SELECT status label,COUNT(*) total FROM orders GROUP BY status ORDER BY total DESC')->fetchAll() ?: [];
        return $this->keyValues($rows, 'label');
    }

    public function orders(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = []; $params = [];
        $status = (string) ($filters['status'] ?? 'all');
        if (in_array($status, ['pending','paid','failed','canceled','expired','refunded'], true)) { $where[] = 'o.status=:status'; $params['status'] = $status; }
        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') { $where[] = '(o.order_number LIKE :q1 OR o.customer_name LIKE :q2 OR o.customer_email LIKE :q3 OR o.item_title LIKE :q4)'; foreach (['q1','q2','q3','q4'] as $key) $params[$key] = '%' . $query . '%'; }
        $clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        return $this->paginate("SELECT o.*,o.order_number AS number,u.name AS user_name FROM orders o JOIN users u ON u.id=o.user_id{$clause} ORDER BY o.created_at DESC", "SELECT COUNT(*) FROM orders o{$clause}", $params, $page, $perPage);
    }

    public function order(string $id): ?array
    {
        $statement = $this->pdo()->prepare('SELECT o.*,o.order_number AS number,u.name AS user_name,u.email AS user_email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=:id LIMIT 1');
        $statement->execute(['id' => $id]);
        $order = $statement->fetch();
        if (!is_array($order)) return null;
        $transactions = $this->pdo()->prepare('SELECT id,gateway,authority,amount,currency,status,reference_id,message,verified_at,created_at,updated_at FROM payment_transactions WHERE order_id=:id ORDER BY created_at DESC');
        $transactions->execute(['id' => $id]);
        $enrollment = $this->pdo()->prepare('SELECT id,course_slug,status,enrolled_at FROM enrollments WHERE order_id=:id LIMIT 1');
        $enrollment->execute(['id' => $id]);
        $order['transactions'] = $transactions->fetchAll() ?: [];
        $order['enrollment'] = $enrollment->fetch() ?: null;
        return $order;
    }

    public function userOperations(string $userId): array
    {
        $queries = [
            'orders' => 'SELECT id,order_number AS number,item_title,amount,status,created_at FROM orders WHERE user_id=:id ORDER BY created_at DESC LIMIT 20',
            'events' => 'SELECT event_slug,status,created_at FROM event_registrations WHERE user_id=:id ORDER BY created_at DESC LIMIT 20',
            'audit' => 'SELECT action,subject_type,subject_id,created_at FROM audit_logs WHERE actor_id=:id ORDER BY created_at DESC LIMIT 20',
        ];
        $result = [];
        foreach ($queries as $key => $sql) { $statement = $this->pdo()->prepare($sql); $statement->execute(['id' => $userId]); $result[$key] = $statement->fetchAll() ?: []; }
        return $result;
    }

    public function notifyUser(string $userId, string $title, string $message): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO notifications (id,user_id,title,message,created_at) VALUES (:id,:user,:title,:message,:created_at)');
        $statement->execute(['id' => Security::randomToken(8), 'user' => $userId, 'title' => trim($title), 'message' => trim($message), 'created_at' => Database::now()]);
    }

    public function engagements(string $type, array $filters, int $page = 1, int $perPage = 20): array
    {
        $config = $this->engagementConfig($type);
        $where = []; $params = [];
        $status = trim((string) ($filters['status'] ?? 'all'));
        if ($status !== 'all' && in_array($status, $config['statuses'], true)) { $where[] = 'status=:status'; $params['status'] = $status; }
        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') { $where[] = "({$config['name']} LIKE :q1 OR {$config['email']} LIKE :q2)"; $params['q1'] = $params['q2'] = '%' . $query . '%'; }
        $clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        return $this->paginate("SELECT {$config['select']} FROM {$config['table']}{$clause} ORDER BY created_at DESC", "SELECT COUNT(*) FROM {$config['table']}{$clause}", $params, $page, $perPage) + ['type' => $type, 'statuses' => $config['statuses']];
    }

    public function updateEngagementStatus(string $type, string $id, string $status): bool
    {
        $config = $this->engagementConfig($type);
        if (!in_array($status, $config['statuses'], true)) throw new RuntimeException('وضعیت انتخاب‌شده معتبر نیست.');
        $statement = $this->pdo()->prepare("UPDATE {$config['table']} SET status=:status,updated_at=:updated_at WHERE id=:id");
        $statement->execute(['status' => $status, 'updated_at' => Database::now(), 'id' => $id]);
        return $statement->rowCount() === 1;
    }

    public function auditLogs(array $filters, int $page = 1, int $perPage = 30): array
    {
        $where = []; $params = [];
        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') { $where[] = '(a.action LIKE :q1 OR a.subject_type LIKE :q2 OR a.subject_id LIKE :q3 OR u.email LIKE :q4)'; foreach (['q1','q2','q3','q4'] as $key) $params[$key] = '%' . $query . '%'; }
        $clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        return $this->paginate("SELECT a.id,a.action,a.subject_type,a.subject_id,a.old_values,a.new_values,a.ip_hash,a.created_at,u.name actor_name,u.email actor_email FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id{$clause} ORDER BY a.created_at DESC", "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id{$clause}", $params, $page, $perPage);
    }

    public function recentAudit(int $limit = 8): array
    {
        $statement = $this->pdo()->prepare('SELECT a.action,a.subject_type,a.subject_id,a.created_at,u.name actor_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id ORDER BY a.created_at DESC LIMIT :limit');
        $statement->bindValue('limit', max(1, min(30, $limit)), PDO::PARAM_INT); $statement->execute();
        return $statement->fetchAll() ?: [];
    }

    public function systemHealth(): array
    {
        $version = (string) $this->pdo()->query('SELECT VERSION()')->fetchColumn();
        return [
            'database' => Database::ping($this->pdo()), 'mysql_version' => $version,
            'tables' => $this->scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()'),
            'active_sessions' => $this->scalar('SELECT COUNT(*) FROM sessions WHERE expires_at>UTC_TIMESTAMP()'),
            'expired_sessions' => $this->scalar('SELECT COUNT(*) FROM sessions WHERE expires_at<=UTC_TIMESTAMP()'),
            'rate_buckets' => $this->scalar('SELECT COUNT(*) FROM rate_limits WHERE reset_at>UTC_TIMESTAMP()'),
            'failed_transactions' => $this->scalar("SELECT COUNT(*) FROM payment_transactions WHERE status='failed'"),
            'unused_reset_tokens' => $this->scalar('SELECT COUNT(*) FROM password_reset_tokens WHERE used_at IS NULL AND expires_at>UTC_TIMESTAMP()'),
            'last_audit' => $this->pdo()->query('SELECT created_at FROM audit_logs ORDER BY created_at DESC LIMIT 1')->fetchColumn() ?: null,
        ];
    }

    private function engagementConfig(string $type): array
    {
        return match ($type) {
            'events' => ['table' => 'event_registrations', 'name' => 'applicant_name', 'email' => 'applicant_email', 'statuses' => ['pending','approved','rejected','canceled'], 'select' => 'id,event_slug AS context,applicant_name AS name,applicant_email AS email,applicant_phone AS phone,professional_role AS detail,status,created_at,updated_at'],
            'community' => ['table' => 'community_memberships', 'name' => 'name', 'email' => 'email', 'statuses' => ['pending','approved','rejected','suspended'], 'select' => 'id,\'community\' AS context,name,email,\'\' AS phone,professional_role AS detail,status,created_at,updated_at'],
            'messages' => ['table' => 'contact_messages', 'name' => 'name', 'email' => 'email', 'statuses' => ['new','in_progress','resolved','spam'], 'select' => 'id,subject AS context,name,email,phone,message AS detail,status,created_at,updated_at'],
            default => throw new RuntimeException('بخش مدیریتی نامعتبر است.'),
        };
    }

    private function paginate(string $sql, string $countSql, array $params, int $page, int $perPage): array
    {
        $page = max(1, $page); $perPage = max(5, min(100, $perPage));
        $count = $this->pdo()->prepare($countSql); $count->execute($params); $total = (int) $count->fetchColumn();
        $statement = $this->pdo()->prepare($sql . ' LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $statement->bindValue($key, $value);
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT); $statement->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT); $statement->execute();
        return ['items' => $statement->fetchAll() ?: [], 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))]];
    }

    private function scalar(string $sql): int { return (int) $this->pdo()->query($sql)->fetchColumn(); }
    private function keyValues(array $rows, string $key = 'period'): array { $result = []; foreach ($rows as $row) $result[(string) $row[$key]] = (int) $row['total']; return $result; }
    private function pdo(): PDO { return $this->database ?? Database::connection(); }
}

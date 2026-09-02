<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Security;
use PDO;
use PDOException;
use RuntimeException;

final class UserRepository
{
    public function __construct(private readonly ?PDO $database = null)
    {
    }

    public function findById(string $id): ?array
    {
        return $this->findOne('id = :value', $id);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findOne('email = :value', self::normalizeEmail($email));
    }

    public function all(): array
    {
        $statement = $this->pdo()->query($this->userSelect() . ' ORDER BY created_at DESC');
        return array_map(fn (array $user): array => self::publicUser($this->hydrate($user)), $statement->fetchAll() ?: []);
    }

    public function create(array $attributes): array
    {
        $email = self::normalizeEmail((string) ($attributes['email'] ?? ''));
        $name = trim((string) ($attributes['name'] ?? ''));
        $password = (string) ($attributes['password'] ?? '');
        $now = Database::now();
        $adminEmails = array_values(array_filter(array_map(
            static fn (string $value): string => self::normalizeEmail($value),
            explode(',', (string) env('SUPER_ADMIN_EMAILS', ''))
        )));
        $user = [
            'id' => Security::randomToken(12),
            'name' => $name,
            'email' => $email,
            'password_hash' => Security::hashPassword($password),
            'account_role' => in_array($email, $adminEmails, true) ? 'super_admin' : 'student',
        ];

        try {
            return Database::transaction($this->pdo(), function (PDO $pdo) use ($user, $now): array {
                $statement = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, phone, professional_role, bio, account_role, status, auth_version, password_changed_at, created_at, updated_at) VALUES (:id, :name, :email, :password_hash, :phone, :professional_role, :bio, :account_role, :status, :auth_version, :password_changed_at, :created_at, :updated_at)');
                $statement->execute([
                    'id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'],
                    'password_hash' => $user['password_hash'], 'phone' => '', 'professional_role' => '', 'bio' => '',
                    'account_role' => $user['account_role'], 'status' => 'active', 'auth_version' => 1,
                    'password_changed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $this->insertNotification($pdo, $user['id'], 'به Mentoris خوش آمدید', 'پروفایل خود را کامل کنید و مسیر یادگیری مناسب را انتخاب کنید.', $now);
                return $this->findByIdOn($pdo, $user['id']) ?? throw new RuntimeException('حساب کاربری ایجاد نشد.');
            });
        } catch (PDOException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw new RuntimeException('این ایمیل قبلاً ثبت شده است.');
            }
            throw $exception;
        }
    }

    public function updateProfile(string $id, array $attributes): ?array
    {
        $allowed = ['name' => 'name', 'phone' => 'phone', 'role' => 'professional_role', 'bio' => 'bio'];
        $sets = [];
        $values = ['id' => $id, 'updated_at' => Database::now()];
        foreach ($allowed as $input => $column) {
            if (!array_key_exists($input, $attributes)) {
                continue;
            }
            $sets[] = "{$column} = :{$input}";
            $values[$input] = trim((string) $attributes[$input]);
        }
        if ($sets === []) {
            return $this->findById($id);
        }
        $sets[] = 'updated_at = :updated_at';
        $statement = $this->pdo()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $statement->execute($values);
        return $this->findById($id);
    }

    public function rehashPassword(string $id, string $password): void
    {
        $statement = $this->pdo()->prepare('UPDATE users SET password_hash = :hash, updated_at = :updated_at WHERE id = :id');
        $statement->execute(['hash' => Security::hashPassword($password), 'updated_at' => Database::now(), 'id' => $id]);
    }

    public function recordLogin(string $id): void
    {
        $now = Database::now();
        $statement = $this->pdo()->prepare('UPDATE users SET last_login_at = :now, updated_at = :now WHERE id = :id');
        $statement->execute(['now' => $now, 'id' => $id]);
    }

    public function updateAccess(string $id, string $accountRole, string $status): ?array
    {
        $validRoles = ['super_admin', 'admin', 'editor', 'instructor', 'support', 'student'];
        $validStatuses = ['active', 'suspended'];
        if (!in_array($accountRole, $validRoles, true) || !in_array($status, $validStatuses, true)) {
            throw new RuntimeException('نقش یا وضعیت حساب معتبر نیست.');
        }
        $statement = $this->pdo()->prepare('UPDATE users SET account_role = :account_role, status = :status, auth_version = auth_version + 1, updated_at = :updated_at WHERE id = :id');
        $statement->execute(['account_role' => $accountRole, 'status' => $status, 'updated_at' => Database::now(), 'id' => $id]);
        return $this->findById($id);
    }

    public function issueResetToken(string $email, int $ttlSeconds = 3600): ?string
    {
        $user = $this->findByEmail($email);
        if ($user === null) {
            return null;
        }
        $token = Security::randomToken(32);
        $now = Database::now();
        $expiresAt = gmdate('Y-m-d H:i:s', time() + max(300, $ttlSeconds));
        Database::transaction($this->pdo(), function (PDO $pdo) use ($user, $token, $now, $expiresAt): void {
            $delete = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
            $delete->execute(['user_id' => $user['id']]);
            $insert = $pdo->prepare('INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at, created_at) VALUES (:id, :user_id, :token_hash, :expires_at, :created_at)');
            $insert->execute([
                'id' => Security::randomToken(16), 'user_id' => $user['id'],
                'token_hash' => hash('sha256', $token), 'expires_at' => $expiresAt, 'created_at' => $now,
            ]);
        });
        return $token;
    }

    public function findByResetToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $statement = $this->pdo()->prepare($this->userSelect('u') . ' JOIN password_reset_tokens prt ON prt.user_id = u.id WHERE prt.token_hash = :hash AND prt.used_at IS NULL AND prt.expires_at > :now LIMIT 1');
        $statement->execute(['hash' => hash('sha256', $token), 'now' => Database::now()]);
        $user = $statement->fetch();
        return is_array($user) ? $this->hydrate($user) : null;
    }

    public function resetPassword(string $token, string $password): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }
        return Database::transaction($this->pdo(), function (PDO $pdo) use ($token, $password): bool {
            $suffix = Database::driver($pdo) === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $pdo->prepare('SELECT id, user_id, expires_at, used_at FROM password_reset_tokens WHERE token_hash = :hash LIMIT 1' . $suffix);
            $statement->execute(['hash' => hash('sha256', $token)]);
            $reset = $statement->fetch();
            if (!is_array($reset) || $reset['used_at'] !== null || strtotime((string) $reset['expires_at'] . ' UTC') <= time()) {
                return false;
            }
            $now = Database::now();
            $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash, password_changed_at = :now, auth_version = auth_version + 1, updated_at = :now WHERE id = :user_id');
            $update->execute(['password_hash' => Security::hashPassword($password), 'now' => $now, 'user_id' => $reset['user_id']]);
            $consume = $pdo->prepare('UPDATE password_reset_tokens SET used_at = :now WHERE id = :id AND used_at IS NULL');
            $consume->execute(['now' => $now, 'id' => $reset['id']]);
            return $consume->rowCount() === 1;
        });
    }

    public function markNotificationsRead(string $id): void
    {
        $statement = $this->pdo()->prepare('UPDATE notifications SET read_at = :now WHERE user_id = :user_id AND read_at IS NULL');
        $statement->execute(['now' => Database::now(), 'user_id' => $id]);
    }

    public function addCourse(string $id, string $courseSlug): ?array
    {
        return Database::transaction($this->pdo(), function (PDO $pdo) use ($id, $courseSlug): ?array {
            $sql = Database::driver($pdo) === 'mysql'
                ? 'INSERT IGNORE INTO enrollments (id, user_id, course_slug, order_id, status, enrolled_at) VALUES (:id, :user_id, :course_slug, NULL, :status, :enrolled_at)'
                : 'INSERT OR IGNORE INTO enrollments (id, user_id, course_slug, order_id, status, enrolled_at) VALUES (:id, :user_id, :course_slug, NULL, :status, :enrolled_at)';
            $statement = $pdo->prepare($sql);
            $now = Database::now();
            $statement->execute(['id' => Security::randomToken(16), 'user_id' => $id, 'course_slug' => $courseSlug, 'status' => 'active', 'enrolled_at' => $now]);
            if ($statement->rowCount() === 1) {
                $this->insertNotification($pdo, $id, 'ثبت‌نام دوره نهایی شد', 'دوره جدید به بخش «دوره‌های من» اضافه شد.', $now);
            }
            return $this->findByIdOn($pdo, $id);
        });
    }

    public static function publicUser(array $user): array
    {
        return array_diff_key($user, array_flip(['password_hash', 'reset_token_hash', 'reset_token_expires_at', 'auth_version']));
    }

    private function findOne(string $where, string $value): ?array
    {
        $statement = $this->pdo()->prepare($this->userSelect() . " WHERE {$where} LIMIT 1");
        $statement->execute(['value' => $value]);
        $user = $statement->fetch();
        return is_array($user) ? $this->hydrate($user) : null;
    }

    private function findByIdOn(PDO $pdo, string $id): ?array
    {
        $statement = $pdo->prepare($this->userSelect() . ' WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return is_array($user) ? $this->hydrate($user, $pdo) : null;
    }

    private function hydrate(array $user, ?PDO $pdo = null): array
    {
        $pdo ??= $this->pdo();
        $user['auth_version'] = (int) ($user['auth_version'] ?? 1);
        $user['courses'] = $this->column($pdo, 'SELECT course_slug FROM enrollments WHERE user_id = :user_id AND status IN (\'active\', \'completed\') ORDER BY enrolled_at', $user['id']);
        $user['events'] = $this->column($pdo, 'SELECT event_slug FROM event_registrations WHERE user_id = :user_id AND status <> \'canceled\' ORDER BY created_at', $user['id']);
        $certificates = $pdo->prepare('SELECT id, course_slug, certificate_number, issued_at, revoked_at FROM certificates WHERE user_id = :user_id ORDER BY issued_at DESC');
        $certificates->execute(['user_id' => $user['id']]);
        $user['certificates'] = $certificates->fetchAll() ?: [];
        $notifications = $pdo->prepare('SELECT id, title, message, created_at, read_at FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC');
        $notifications->execute(['user_id' => $user['id']]);
        $user['notifications'] = $notifications->fetchAll() ?: [];
        $reset = $pdo->prepare('SELECT token_hash, expires_at FROM password_reset_tokens WHERE user_id = :user_id AND used_at IS NULL ORDER BY created_at DESC LIMIT 1');
        $reset->execute(['user_id' => $user['id']]);
        $reset = $reset->fetch();
        $user['reset_token_hash'] = is_array($reset) ? $reset['token_hash'] : null;
        $user['reset_token_expires_at'] = is_array($reset) ? strtotime((string) $reset['expires_at'] . ' UTC') : null;
        return $user;
    }

    private function column(PDO $pdo, string $sql, string $userId): array
    {
        $statement = $pdo->prepare($sql);
        $statement->execute(['user_id' => $userId]);
        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    private function insertNotification(PDO $pdo, string $userId, string $title, string $message, string $now): void
    {
        $statement = $pdo->prepare('INSERT INTO notifications (id, user_id, title, message, created_at) VALUES (:id, :user_id, :title, :message, :created_at)');
        $statement->execute(['id' => Security::randomToken(8), 'user_id' => $userId, 'title' => $title, 'message' => $message, 'created_at' => $now]);
    }

    private function userSelect(string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $from = $alias !== '' ? 'users ' . $alias : 'users';
        return "SELECT {$prefix}id, {$prefix}name, {$prefix}email, {$prefix}password_hash, {$prefix}phone, {$prefix}professional_role AS role, {$prefix}bio, {$prefix}account_role, {$prefix}status, {$prefix}auth_version, {$prefix}email_verified_at, {$prefix}last_login_at, {$prefix}password_changed_at, {$prefix}created_at, {$prefix}updated_at FROM {$from}";
    }

    private function pdo(): PDO
    {
        return $this->database ?? Database::connection();
    }

    private function isUniqueViolation(PDOException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '19'], true);
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}

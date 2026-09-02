<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Security;
use PDO;
use PDOException;
use RuntimeException;

final class EngagementRepository
{
    public function __construct(private readonly ?PDO $database = null) {}

    public function joinCommunity(array $data, ?string $userId = null): void
    {
        $now = Database::now();
        try {
            $this->pdo()->prepare('INSERT INTO community_memberships (id,user_id,name,email,professional_role,interests,status,created_at,updated_at) VALUES (:id,:user_id,:name,:email,:role,:interests,:status,:now,:now)')
                ->execute(['id' => Security::randomToken(16), 'user_id' => $userId, 'name' => trim((string) $data['name']), 'email' => mb_strtolower(trim((string) $data['email'])), 'role' => trim((string) ($data['role'] ?? '')), 'interests' => trim((string) ($data['interests'] ?? '')), 'status' => 'pending', 'now' => $now]);
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) throw new RuntimeException('درخواست عضویت شما قبلاً ثبت شده است.');
            throw $exception;
        }
    }

    public function createContactMessage(array $data, string $ip): void
    {
        $now = Database::now();
        $this->pdo()->prepare('INSERT INTO contact_messages (id,name,email,phone,subject,message,status,ip_hash,created_at,updated_at) VALUES (:id,:name,:email,:phone,:subject,:message,:status,:ip,:now,:now)')
            ->execute(['id' => Security::randomToken(16), 'name' => trim((string) $data['name']), 'email' => mb_strtolower(trim((string) $data['email'])), 'phone' => trim((string) ($data['phone'] ?? '')), 'subject' => trim((string) $data['subject']), 'message' => trim((string) $data['message']), 'status' => 'new', 'ip' => Crypto::keyedHash($ip), 'now' => $now]);
    }

    public function registerEvent(string $slug, array $data, ?string $userId = null): void
    {
        $now = Database::now();
        try {
            $this->pdo()->prepare('INSERT INTO event_registrations (id,user_id,event_slug,applicant_name,applicant_email,applicant_phone,professional_role,status,created_at,updated_at) VALUES (:id,:user_id,:slug,:name,:email,:phone,:role,:status,:now,:now)')
                ->execute(['id' => Security::randomToken(16), 'user_id' => $userId, 'slug' => $slug, 'name' => trim((string) $data['name']), 'email' => mb_strtolower(trim((string) $data['email'])), 'phone' => trim((string) ($data['phone'] ?? '')), 'role' => trim((string) ($data['role'] ?? '')), 'status' => 'pending', 'now' => $now]);
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) throw new RuntimeException('درخواست ثبت‌نام شما قبلاً ثبت شده است.');
            throw $exception;
        }
    }

    private function pdo(): PDO { return $this->database ?? Database::connection(); }
}

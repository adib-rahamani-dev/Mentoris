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
            $this->pdo()->prepare('INSERT INTO community_memberships (id,user_id,name,email,professional_role,interests,status,created_at,updated_at) VALUES (:id,:user_id,:name,:email,:role,:interests,:status,:created_at,:updated_at)')
                ->execute(['id' => Security::randomToken(16), 'user_id' => $userId, 'name' => trim((string) $data['name']), 'email' => mb_strtolower(trim((string) $data['email'])), 'role' => trim((string) ($data['role'] ?? '')), 'interests' => trim((string) ($data['interests'] ?? '')), 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) throw new RuntimeException('درخواست عضویت شما قبلاً ثبت شده است.');
            throw $exception;
        }
    }

    public function communityForUser(array $user): ?array
    {
        $s=$this->pdo()->prepare('SELECT id,user_id,status FROM community_memberships WHERE user_id=:id OR email=:email ORDER BY created_at DESC LIMIT 1');
        $s->execute(['id'=>$user['id'],'email'=>$user['email']]); $row=$s->fetch();
        return is_array($row) && ($row['user_id']===null || $row['user_id']===$user['id']) ? $row : null;
    }
    public function setCommunity(array $user,bool $requested,string $interests=''): ?array
    {
        $old=$this->communityForUser($user);
        if ($old) {
            if ($requested && in_array($old['status'],['rejected','suspended'],true)) throw new RuntimeException('درخواست قبلی نیاز به بررسی پشتیبانی دارد.');
            $status=$requested ? ($old['status']==='withdrawn' ? 'pending' : $old['status']) : 'withdrawn';
            $this->pdo()->prepare('UPDATE community_memberships SET user_id=:user_id,status=:status,interests=:interests,updated_at=:now WHERE id=:id')->execute(['user_id'=>$user['id'],'status'=>$status,'interests'=>mb_substr($interests,0,500),'now'=>Database::now(),'id'=>$old['id']]);
        } elseif ($requested) $this->joinCommunity(['name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role'] ?? '','interests'=>$interests],$user['id']);
        return $this->communityForUser($user);
    }

    public function createContactMessage(array $data, string $ip): void
    {
        $now = Database::now();
        $this->pdo()->prepare('INSERT INTO contact_messages (id,name,email,phone,subject,message,status,ip_hash,created_at,updated_at) VALUES (:id,:name,:email,:phone,:subject,:message,:status,:ip,:created_at,:updated_at)')
            ->execute(['id' => Security::randomToken(16), 'name' => trim((string) $data['name']), 'email' => mb_strtolower(trim((string) $data['email'])), 'phone' => trim((string) ($data['phone'] ?? '')), 'subject' => trim((string) $data['subject']), 'message' => trim((string) $data['message']), 'status' => 'new', 'ip' => Crypto::keyedHash($ip), 'created_at' => $now, 'updated_at' => $now]);
    }

    public function registerEvent(string $slug, array $data, ?string $userId = null): void
    {
        $now = Database::now();
        try {
            $this->pdo()->prepare('INSERT INTO event_registrations (id,user_id,event_slug,applicant_name,applicant_email,applicant_phone,professional_role,status,created_at,updated_at) VALUES (:id,:user_id,:slug,:name,:email,:phone,:role,:status,:created_at,:updated_at)')
                ->execute(['id' => Security::randomToken(16), 'user_id' => $userId, 'slug' => $slug, 'name' => trim((string) $data['name']), 'email' => mb_strtolower(trim((string) $data['email'])), 'phone' => trim((string) ($data['phone'] ?? '')), 'role' => trim((string) ($data['role'] ?? '')), 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) throw new RuntimeException('درخواست ثبت‌نام شما قبلاً ثبت شده است.');
            throw $exception;
        }
    }

    private function pdo(): PDO { return $this->database ?? Database::connection(); }
}

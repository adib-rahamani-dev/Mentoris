<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\{Database, PhoneNumber, Security};
use PDO;
use PDOException;
use RuntimeException;
final class CourseRequestRepository
{
    public function __construct(private readonly ?PDO $database=null) {}
    private function pdo(): PDO { return $this->database ?? Database::connection(); }
    public function available(): bool
    {
        try { $this->pdo()->query('SELECT 1 FROM course_requests LIMIT 1'); return true; }
        catch(PDOException $e) { if(MemberProfileRepository::missingTable($e)) return false; throw $e; }
    }
    public function request(string $slug,array $data,?string $userId): void
    {
        try {
            $this->pdo()->prepare("INSERT INTO course_requests (id,user_id,course_slug,name,phone,payment_plan,status,created_at,updated_at) VALUES (:id,:user,:course,:name,:phone,:plan,'requested',:created,:updated)")->execute(['id'=>Security::randomToken(16),'user'=>$userId,'course'=>$slug,'name'=>$data['name'],'phone'=>PhoneNumber::normalize($data['phone']),'plan'=>$data['payment_plan'],'created'=>Database::now(),'updated'=>Database::now()]);
        } catch(PDOException $e) {
            if(in_array((string)$e->getCode(),['23000','19'],true)) throw new RuntimeException('درخواست شما برای این دوره قبلاً ثبت شده است؛ برای پیگیری با تیم منتوریس تماس بگیرید.');
            throw $e;
        }
    }
    public function userRequests(string $userId): array
    {
        if(!$this->available()) return [];
        $q=$this->pdo()->prepare('SELECT course_slug,payment_plan,status,created_at FROM course_requests WHERE user_id=:id ORDER BY created_at DESC'); $q->execute(['id'=>$userId]); return $q->fetchAll() ?: [];
    }
}

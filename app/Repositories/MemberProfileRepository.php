<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class MemberProfileRepository
{
    public function __construct(private readonly ?PDO $database = null) {}
    private function pdo(): PDO { return $this->database ?? Database::connection(); }
    public function available(): bool
    {
        try { $this->pdo()->query('SELECT user_id FROM member_profiles LIMIT 1'); return true; }
        catch (PDOException $e) { if (!self::missingTable($e)) throw $e; return false; }
    }
    public static function missingTable(PDOException $e): bool
    { return (string)$e->getCode() === '42S02' || str_contains($e->getMessage(), 'no such table'); }
    public function find(string $id): array
    {
        if (!$this->available()) return [];
        $s = $this->pdo()->prepare('SELECT * FROM member_profiles WHERE user_id = :id');
        $s->execute(['id'=>$id]); $row = $s->fetch();
        if (!$row) return [];
        return array_replace(json_decode($row['details'], true) ?: [], $row, ['training_courses'=>json_decode($row['training_courses'], true) ?: []]);
    }
    public function save(string $id, array $data, bool $complete = false, bool $acceptTerms = false): void
    {
        $columns = ['member_type','education_status','field_of_study','degree','university','city','practice_status','specialty_fields'];
        $old = $this->find($id);
        $values = ['user_id'=>$id];
        foreach ($columns as $key) $values[$key] = (string)($data[$key] ?? '');
        $details = array_diff_key($data, array_flip([...$columns,'training_courses','marketing_consent','terms_accepted_at','completed_at','updated_at','user_id','details']));
        $values += ['details'=>json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'training_courses'=>json_encode($data['training_courses'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'marketing_consent'=>!empty($data['marketing_consent']) ? 1 : 0, 'terms_accepted_at'=>$old['terms_accepted_at'] ?? ($acceptTerms ? Database::now() : null), 'completed_at'=>$complete ? Database::now() : null, 'updated_at'=>Database::now()];
        $keys = array_keys($values);
        $update = implode(', ', array_map(fn($k)=>$k . ' = ' . (Database::driver($this->pdo()) === 'mysql' ? 'VALUES('.$k.')' : 'excluded.'.$k), array_slice($keys, 1)));
        $sql = 'INSERT INTO member_profiles ('.implode(',',$keys).') VALUES (:'.implode(',:',$keys).') ' . (Database::driver($this->pdo()) === 'mysql' ? 'ON DUPLICATE KEY UPDATE ' : 'ON CONFLICT(user_id) DO UPDATE SET ') . $update;
        $this->pdo()->prepare($sql)->execute($values);
    }
    public function search(array $filters): array
    {
        $enabled = $this->available(); $where=[]; $args=[];
        foreach (['member_type','field_of_study','degree','university','city','specialty_fields','practice_status'] as $key) {
            $value = trim(is_string($filters[$key] ?? null) ? $filters[$key] : '');
            if ($value !== '' && $enabled) { $where[] = 'p.'.$key.' LIKE :'.$key; $args[$key]='%'.mb_substr($value,0,160).'%'; }
        }
        foreach (['account_role','status'] as $key) if (is_string($filters[$key] ?? null) && $filters[$key] !== '') { $where[]='u.'.$key.' = :'.$key; $args[$key]=$filters[$key]; }
        if (is_string($filters['q'] ?? null) && trim($filters['q']) !== '') {
            $where[]='(u.name LIKE :q1 OR u.email LIKE :q2 OR u.phone LIKE :q3)';
            foreach (['q1','q2','q3'] as $key) $args[$key]='%'.mb_substr(trim($filters['q']),0,120).'%';
        }
        $from=' FROM users u'.($enabled ? ' LEFT JOIN member_profiles p ON p.user_id=u.id' : '');
        $clause=$where ? ' WHERE '.implode(' AND ',$where) : '';
        $count=$this->pdo()->prepare('SELECT COUNT(*)'.$from.$clause); $count->execute($args); $total=(int)$count->fetchColumn();
        $page=max(1,min((int)($filters['page'] ?? 1), max(1,(int)ceil($total/50))));
        $select='SELECT u.id,u.name,u.email,u.phone,u.professional_role AS role,u.account_role,u.status,u.created_at';
        if ($enabled) $select.=',p.member_type,p.field_of_study,p.degree,p.university,p.city,p.practice_status,p.specialty_fields,p.completed_at';
        $s=$this->pdo()->prepare($select.$from.$clause.' ORDER BY u.created_at DESC,u.id DESC LIMIT 50 OFFSET '.(($page-1)*50)); $s->execute($args);
        $stats=['total'=>(int)$this->pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),'student'=>0,'therapist'=>0,'completed'=>0,'consented'=>0];
        if ($enabled) {
            foreach ($this->pdo()->query('SELECT member_type,COUNT(*) AS n FROM member_profiles GROUP BY member_type')->fetchAll() as $row) $stats[$row['member_type']]=(int)$row['n'];
            $stats['completed']=(int)$this->pdo()->query('SELECT COUNT(*) FROM member_profiles WHERE completed_at IS NOT NULL')->fetchColumn();
            $stats['consented']=(int)$this->pdo()->query('SELECT COUNT(*) FROM member_profiles WHERE marketing_consent=1')->fetchColumn();
        }
        return ['rows'=>$s->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/50)),'stats'=>$stats,'enabled'=>$enabled];
    }
}

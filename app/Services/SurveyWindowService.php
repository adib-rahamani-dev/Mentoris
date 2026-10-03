<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Repositories\MemberProfileRepository;
use DateTimeImmutable;
use DateTimeZone;

final class SurveyWindowService
{
    public const EVENTS=['therapists-circle-tabriz','therapists-circle-second'];
    public function status(string $slug, ?int $now=null): array
    {
        $row=null; $ready=true;
        try { $s=Database::connection()->prepare('SELECT * FROM feedback_windows WHERE event_slug=:slug'); $s->execute(['slug'=>$slug]); $row=$s->fetch() ?: null; }
        catch(\PDOException $e) { if(!MemberProfileRepository::missingTable($e)) throw $e; $ready=false; }
        $now??=time(); $start=$row ? strtotime($row['starts_at'].' UTC') : null; $end=$row ? strtotime($row['ends_at'].' UTC') : null;
        $state=!$row ? 'unconfigured' : (empty($row['enabled']) ? 'disabled' : ($now<$start ? 'upcoming' : ($now>=$end ? 'closed' : 'open')));
        $messages=['unconfigured'=>'زمان نظرسنجی هنوز توسط برگزارکننده تعیین نشده است.','disabled'=>'نظرسنجی فعلاً غیرفعال است.','upcoming'=>'نظرسنجی هنوز شروع نشده است.','closed'=>'مهلت ثبت نظر در این نظرسنجی پایان یافته است.','open'=>'نظرسنجی باز است؛ دو امتیاز و یک دقیقه از وقت شما.'];
        $zone=new DateTimeZone('Asia/Tehran');
        return ['ready'=>$ready,'row'=>$row,'state'=>$state,'open'=>$state==='open','message'=>$messages[$state],'start'=>$start,'end'=>$end,'starts_local'=>$row ? (new DateTimeImmutable($row['starts_at'],new DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d\TH:i') : '', 'ends_local'=>$row ? (new DateTimeImmutable($row['ends_at'],new DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d\TH:i') : ''];
    }
    public function save(string $slug,array $input,string $actor): array
    {
        $errors=[]; $times=[];
        if(!in_array($slug,self::EVENTS,true)) return ['event'=>['نشست معتبر انتخاب کنید.']];
        foreach(['starts_at','ends_at'] as $field) {
            $raw=is_string($input[$field] ?? null) ? $input[$field] : '';
            $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$raw,new DateTimeZone('Asia/Tehran'));
            if(!$date || $date->format('Y-m-d\TH:i')!==$raw) $errors[$field]=['تاریخ و ساعت معتبر وارد کنید.'];
            else $times[$field]=$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }
        if(!$errors && $times['ends_at']<=$times['starts_at']) $errors['ends_at']=['پایان باید بعد از شروع باشد.'];
        if($errors) return $errors;
        $pdo=Database::connection(); $suffix=Database::driver($pdo)==='mysql' ? 'ON DUPLICATE KEY UPDATE starts_at=VALUES(starts_at),ends_at=VALUES(ends_at),enabled=VALUES(enabled),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)' : 'ON CONFLICT(event_slug) DO UPDATE SET starts_at=excluded.starts_at,ends_at=excluded.ends_at,enabled=excluded.enabled,updated_by=excluded.updated_by,updated_at=excluded.updated_at';
        $pdo->prepare('INSERT INTO feedback_windows (event_slug,starts_at,ends_at,enabled,updated_by,updated_at) VALUES (:slug,:starts_at,:ends_at,:enabled,:actor,:now) '.$suffix)->execute(['slug'=>$slug,...$times,'enabled'=>($input['enabled'] ?? '')==='1' ? 1 : 0,'actor'=>$actor,'now'=>Database::now()]);
        return [];
    }
}

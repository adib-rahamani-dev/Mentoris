<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Repositories\CircleRepository;
use PDO;
use Throwable;
use RuntimeException;

final class PhoneVerificationService
{
    public const COOLDOWN = 90;
    public const EXPIRY = 300;
    public const MAX_TRIES = 5;
    public function __construct(private readonly ?PDO $database = null, private readonly SmsIrClient $client = new SmsIrClient()) {}
    private function pdo(): PDO { return $this->database ?? Database::connection(); }
    private function lock(): string { return Database::driver($this->pdo())==='mysql' ? ' FOR UPDATE' : ''; }
    public static function dayLimit(): int { return max(1,min(1000,(int)env('SMS_DAILY_LIMIT',40))); }
    public static function hourLimit(): int { return max(1,min(self::dayLimit(),(int)env('SMS_HOURLY_LIMIT',10))); }

    public function schemaReady(): bool
    {
        try { foreach(['sms_challenges','sms_deliveries','phone_verifications','rate_limits'] as $table) $this->pdo()->query('SELECT 1 FROM '.$table.' LIMIT 1'); return true; }
        catch(Throwable) { return false; }
    }

    private function user(string $id, bool $lock=false): array
    {
        $query=$this->pdo()->prepare('SELECT id,phone,status FROM users WHERE id=:id'.($lock ? $this->lock() : ''));
        $query->execute(['id'=>$id]); $user=$query->fetch();
        if(!is_array($user) || $user['status']!=='active') throw new RuntimeException('Account unavailable.');
        $user['phone']=CircleRepository::phone((string)$user['phone']);
        return $user;
    }

    public function state(string $id): array
    {
        $state=['ready'=>false,'verified'=>false,'phone'=>'','retry_after'=>0,'expires_at'=>0,'pending'=>false];
        try {
            $user=$this->user($id); $state['phone']=$user['phone'];
            if(!$this->schemaReady()) return $state;
            $state['ready']=$this->client->configured();
            $q=$this->pdo()->prepare('SELECT phone FROM phone_verifications WHERE user_id=:id'); $q->execute(['id'=>$id]);
            $state['verified']=$q->fetchColumn()===$user['phone'];
            $q=$this->pdo()->prepare('SELECT phone_hash,status,sent_at,expires_at,attempts FROM sms_challenges WHERE user_id=:id'); $q->execute(['id'=>$id]); $row=$q->fetch();
            if(is_array($row) && hash_equals($row['phone_hash'],Crypto::keyedHash($user['phone']))) {
                $state['retry_after']=max(0,strtotime($row['sent_at'].' UTC')+self::COOLDOWN-time());
                $state['expires_at']=strtotime($row['expires_at'].' UTC');
                $state['pending']=$row['status']==='sent' && (int)$row['attempts']<self::MAX_TRIES && $state['expires_at']>time();
            }
        } catch(Throwable) { /* Optional verification must never break the profile. */ }
        return $state;
    }

    public function send(string $id, string $ip): array
    {
        if(!$this->client->configured() || !$this->schemaReady()) return $this->result('تأیید پیامکی فعلاً در دسترس نیست. حساب شما فعال است.',503);
        $user=$this->user($id); $phone=$user['phone'];
        if(!preg_match('/^09[0-9]{9}$/',$phone)) return $this->result('ابتدا شمارهٔ موبایل معتبر را در اطلاعات حساب ذخیره کنید.',422);
        if($this->state($id)['verified']) return $this->result('شمارهٔ موبایل شما تأیید شده است.',200);
        $code=(string)random_int(100000,999999); $challenge=bin2hex(random_bytes(12));
        $phoneHash=Crypto::keyedHash($phone); $codeHash=Crypto::keyedHash('phone-otp|'.$id.'|'.$phone.'|'.$challenge.'|'.$code);
        $limits=[];
        foreach ([['user:'.$id,1,self::COOLDOWN],['phone:'.$phone,1,self::COOLDOWN],['user-hour:'.$id,3,3600],['phone-hour:'.$phone,3,3600],['user-day:'.$id,5,86400],['phone-day:'.$phone,5,86400],['ip-hour:'.$ip,10,3600],['ip-day:'.$ip,20,86400],['site-hour',self::hourLimit(),3600],['site-day',self::dayLimit(),86400]] as [$key,$max,$seconds]) $limits[]=['key'=>'sms:'.$key,'max'=>$max,'seconds'=>$seconds];
        try {
            $reserve=(new RateLimiter($this->pdo()))->reserveMany($limits,function(PDO $pdo) use ($id,$phone,$phoneHash,$codeHash,$challenge): void {
                if($this->user($id,true)['phone']!==$phone) throw new RuntimeException('Phone changed.');
                $claim=$pdo->prepare('SELECT user_id FROM phone_verifications WHERE phone=:phone AND user_id<>:id'); $claim->execute(['phone'=>$phone,'id'=>$id]);
                if($claim->fetchColumn()) throw new RuntimeException('Phone already claimed.');
                $values=['user'=>$id,'challenge'=>$challenge,'phone'=>$phoneHash,'code'=>$codeHash,'expires'=>gmdate('Y-m-d H:i:s',time()+self::EXPIRY),'sent'=>Database::now(),'updated'=>Database::now()];
                $sql='INSERT INTO sms_challenges (user_id,challenge_id,phone_hash,code_hash,expires_at,attempts,status,sent_at,updated_at) VALUES (:user,:challenge,:phone,:code,:expires,0,\'sending\',:sent,:updated)';
                $sql.=Database::driver($pdo)==='mysql' ? ' ON DUPLICATE KEY UPDATE challenge_id=VALUES(challenge_id),phone_hash=VALUES(phone_hash),code_hash=VALUES(code_hash),expires_at=VALUES(expires_at),attempts=0,status=\'sending\',sent_at=VALUES(sent_at),updated_at=VALUES(updated_at)' : ' ON CONFLICT(user_id) DO UPDATE SET challenge_id=excluded.challenge_id,phone_hash=excluded.phone_hash,code_hash=excluded.code_hash,expires_at=excluded.expires_at,attempts=0,status=\'sending\',sent_at=excluded.sent_at,updated_at=excluded.updated_at';
                $pdo->prepare($sql)->execute($values);
                $pdo->prepare('INSERT INTO sms_deliveries (id,user_id,phone_hash,kind,status,created_at,updated_at) VALUES (:id,:user,:phone,\'phone_otp\',\'sending\',:created,:updated)')->execute(['id'=>$challenge,'user'=>$id,'phone'=>$phoneHash,'created'=>Database::now(),'updated'=>Database::now()]);
            });
        } catch(Throwable) { return $this->result('ارسال کد ممکن نیست. شمارهٔ حساب را بررسی کنید یا کمی بعد تلاش کنید.',503); }
        if(!$reserve['allowed']) return $this->result('به سقف ارسال رسیده‌اید. کمی بعد دوباره تلاش کنید.',429,(int)$reserve['retry_after']);
        // Outside the retried DB transaction: at most one provider call per reservation.
        try { $delivery=$this->client->sendCode($phone,$code); } catch(Throwable) { $delivery=['status'=>'unknown','provider_code'=>null]; }
        try {
            Database::transaction($this->pdo(),function(PDO $pdo) use ($challenge,$delivery): void {
                $pdo->prepare('UPDATE sms_deliveries SET status=:status,provider_message_id=:message,provider_code=:code,cost=:cost,updated_at=:updated WHERE id=:id')->execute(['status'=>$delivery['status'],'message'=>$delivery['message_id'] ?? null,'code'=>$delivery['provider_code'],'cost'=>$delivery['cost'] ?? null,'updated'=>Database::now(),'id'=>$challenge]);
                $pdo->prepare('UPDATE sms_challenges SET status=:status,updated_at=:updated WHERE challenge_id=:id AND status=\'sending\'')->execute(['status'=>$delivery['status'],'updated'=>Database::now(),'id'=>$challenge]);
            });
        } catch(Throwable) { return $this->result('پاسخ ارسال هنوز قابل تأیید نیست. کمی بعد دوباره تلاش کنید.',503,self::COOLDOWN); }
        return $delivery['status']==='sent' ? $this->result('کد تأیید ارسال شد؛ تا ۵ دقیقه معتبر است.',200,self::COOLDOWN) : $this->result('ارسال پیامک تأیید نشد. کمی بعد دوباره تلاش کنید.',503,self::COOLDOWN);
    }

    public function verify(string $id, string $input): array
    {
        if(!$this->schemaReady()) return $this->result('تأیید پیامکی فعلاً در دسترس نیست.',503);
        $code=CircleRepository::phone($input);
        try {
            return Database::transaction($this->pdo(),function(PDO $pdo) use ($id,$code): array {
                $user=$this->user($id,true);
                $q=$pdo->prepare('SELECT * FROM sms_challenges WHERE user_id=:id'.$this->lock()); $q->execute(['id'=>$id]); $row=$q->fetch();
                if(!is_array($row) || $row['status']!=='sent') return $this->result('ابتدا یک کد جدید درخواست کنید.',422);
                if($row['attempts']>=self::MAX_TRIES || strtotime($row['expires_at'].' UTC')<=time() || !hash_equals($row['phone_hash'],Crypto::keyedHash($user['phone']))) {
                    $pdo->prepare('UPDATE sms_challenges SET status=\'expired\',code_hash=\'\',updated_at=:now WHERE user_id=:id')->execute(['id'=>$id,'now'=>Database::now()]);
                    return $this->result('کد منقضی شده یا شماره تغییر کرده است؛ کد جدید بگیرید.',422);
                }
                $hash=Crypto::keyedHash('phone-otp|'.$id.'|'.$user['phone'].'|'.$row['challenge_id'].'|'.$code);
                if(!preg_match('/^[0-9]{6}$/',$code) || !hash_equals($row['code_hash'],$hash)) {
                    $attempts=(int)$row['attempts']+1; $exhausted=$attempts>=self::MAX_TRIES;
                    $pdo->prepare('UPDATE sms_challenges SET attempts=:tries,status=:status,code_hash=:hash,updated_at=:now WHERE user_id=:id')->execute(['tries'=>$attempts,'status'=>$exhausted ? 'exhausted' : 'sent','hash'=>$exhausted ? '' : $row['code_hash'],'id'=>$id,'now'=>Database::now()]);
                    return $this->result($exhausted ? 'تعداد تلاش‌ها تمام شد؛ کد جدید درخواست کنید.' : 'کد صحیح نیست. دوباره بررسی کنید.',422);
                }
                $sql='INSERT INTO phone_verifications (user_id,phone,verified_at) VALUES (:id,:phone,:now)';
                // Update only this user's row; a phone claimed by another account is rejected.
                if(Database::driver($pdo)==='mysql') {
                    $pdo->prepare('DELETE FROM phone_verifications WHERE user_id=:id')->execute(['id'=>$id]);
                } else $sql.=' ON CONFLICT(user_id) DO UPDATE SET phone=excluded.phone,verified_at=excluded.verified_at';
                $pdo->prepare($sql)->execute(['id'=>$id,'phone'=>$user['phone'],'now'=>Database::now()]);
                $pdo->prepare('UPDATE sms_challenges SET status=\'consumed\',code_hash=\'\',updated_at=:now WHERE user_id=:id')->execute(['id'=>$id,'now'=>Database::now()]);
                return $this->result('شمارهٔ موبایل شما با موفقیت تأیید شد.',200);
            });
        } catch(Throwable) { return $this->result('تأیید شماره انجام نشد. اطلاعات حساب را بررسی کنید.',422); }
    }

    private function result(string $message,int $status,int $retry=0): array { return ['message'=>$message,'status'=>$status,'retry_after'=>$retry]; }

    public function report(): array
    {
        $ready=$this->schemaReady(); $report=['schema_ready'=>$ready,'configured'=>$this->client->configured(),'day_limit'=>self::dayLimit(),'hour_limit'=>self::hourLimit(),'stats'=>[],'deliveries'=>[],'verified'=>0,'budget'=>[]];
        if(!$ready) return $report;
        $q=$this->pdo()->prepare('SELECT status,COUNT(*) AS total FROM sms_deliveries WHERE created_at>=:start GROUP BY status'); $q->execute(['start'=>gmdate('Y-m-d H:i:s',time()-86400)]); $report['stats']=$q->fetchAll();
        $report['deliveries']=$this->pdo()->query('SELECT d.id,d.status,d.provider_message_id,d.provider_code,d.cost,d.created_at,u.name,u.phone FROM sms_deliveries d LEFT JOIN users u ON u.id=d.user_id ORDER BY d.created_at DESC LIMIT 50')->fetchAll();
        $report['verified']=(int)$this->pdo()->query('SELECT COUNT(*) FROM phone_verifications p JOIN users u ON u.id=p.user_id WHERE u.phone=p.phone')->fetchColumn();
        foreach(['site-hour'=>self::hourLimit(),'site-day'=>self::dayLimit()] as $key=>$max) {
            $q=$this->pdo()->prepare('SELECT hits,reset_at FROM rate_limits WHERE key_hash=:hash'); $q->execute(['hash'=>Crypto::keyedHash('sms:'.$key)]); $row=$q->fetch();
            $report['budget'][$key]=['hits'=>is_array($row) && strtotime($row['reset_at'].' UTC')>time() ? (int)$row['hits'] : 0,'max'=>$max,'reset_at'=>$row['reset_at'] ?? null];
        }
        return $report;
    }
}

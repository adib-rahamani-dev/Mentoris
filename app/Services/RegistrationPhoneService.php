<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Crypto, Database, PhoneNumber, RateLimiter, Session};
use PDO;
use RuntimeException;
use Throwable;

final class RegistrationPhoneService
{
    public function __construct(private readonly ?PDO $database = null, private readonly SmsIrClient $client = new SmsIrClient()) {}
    private function pdo(): PDO { return $this->database ?? Database::connection(); }
    public static function required(): bool { return (bool)env('SMS_REGISTRATION_REQUIRED', true); }
    public static function context(): string
    {
        $session=new Session(); $context=$session->get('registration.phone.context');
        if(!is_string($context) || !preg_match('/^[a-f0-9]{64}$/',$context)) { $context=bin2hex(random_bytes(32)); $session->put('registration.phone.context',$context); }
        return $context;
    }
    public function schemaReady(): bool
    {
        try { foreach(['registration_sms_challenges','sms_deliveries','phone_verifications','rate_limits'] as $table) $this->pdo()->query('SELECT 1 FROM '.$table.' LIMIT 1'); return true; }
        catch(Throwable) { return false; }
    }
    private static function result(string $message,int $status,int $retry=0): array { return ['message'=>$message,'status'=>$status,'retry_after'=>$retry]; }

    public function send(string $context,string $input,string $ip): array
    {
        $phone=PhoneNumber::normalize($input);
        if(!preg_match('/^09[0-9]{9}$/',$phone)) return self::result('شماره موبایل معتبر وارد کنید.',422);
        if(!$this->schemaReady() || !$this->client->configured()) return self::result('ارسال کد ثبت‌نام فعلاً در دسترس نیست. کمی بعد دوباره تلاش کنید.',503);
        $sessionHash=Crypto::keyedHash('registration-session|'.$context); $phoneHash=Crypto::keyedHash($phone);
        $challenge=bin2hex(random_bytes(12)); $code=(string)random_int(100000,999999);
        $codeHash=Crypto::keyedHash('registration-otp|'.$sessionHash.'|'.$phone.'|'.$challenge.'|'.$code);
        $limits=[];
        foreach([['registration-session:'.$sessionHash,1,90],['phone:'.$phone,1,90],['phone-hour:'.$phone,3,3600],['phone-day:'.$phone,5,86400],['ip-hour:'.$ip,10,3600],['ip-day:'.$ip,20,86400],['site-hour',PhoneVerificationService::hourLimit(),3600],['site-day',PhoneVerificationService::dayLimit(),86400]] as [$key,$max,$seconds]) $limits[]=['key'=>'sms:'.$key,'max'=>$max,'seconds'=>$seconds];
        try {
            $reserve=(new RateLimiter($this->pdo()))->reserveMany($limits,function(PDO $pdo) use($sessionHash,$phoneHash,$challenge,$codeHash): void {
                $values=['session'=>$sessionHash,'phone'=>$phoneHash,'challenge'=>$challenge,'code'=>$codeHash,'expires'=>gmdate('Y-m-d H:i:s',time()+300),'sent'=>Database::now(),'updated'=>Database::now()];
                $sql="INSERT INTO registration_sms_challenges (session_hash,phone_hash,challenge_id,code_hash,expires_at,attempts,status,sent_at,updated_at) VALUES (:session,:phone,:challenge,:code,:expires,0,'sending',:sent,:updated)";
                $sql.=Database::driver($pdo)==='mysql' ? " ON DUPLICATE KEY UPDATE phone_hash=VALUES(phone_hash),challenge_id=VALUES(challenge_id),code_hash=VALUES(code_hash),expires_at=VALUES(expires_at),attempts=0,status='sending',sent_at=VALUES(sent_at),updated_at=VALUES(updated_at)" : " ON CONFLICT(session_hash) DO UPDATE SET phone_hash=excluded.phone_hash,challenge_id=excluded.challenge_id,code_hash=excluded.code_hash,expires_at=excluded.expires_at,attempts=0,status='sending',sent_at=excluded.sent_at,updated_at=excluded.updated_at";
                $pdo->prepare($sql)->execute($values);
                $pdo->prepare("INSERT INTO sms_deliveries (id,user_id,phone_hash,kind,status,created_at,updated_at) VALUES (:id,NULL,:phone,'registration_otp','sending',:created,:updated)")->execute(['id'=>$challenge,'phone'=>$phoneHash,'created'=>Database::now(),'updated'=>Database::now()]);
            });
        } catch(Throwable) { return self::result('ارسال کد ممکن نیست. کمی بعد دوباره تلاش کنید.',503); }
        if(!$reserve['allowed']) return self::result('به سقف ارسال رسیده‌اید. کمی بعد دوباره تلاش کنید.',429,(int)$reserve['retry_after']);
        try { $delivery=$this->client->sendCode($phone,$code); } catch(Throwable) { $delivery=['status'=>'unknown','provider_code'=>null]; }
        try {
            Database::transaction($this->pdo(),static function(PDO $pdo) use($challenge,$delivery): void {
                $pdo->prepare('UPDATE sms_deliveries SET status=:status,provider_message_id=:message,provider_code=:code,cost=:cost,updated_at=:updated WHERE id=:id')->execute(['status'=>$delivery['status'],'message'=>$delivery['message_id'] ?? null,'code'=>$delivery['provider_code'],'cost'=>$delivery['cost'] ?? null,'updated'=>Database::now(),'id'=>$challenge]);
                $pdo->prepare("UPDATE registration_sms_challenges SET status=:status,updated_at=:now WHERE challenge_id=:id AND status='sending'")->execute(['status'=>$delivery['status'],'now'=>Database::now(),'id'=>$challenge]);
            });
        } catch(Throwable) { return self::result('نتیجهٔ ارسال قابل تأیید نیست. کمی بعد دوباره تلاش کنید.',503,90); }
        return $delivery['status']==='sent' ? self::result('کد به شمارهٔ واردشده ارسال شد؛ تا ۵ دقیقه معتبر است.',200,90) : self::result('ارسال پیامک تأیید نشد. کمی بعد دوباره تلاش کنید.',503,90);
    }

    public function verify(string $context,string $input,string $inputCode): array
    {
        if(!$this->schemaReady()) return self::result('تأیید موبایل فعلاً در دسترس نیست.',503);
        $phone=PhoneNumber::normalize($input);
        // Only digit conversion: country-prefix normalization must not apply to an OTP.
        $code=strtr(trim($inputCode),array_combine(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9']));
        $sessionHash=Crypto::keyedHash('registration-session|'.$context);
        try {
            return Database::transaction($this->pdo(),static function(PDO $pdo) use($sessionHash,$phone,$code): array {
                $q=$pdo->prepare('SELECT * FROM registration_sms_challenges WHERE session_hash=:session'.(Database::driver($pdo)==='mysql' ? ' FOR UPDATE' : '')); $q->execute(['session'=>$sessionHash]); $row=$q->fetch();
                if(!is_array($row) || !in_array($row['status'],['sent','verified'],true) || !hash_equals($row['phone_hash'],Crypto::keyedHash($phone))) return self::result('ابتدا برای همین شماره کد تأیید بگیرید.',422);
                if(strtotime($row['expires_at'].' UTC')<=time() || (int)$row['attempts']>=5) return self::result('کد منقضی شده است؛ کد جدید بگیرید.',422);
                if($row['status']==='verified') return self::result('شماره تأیید شد.',200);
                $hash=Crypto::keyedHash('registration-otp|'.$sessionHash.'|'.$phone.'|'.$row['challenge_id'].'|'.$code);
                if(!preg_match('/^[0-9]{6}$/',$code) || !hash_equals($row['code_hash'],$hash)) {
                    $tries=(int)$row['attempts']+1;
                    $pdo->prepare('UPDATE registration_sms_challenges SET attempts=:tries,status=:status,code_hash=:code,updated_at=:now WHERE session_hash=:session')->execute(['tries'=>$tries,'status'=>$tries>=5 ? 'exhausted' : 'sent','code'=>$tries>=5 ? '' : $row['code_hash'],'now'=>Database::now(),'session'=>$sessionHash]);
                    return self::result($tries>=5 ? 'تعداد تلاش‌ها تمام شد؛ کد جدید بگیرید.' : 'کد تأیید صحیح نیست.',422);
                }
                $pdo->prepare("UPDATE registration_sms_challenges SET status='verified',code_hash='',updated_at=:now WHERE session_hash=:session")->execute(['now'=>Database::now(),'session'=>$sessionHash]);
                return self::result('شماره تأیید شد.',200);
            });
        } catch(Throwable) { return self::result('تأیید موبایل انجام نشد. دوباره تلاش کنید.',503); }
    }

    /** Called after inserting the user, within the SAME account transaction; failures roll everything back. */
    public static function consume(PDO $pdo,string $context,string $phone,string $userId): void
    {
        $sessionHash=Crypto::keyedHash('registration-session|'.$context);
        $q=$pdo->prepare('SELECT phone_hash,status,expires_at,challenge_id FROM registration_sms_challenges WHERE session_hash=:session'.(Database::driver($pdo)==='mysql' ? ' FOR UPDATE' : '')); $q->execute(['session'=>$sessionHash]); $row=$q->fetch();
        if(!is_array($row) || $row['status']!=='verified' || strtotime($row['expires_at'].' UTC')<=time() || !hash_equals($row['phone_hash'],Crypto::keyedHash($phone))) throw new RuntimeException('شماره موبایل را با کد پیامکی تأیید کنید.',409);
        $pdo->prepare('INSERT INTO phone_verifications (user_id,phone,verified_at) VALUES (:id,:phone,:now)')->execute(['id'=>$userId,'phone'=>$phone,'now'=>Database::now()]);
        $pdo->prepare("UPDATE registration_sms_challenges SET status='consumed',code_hash='',updated_at=:now WHERE session_hash=:session")->execute(['now'=>Database::now(),'session'=>$sessionHash]);
        $pdo->prepare('UPDATE sms_deliveries SET user_id=:user WHERE id=:id')->execute(['user'=>$userId,'id'=>$row['challenge_id']]);
    }
}

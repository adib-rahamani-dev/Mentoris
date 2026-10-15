<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,Security,Authorization,Audit};
use App\Repositories\{CommunicationRepository,MemberProfileRepository};
use RuntimeException;
final class PaymentLifecycleService
{
    public const REFUND_POLICY='در صورت عدم تأیید پذیرش، مبلغ پرداخت‌شده پس از تأیید واریز، با هماهنگی پشتیبانی به حساب پرداخت‌کننده بازگردانده می‌شود. پرداخت به‌تنهایی به معنی پذیرش قطعی نیست.';
    public function __construct(private readonly CommunicationRepository $hub=new CommunicationRepository()){}
    public function available(): bool
    {
        try{$this->hub->pdo()->query('SELECT id FROM payment_refunds LIMIT 0');return true;}
        catch(\PDOException $e){if(MemberProfileRepository::missingTable($e))return false;throw $e;}
    }
    public function refunds(string $type,string $id): array
    {
        return $this->available()?$this->hub->rows('SELECT r.*,p.part FROM payment_refunds r JOIN manual_payments p ON p.id=r.payment_id WHERE p.request_type=:type AND p.request_id=:id ORDER BY p.part',['type'=>$type,'id'=>$id]):[];
    }
    public function reviewReady(string $type,string $id): bool
    {
        return in_array($type,['circle','courses'],true) && (new ManualPaymentService($this->hub))->available() && $this->hub->one("SELECT id FROM manual_payments WHERE request_type=:type AND request_id=:id AND part=1 AND status='approved' LIMIT 1",['type'=>$type,'id'=>$id])!==null;
    }
    public function requirePayment(string $type,string $id,string $status): void
    {
        if($status==='approved' && in_array($type,['circle','courses'],true) && !$this->reviewReady($type,$id))throw new RuntimeException('ابتدا واریز بخش اول را از صفحه رسید تأیید کنید؛ درخواست بدون پرداخت قابل پذیرش نیست.');
    }
    public function sync(string $type,string $id): void
    {
        if(!in_array($type,['circle','courses'],true) || !(new ManualPaymentService($this->hub))->available())return;
        Database::transaction($this->hub->pdo(),function()use($type,$id):void{
            $table=$type==='circle'?'event_signups':'course_requests';$lock=Database::driver($this->hub->pdo())==='mysql'?' FOR UPDATE':'';
            $request=$this->hub->one("SELECT * FROM $table WHERE id=:id".$lock,['id'=>$id]);if(!$request)return;
            $paid=$this->hub->rows("SELECT * FROM manual_payments WHERE request_type=:type AND request_id=:id AND status='approved'",['type'=>$type,'id'=>$id]);
            if(in_array($request['status'],['rejected','canceled'],true) && $paid){
                if(!$this->available())throw new RuntimeException('پیش از رد درخواست دارای واریز، SQL شماره ۰۱۴ را برای پیگیری بازگشت وجه اجرا کنید.');
                foreach($paid as $payment){
                    $insert=Database::driver($this->hub->pdo())==='sqlite'?'INSERT OR IGNORE':'INSERT IGNORE';
                    $created=$this->hub->execute("$insert INTO payment_refunds (id,payment_id,user_id,amount,status,reason,created_at,updated_at) VALUES (:id,:payment,:user,:amount,'pending',:reason,:created,:updated)",['id'=>Security::randomToken(16),'payment'=>$payment['id'],'user'=>$payment['user_id'],'amount'=>$payment['amount'],'reason'=>'عدم تأیید یا لغو پذیرش','created'=>Database::now(),'updated'=>Database::now()]);
                    if($created===1)$this->notify($payment,'pending');
                }
                $slug=$request['event_slug']??$request['course_slug'];
                $this->hub->execute($type==='circle'?"UPDATE event_registrations SET status='canceled' WHERE user_id=:user AND event_slug=:slug":"UPDATE enrollments SET status='canceled' WHERE user_id=:user AND course_slug=:slug",['user'=>$request['user_id'],'slug'=>$slug]);return;
            }
            if($request['status']!=='approved')return;
            if($this->refunds($type,$id))throw new RuntimeException('این درخواست پرونده بازگشت وجه دارد؛ برای پذیرش دوباره، درخواست جدید با پشتیبانی هماهنگ شود.');
            foreach($paid as $payment){if((int)$payment['part']!==1)continue;
                $existing=$this->hub->one($type==='circle'?'SELECT id FROM event_registrations WHERE user_id=:user AND event_slug=:slug LIMIT 1':'SELECT id FROM enrollments WHERE user_id=:user AND course_slug=:slug LIMIT 1',['user'=>$payment['user_id'],'slug'=>$payment['item_slug']]);if($existing)continue;
                $insert=Database::driver($this->hub->pdo())==='sqlite'?'INSERT OR IGNORE':'INSERT IGNORE';
                $member=$this->hub->one('SELECT name,email,phone FROM users WHERE id=:id',['id'=>$payment['user_id']]);if(!$member)continue;
                if($type==='circle')$this->hub->execute("$insert INTO event_registrations (id,user_id,event_slug,applicant_name,applicant_email,applicant_phone,status,created_at,updated_at) VALUES (:id,:user,:slug,:name,:email,:phone,'approved',:created,:updated)",['id'=>Security::randomToken(16),'user'=>$payment['user_id'],'slug'=>$payment['item_slug'],'name'=>$member['name'],'email'=>$member['email']?:$payment['user_id'].'@members.mentoris.invalid','phone'=>$member['phone']??'','created'=>Database::now(),'updated'=>Database::now()]);
                else $this->hub->execute("$insert INTO enrollments (id,user_id,course_slug,status,enrolled_at) VALUES (:id,:user,:slug,'active',:at)",['id'=>Security::randomToken(16),'user'=>$payment['user_id'],'slug'=>$payment['item_slug'],'at'=>Database::now()]);
            }
        });
    }
    public function complete(string $paymentId,array $admin,string $reference,string $note,bool $confirmed): void
    {
        if(!Authorization::can($admin,'payments.manage'))throw new RuntimeException('دسترسی مجاز نیست.');
        $reference=trim($reference);if(!$confirmed || mb_strlen($reference)<4 || mb_strlen($reference)>120 || mb_strlen($note)>1000)throw new RuntimeException('پس از واریز واقعی، شماره پیگیری معتبر و تأیید انجام واریز را ثبت کنید.');
        Database::transaction($this->hub->pdo(),function()use($paymentId,$admin,$reference,$note):void{
            $payment=(new ManualPaymentService($this->hub))->find($paymentId);if(!$payment || !$this->available())throw new RuntimeException('پرونده بازگشت وجه در دسترس نیست.');
            $table=$payment['request_type']==='circle'?'event_signups':'course_requests';$lock=Database::driver($this->hub->pdo())==='mysql'?' FOR UPDATE':'';
            $request=$this->hub->one("SELECT status FROM $table WHERE id=:id".$lock,['id'=>$payment['request_id']]);
            if(!$request || !in_array($request['status'],['rejected','canceled'],true))throw new RuntimeException('وضعیت پذیرش تغییر کرده است؛ صفحه را تازه کنید.');
            if($this->hub->execute("UPDATE payment_refunds SET status='refunded',transfer_reference=:ref,note=:note,completed_by=:admin,completed_at=:at,updated_at=:updated WHERE payment_id=:payment AND status='pending'",['ref'=>$reference,'note'=>trim($note),'admin'=>$admin['id'],'at'=>Database::now(),'updated'=>Database::now(),'payment'=>$paymentId])!==1)throw new RuntimeException('بازگشت وجه قبلاً ثبت شده یا پرونده‌ای وجود ندارد.');
            Audit::record('payment.refunded','manual_payment',$paymentId,$admin['id'],['status'=>'pending'],['amount'=>$payment['amount'],'transfer_reference'=>$reference],'manual-refund');$this->notify($payment,'refunded',$reference);
        });
    }
    private function notify(array $payment,string $status,string $reference=''): void
    {
        $text=$payment['item_title']."\nمبلغ: ".number_format((int)$payment['amount']).' تومان' ."\n".($status==='pending'?'پذیرش تأیید نشد؛ بازگشت مبلغ واریزشده در انتظار اقدام تیم است.':'بازگشت وجه توسط مدیر ثبت شد؛ شماره پیگیری: '.$reference);
        (new NotificationService())->panel(['id'=>$payment['user_id']],'بازگشت وجه',$text);
        if(!$this->hub->available())return;
        $this->hub->notifyUser($payment['user_id'],'بازگشت وجه',$text."\nپیگیری: ".rtrim((string)env('APP_URL'),'/').'/payments/'.$payment['request_type'].'/'.$payment['request_id'],'refund:'.$payment['id'].':'.$status);
        $member=$this->hub->one('SELECT name,phone FROM users WHERE id=:id',['id'=>$payment['user_id']]);
        foreach($this->hub->rows('SELECT * FROM comm_accounts WHERE admin_alerts=1 AND notifications_enabled=1') as $account){$admin=$this->hub->linkedUser($account);if(!$admin || $account['link_method']!=='site' || !Authorization::can($admin,'payments.manage'))continue;$admin['__permission']='payments.manage';
            $this->hub->message($account,($status==='pending'?'🔴 بازگشت وجه نیازمند اقدام':'✅ بازگشت وجه ثبت شد')."\nعضو: ".($member['name']??'')."\nشماره: ".($member['phone']??'')."\n".$text,['inline_keyboard'=>[[['text'=>'پرونده رسید و بازگشت وجه','url'=>rtrim((string)env('APP_URL'),'/').'/admin/manual-payments/'.$payment['id']]]]],'refund-admin:'.$payment['id'].':'.$status,$admin);
        }
    }
}

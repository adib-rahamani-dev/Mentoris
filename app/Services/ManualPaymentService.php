<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,Security,Authorization,Audit};
use App\Repositories\{CommunicationRepository,MemberProfileRepository};
use RuntimeException;

final class ManualPaymentService
{
    public const CARD='5859831243559068';
    public function __construct(private readonly CommunicationRepository $hub=new CommunicationRepository()) {}
    public static function profileReady(?array $user): bool
    {
        if(!$user)return false;
        $profile=(new MemberProfileRepository())->find($user['id']);
        return !empty($profile['completed_at']) && MemberProfileService::validate($profile,true)[1]===[];
    }
    public function available(): bool
    {
        try{$this->hub->pdo()->query('SELECT id FROM manual_payments LIMIT 0');return true;}
        catch(\PDOException $e){if(MemberProfileRepository::missingTable($e))return false;throw $e;}
    }
    public static function enrollmentReady(?array $user,string $type): bool
    {
        return $user!==null && ($type==='courses' || ($type==='circle' && self::profileReady($user)));
    }
    public function context(string $type,string $id,array $user): array
    {
        $table=['circle'=>'event_signups','courses'=>'course_requests'][$type]??null;
        if(!$table)throw new RuntimeException('درخواست معتبر نیست.');
        $request=$this->hub->one("SELECT * FROM $table WHERE id=:id AND user_id=:user",['id'=>$id,'user'=>$user['id']]);
        if(!$request)throw new RuntimeException('درخواست در دسترس نیست.');
        $slug=$request['event_slug']??$request['course_slug'];
        $item=$type==='circle'?PublicContentService::event($slug):PublicContentService::course($slug);
        if(!$item)throw new RuntimeException('این برنامه در دسترس نیست.');
        $payable=$type!=='circle' || !in_array($item['status'],['completed','canceled'],true);
        $parts=$type==='courses' && $request['payment_plan']==='installments'?2:1;
        $total=(int)$item['price_amount'];
        $rows=$this->available()?$this->hub->rows('SELECT * FROM manual_payments WHERE request_type=:type AND request_id=:id ORDER BY part',['type'=>$type,'id'=>$id]):[];
        if($rows)$total=(int)$rows[0]['amount']*$parts;
        $discounts=new DiscountCodeService($this->hub);$couponReady=$discounts->available();$discount=$discounts->redemption($type,$id);
        $originalTotal=$discount?(int)$discount['original_amount']:$total;
        if($discount)$total=(int)$discount['final_amount'];
        $lifecycle=new PaymentLifecycleService($this->hub);$refundReady=$lifecycle->available();$refunds=$lifecycle->refunds($type,$id);
        $payable=$payable && in_array($request['status'],['requested','approved'],true) && ($request['status']==='approved' || $refundReady) && !$refunds;
        return compact('request','item','parts','total','originalTotal','rows','type','id','payable','discount','couponReady','refundReady','refunds');
    }
    public function submit(array $context,array $user,int $part,array $file): array
    {
        if(!self::enrollmentReady($user,$context['type']))throw new RuntimeException('ابتدا پروفایل خود را تکمیل کنید.');
        if(!$this->available())throw new RuntimeException('بخش پرداخت در حال آماده‌سازی است.');
        if(($context['payable']??true)===false)throw new RuntimeException('پرداخت برای این حلقه پایان‌یافته یا لغوشده فعال نیست.');
        if(!in_array($context['request']['status'],['requested','approved'],true))throw new RuntimeException('این درخواست قابل پرداخت نیست.');
        if($context['request']['status']==='requested' && !(new PaymentLifecycleService($this->hub))->available())throw new RuntimeException('پرداخت پیش از پذیرش پس از اجرای SQL شماره ۰۱۴ فعال می‌شود.');
        if($context['total']<=0 || $part<1 || $part>$context['parts'])throw new RuntimeException('مبلغ یا مرحله پرداخت معتبر نیست.');
        $mime=$file['mime_type']??'';
        if(!in_array($mime,['image/jpeg','image/png'],true))throw new RuntimeException('رسید باید تصویر JPG یا PNG باشد.');
        return Database::transaction($this->hub->pdo(),function() use($context,$user,$part,$file){
            $table=$context['type']==='circle'?'event_signups':'course_requests';
            $lock=Database::driver($this->hub->pdo())==='mysql'?' FOR UPDATE':'';
            $accepted=$this->hub->one("SELECT status FROM $table WHERE id=:id AND user_id=:user".$lock,['id'=>$context['id'],'user'=>$user['id']]);
            if(!$accepted || !in_array($accepted['status'],['requested','approved'],true) || (new PaymentLifecycleService($this->hub))->refunds($context['type'],$context['id']))throw new RuntimeException('درخواست پذیرش تغییر کرده است؛ وضعیت را دوباره بررسی کنید.');
            if($accepted['status']==='requested' && !(new PaymentLifecycleService($this->hub))->available())throw new RuntimeException('ابتدا SQL شماره ۰۱۴ اجرا شود.');
            if($part===2 && $accepted['status']!=='approved')throw new RuntimeException('پرداخت نیمه دوم پس از تأیید پذیرش فعال می‌شود.');
            $discount=(new DiscountCodeService($this->hub))->redemption($context['type'],$context['id']);
            if(($discount && (int)$discount['final_amount']!==$context['total']) || (!$discount && !empty($context['discount'])))throw new RuntimeException('مبلغ تخفیف تغییر کرده است؛ صفحه پرداخت را تازه کنید.');
            if($part===2 && !$this->hub->one("SELECT id FROM manual_payments WHERE request_type=:type AND request_id=:id AND part=1 AND status='approved'",['type'=>$context['type'],'id'=>$context['id']]))throw new RuntimeException('ابتدا پرداخت بخش اول باید تأیید شود.');
            $old=$this->hub->one('SELECT * FROM manual_payments WHERE request_type=:type AND request_id=:id AND part=:part',['type'=>$context['type'],'id'=>$context['id'],'part'=>$part]);
            if($old && $old['status']!=='rejected')throw new RuntimeException('رسید این بخش قبلاً ثبت شده؛ منتظر بررسی بمانید.');
            $id=$old['id']??Security::randomToken(16);$amount=$context['parts']===1?$context['total']:($part===1?(int)ceil($context['total']/2):(int)floor($context['total']/2));
            $params=['id'=>$id,'file'=>json_encode($file,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'now'=>Database::now()];
            if($old){
                if($this->hub->execute("UPDATE manual_payments SET receipt=:file,version=version+1,status='pending',review_note='',reviewed_by=NULL,reviewed_at=NULL,updated_at=:now WHERE id=:id AND status='rejected' AND version=:version",$params+['version'=>$old['version']])!==1)throw new RuntimeException('رسید هم‌زمان تغییر کرد.');
            }else $this->hub->execute('INSERT INTO manual_payments (id,user_id,request_type,request_id,item_slug,item_title,part,amount,receipt,created_at,updated_at) VALUES (:id,:user,:type,:request,:slug,:title,:part,:amount,:file,:created,:now)',$params+['user'=>$user['id'],'type'=>$context['type'],'request'=>$context['id'],'slug'=>$context['item']['slug'],'title'=>$context['item']['title'],'part'=>$part,'amount'=>$amount,'created'=>Database::now()]);
            $row=$this->find($id);$this->notify($row);return $row;
        });
    }
    public function find(string $id): ?array {return $this->available()?$this->hub->one('SELECT * FROM manual_payments WHERE id=:id',['id'=>$id]):null;}
    public function receipt(array $row): array {return json_decode($row['receipt'],true,512,JSON_THROW_ON_ERROR);}
    public function notify(array $row): void
    {
        $notifications=new NotificationService();
        $discount=(new DiscountCodeService($this->hub))->redemption($row['request_type'],$row['request_id']);
        $discountText=$discount?"\nکد تخفیف: ".$discount['code'].' | تخفیف کل: '.number_format((int)$discount['discount_amount']).' تومان':'';
        $member=$this->hub->one('SELECT name,phone FROM users WHERE id=:id',['id'=>$row['user_id']]);
        foreach($this->hub->rows("SELECT id,account_role,status FROM users WHERE status='active' AND account_role IN ('admin','super_admin')") as $admin){
            if(Authorization::can($admin,'payments.manage'))$notifications->panel($admin,'رسید پرداخت تازه',($member['name']??'عضو').'؛ '.$row['item_title'].' | بخش '.$row['part'].' | '.number_format((int)$row['amount']).' تومان؛ آماده بررسی در پنل رسیدها.'.$discountText);
        }
        $notifications->panel(['id'=>$row['user_id']],'رسید پرداخت دریافت شد',$row['item_title'].'؛ رسید بخش '.$row['part'].' ثبت شد و منتظر بررسی مدیر است. دوباره واریز نکنید.');
        if(!$this->hub->available())return;
        foreach($this->hub->rows('SELECT * FROM comm_accounts WHERE admin_alerts=1 AND notifications_enabled=1') as $account){
            $admin=$this->hub->linkedUser($account);
            if(!$admin || $account['link_method']!=='site' || !Authorization::can($admin,'payments.manage'))continue;
            $admin['__permission']='payments.manage';$key='manual:'.$row['id'].':'.$row['version'];
            $this->hub->queue($account['channel'],$account['chat_id'],'_manual_receipt',['payment_id'=>$row['id'],'version'=>$row['version']],$key.':image',$admin);
            $this->hub->message($account,(new CommunicationDetailService($this->hub))->receipt($row), ['inline_keyboard'=>[[['text'=>'مشاهده اطلاعات و بررسی رسید','url'=>rtrim((string)env('APP_URL'),'/').'/admin/manual-payments/'.$row['id']]],[['text'=>'تأیید رسید','callback_data'=>'mpa:'.$row['id'].':'.$row['version']],['text'=>'رد رسید','callback_data'=>'mpr:'.$row['id'].':'.$row['version']]]]],$key.':notice',$admin);
        }
    }
    public function review(string $id,int $version,string $status,array $admin,string $note=''): void
    {
        if(!Authorization::can($admin,'payments.manage') || !in_array($status,['approved','rejected'],true))throw new RuntimeException('دسترسی تأیید پرداخت مجاز نیست.');
        Database::transaction($this->hub->pdo(),function() use($id,$version,$status,$admin,$note){
            $row=$this->find($id);if(!$row)throw new RuntimeException('رسید یافت نشد.');
            $table=$row['request_type']==='circle'?'event_signups':'course_requests';
            $lock=Database::driver($this->hub->pdo())==='mysql'?' FOR UPDATE':'';
            $accepted=$this->hub->one("SELECT status FROM $table WHERE id=:id AND user_id=:user".$lock,['id'=>$row['request_id'],'user'=>$row['user_id']]);
            if(!$accepted)throw new RuntimeException('درخواست پذیرش در دسترس نیست.');
            if($this->hub->execute("UPDATE manual_payments SET status=:status,reviewed_by=:admin,reviewed_at=:now,updated_at=:updated,review_note=:note WHERE id=:id AND version=:version AND status='pending'",['status'=>$status,'admin'=>$admin['id'],'now'=>Database::now(),'updated'=>Database::now(),'note'=>mb_substr($note,0,1000),'id'=>$id,'version'=>$version])!==1)throw new RuntimeException('رسید قبلاً بررسی شده یا نسخه جدیدی دارد.');
            (new PaymentLifecycleService($this->hub))->sync($row['request_type'],$row['request_id']);
            Audit::record('manual_payment.'.$status,'manual_payment',$id,$admin['id'],['status'=>'pending'],['status'=>$status,'amount'=>$row['amount']], 'manual-review');
            (new NotificationService())->panel(['id'=>$row['user_id']],'بررسی رسید پرداخت',$row['item_title'].'؛ '.($status==='approved'?'رسید بخش '.$row['part'].' تأیید شد.':'رسید تأیید نشد؛ تصویر صحیح را از بخش پرداخت دوباره ارسال کنید.').($note?"\n".$note:''));
            if($this->hub->available())$this->hub->notifyUser($row['user_id'],'بررسی رسید پرداخت',$row['item_title'].'؛ '.($status==='approved'?'رسید تأیید شد.':'رسید تأیید نشد؛ از پنل پرداخت دوباره ارسال کنید.').($note?"\n".$note:''),'manual-review:'.$id.':'.$version.':'.$status);
        });
    }
}

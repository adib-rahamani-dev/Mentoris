<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,Security,Authorization,Audit};
use App\Repositories\{CommunicationRepository,MemberProfileRepository};
use RuntimeException;

final class DiscountCodeService
{
    private ?bool $ready=null;
    public function __construct(private readonly CommunicationRepository $hub=new CommunicationRepository()) {}
    public function available(): bool
    {
        if($this->ready!==null)return $this->ready;
        try{$this->hub->pdo()->query('SELECT d.id FROM discount_codes c LEFT JOIN discount_redemptions d ON d.code_id=c.id LIMIT 0');return $this->ready=true;}
        catch(\PDOException $e){if(MemberProfileRepository::missingTable($e))return $this->ready=false;throw $e;}
    }
    public static function normalize(string $code): string
    {
        return strtoupper(trim(strtr($code,array_combine(preg_split('//u','۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩',-1,PREG_SPLIT_NO_EMPTY),str_split('01234567890123456789')))));
    }
    private function integer(mixed $value,int $maximum): int
    {
        if(!is_string($value) || !preg_match('/^[0-9]{1,10}$/',self::normalize($value)))throw new RuntimeException('مقدار عددی معتبر وارد کنید.');
        $value=(int)self::normalize($value);
        if($value<1 || $value>$maximum)throw new RuntimeException('مقدار تخفیف یا سقف استفاده خارج از محدوده است.');
        return $value;
    }
    private function date(mixed $value): ?string
    {
        if($value==='')return null;
        if(!is_string($value))throw new RuntimeException('تاریخ معتبر انتخاب کنید.');
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new \DateTimeZone('Asia/Tehran'));
        if(!$date || (int)$date->format('Y')<1000 || $date->format('Y-m-d\TH:i')!==$value)throw new RuntimeException('تاریخ معتبر انتخاب کنید.');
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
    public function create(array $input,array $admin): array
    {
        if(!Authorization::can($admin,'payments.manage'))throw new RuntimeException('دسترسی مدیریت تخفیف مجاز نیست.');
        if(!$this->available())throw new RuntimeException('ابتدا SQL تخفیف‌ها را نصب کنید.');
        $code=self::normalize(is_string($input['code']??null)?$input['code']:'');
        if(!preg_match('/^[A-Z0-9][A-Z0-9_-]{2,31}$/',$code))throw new RuntimeException('کد را با ۳ تا ۳۲ حرف انگلیسی، عدد، خط تیره یا زیرخط وارد کنید.');
        $type=$input['discount_type']??'';
        if(!in_array($type,['percent','fixed'],true))throw new RuntimeException('نوع تخفیف معتبر انتخاب کنید.');
        $value=$this->integer($input['value']??'', $type==='percent'?99:1000000000);
        $scope=$input['applies_to']??'all';
        if(!in_array($scope,['all','circle','courses'],true))throw new RuntimeException('محدوده تخفیف معتبر نیست.');
        $slug=trim(is_string($input['item_slug']??null)?$input['item_slug']:'');
        if($slug!=='' && ($scope==='all' || !($scope==='circle'?PublicContentService::event($slug):PublicContentService::course($slug))))throw new RuntimeException('برنامه انتخاب‌شده با محدوده تخفیف سازگار نیست.');
        $maximum=($input['max_uses']??'')===''?null:$this->integer($input['max_uses'],1000000);
        $start=$this->date($input['starts_at']??'');$end=$this->date($input['ends_at']??'');
        if($start && $end && $end<=$start)throw new RuntimeException('پایان اعتبار باید بعد از شروع باشد.');
        $row=['id'=>Security::randomToken(16),'code'=>$code,'discount_type'=>$type,'value'=>$value,'applies_to'=>$scope,'item_slug'=>$slug?:null,'max_uses'=>$maximum,'starts_at'=>$start,'ends_at'=>$end,'created_by'=>$admin['id'],'created_at'=>Database::now(),'updated_at'=>Database::now()];
        try{$this->hub->execute('INSERT INTO discount_codes ('.implode(',',array_keys($row)).') VALUES (:'.implode(',:',array_keys($row)).')',$row);}
        catch(\PDOException $e){if(in_array((string)$e->getCode(),['23000','23505'],true))throw new RuntimeException('این کد قبلاً ساخته شده است.');throw $e;}
        Audit::record('discount.created','discount',$row['id'],$admin['id'],[],['code'=>$code,'type'=>$type,'value'=>$value],'discount-admin');
        return $row;
    }
    public function redemption(string $type,string $id): ?array
    {
        return $this->available()?$this->hub->one('SELECT * FROM discount_redemptions WHERE request_type=:type AND request_id=:id',['type'=>$type,'id'=>$id]):null;
    }
    private function lockRequest(array $context,array $user): void
    {
        $table=['circle'=>'event_signups','courses'=>'course_requests'][$context['type']]??null;
        if(!$table)throw new RuntimeException('درخواست معتبر نیست.');
        $lock=Database::driver($this->hub->pdo())==='mysql'?' FOR UPDATE':'';
        $request=$this->hub->one("SELECT status FROM $table WHERE id=:id AND user_id=:user".$lock,['id'=>$context['id'],'user'=>$user['id']]);
        if(!$request || $request['status']!=='approved' || ($context['payable']??true)===false)throw new RuntimeException('تخفیف فقط برای درخواست پذیرفته‌شده و فعال قابل اعمال است.');
        if(!ManualPaymentService::profileReady($user))throw new RuntimeException('ابتدا پروفایل را تکمیل کنید.');
        if($this->hub->one('SELECT id FROM manual_payments WHERE request_type=:type AND request_id=:id LIMIT 1',['type'=>$context['type'],'id'=>$context['id']]))throw new RuntimeException('پس از ثبت اولین رسید، کد و مبلغ تخفیف قابل تغییر نیست.');
    }
    public function apply(array $context,array $user,string $rawCode): array
    {
        if(!$this->available())throw new RuntimeException('بخش تخفیف در حال آماده‌سازی است.');
        $code=self::normalize($rawCode);
        if(!preg_match('/^[A-Z0-9][A-Z0-9_-]{2,31}$/',$code))throw new RuntimeException('کد تخفیف معتبر وارد کنید.');
        return Database::transaction($this->hub->pdo(),function() use($context,$user,$code){
            $this->lockRequest($context,$user);
            $existing=$this->redemption($context['type'],$context['id']);
            if($existing){if($existing['code']===$code)return $existing;throw new RuntimeException('ابتدا کد فعلی را حذف کنید. تخفیف‌ها با هم جمع نمی‌شوند.');}
            $lock=Database::driver($this->hub->pdo())==='mysql'?' FOR UPDATE':'';
            $coupon=$this->hub->one('SELECT * FROM discount_codes WHERE code=:code'.$lock,['code'=>$code]);$now=Database::now();
            if(!$coupon || !$coupon['active'] || ($coupon['starts_at'] && $coupon['starts_at']>$now) || ($coupon['ends_at'] && $coupon['ends_at']<=$now))throw new RuntimeException('کد فعال نیست یا خارج از بازه اعتبار است.');
            if($coupon['applies_to']!=='all' && $coupon['applies_to']!==$context['type'])throw new RuntimeException('این کد برای این نوع برنامه قابل استفاده نیست.');
            if($coupon['item_slug'] && $coupon['item_slug']!==$context['item']['slug'])throw new RuntimeException('این کد مخصوص برنامه دیگری است.');
            if($this->hub->one('SELECT id FROM discount_redemptions WHERE code_id=:code AND user_id=:user',['code'=>$coupon['id'],'user'=>$user['id']]))throw new RuntimeException('هر عضو می‌تواند این کد را فقط روی یک درخواست استفاده کند.');
            $original=(int)$context['item']['price_amount'];
            $percent=(int)$coupon['value'];
            $discount=$coupon['discount_type']==='percent'?intdiv($original,100)*$percent+intdiv(($original%100)*$percent,100):$percent;
            if($discount<1 || $discount>=$original)throw new RuntimeException('تخفیف باید کمتر از مبلغ کل باشد؛ ثبت‌نام رایگان از این مسیر فعال نیست.');
            if($this->hub->execute('UPDATE discount_codes SET used_count=used_count+1,updated_at=:now WHERE id=:id AND (max_uses IS NULL OR used_count<max_uses)',['now'=>$now,'id'=>$coupon['id']])!==1)throw new RuntimeException('ظرفیت استفاده از این کد تکمیل شده است.');
            $row=['id'=>Security::randomToken(16),'code_id'=>$coupon['id'],'user_id'=>$user['id'],'request_type'=>$context['type'],'request_id'=>$context['id'],'code'=>$coupon['code'],'original_amount'=>$original,'discount_amount'=>$discount,'final_amount'=>$original-$discount,'created_at'=>$now];
            $this->hub->execute('INSERT INTO discount_redemptions ('.implode(',',array_keys($row)).') VALUES (:'.implode(',:',array_keys($row)).')',$row);
            Audit::record('discount.applied','discount_redemption',$row['id'],$user['id'],[],['code'=>$code,'discount'=>$discount],'discount-user');
            return $row;
        });
    }
    public function remove(array $context,array $user): void
    {
        if(!$this->available())throw new RuntimeException('بخش تخفیف در حال آماده‌سازی است.');
        Database::transaction($this->hub->pdo(),function()use($context,$user){
            $this->lockRequest($context,$user);$row=$this->redemption($context['type'],$context['id']);if(!$row)return;
            $this->hub->execute('DELETE FROM discount_redemptions WHERE id=:id',['id'=>$row['id']]);
            $this->hub->execute('UPDATE discount_codes SET used_count=used_count-1,updated_at=:now WHERE id=:id AND used_count>0',['now'=>Database::now(),'id'=>$row['code_id']]);
            Audit::record('discount.removed','discount_redemption',$row['id'],$user['id'],['code'=>$row['code']],[],'discount-user');
        });
    }
    public function setActive(string $id,bool $active,array $admin): void
    {
        if(!Authorization::can($admin,'payments.manage'))throw new RuntimeException('دسترسی مدیریت تخفیف مجاز نیست.');
        if(!$this->available())throw new RuntimeException('ابتدا SQL تخفیف‌ها را نصب کنید.');
        if(!$this->hub->one('SELECT id FROM discount_codes WHERE id=:id',['id'=>$id]))throw new RuntimeException('کد یافت نشد.');
        $this->hub->execute('UPDATE discount_codes SET active=:active,updated_at=:now WHERE id=:id',['active'=>(int)$active,'now'=>Database::now(),'id'=>$id]);
        Audit::record('discount.status','discount',$id,$admin['id'],[],['active'=>$active],'discount-admin');
    }
}

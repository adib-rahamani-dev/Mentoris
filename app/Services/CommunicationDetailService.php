<?php
declare(strict_types=1);
namespace App\Services;
use App\Repositories\{CommunicationRepository,MemberProfileRepository,CircleRepository};
final class CommunicationDetailService
{
    public function __construct(private readonly CommunicationRepository $hub=new CommunicationRepository()){}
    public static function time(string $value): string
    {
        try{return (new \DateTimeImmutable($value,new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Asia/Tehran'))->format('Y/m/d H:i');}catch(\Throwable){return $value;}
    }
    public function request(string $type,string $id): string
    {
        $table=['circle'=>'event_signups','courses'=>'course_requests'][$type]??null;if(!$table)return '';
        $row=$this->hub->one("SELECT * FROM $table WHERE id=:id",['id'=>$id]);if(!$row)return 'درخواست در دسترس نیست.';
        $member=!empty($row['user_id'])?$this->hub->one('SELECT id,name,email,phone FROM users WHERE id=:id',['id'=>$row['user_id']]):null;
        $profile=$member?(new MemberProfileRepository($this->hub->pdo()))->find($member['id']):[];
        $item=$type==='circle'?PublicContentService::event($row['event_slug']):PublicContentService::course($row['course_slug']);
        $lines=['📋 '.($type==='circle'?'درخواست حلقه':'درخواست دوره'),'برنامه: '.($item['title']??$row['event_slug']??$row['course_slug']),'شناسه: '.$id,'ثبت: '.self::time($row['created_at']).' تهران','پذیرش: '.(['requested'=>'در انتظار بررسی','approved'=>'تأیید شده','rejected'=>'رد شده','canceled'=>'لغو شده','attended'=>'حضور ثبت شده'][$row['status']]??$row['status']),'','👤 متقاضی','نام: '.$row['name'],'موبایل: '.$row['phone'],'ایمیل: '.($member['email']??'ثبت نشده')];
        if(!empty($profile['member_type']))$lines[]='نوع عضو: '.(MemberProfileService::OPTIONS['member_type'][$profile['member_type']]??$profile['member_type']);
        $details=$profile;
        if($type==='circle'){$application=(new CircleRepository($this->hub->pdo()))->application($id);$details=($application['details']??[])+$details;$lines[]='شهر: '.($row['city']?:'ثبت نشده');$lines[]='رزومه: '.($application['resume']['filename']??'ارسال نشده (اختیاری)');}
        else $lines[]='پرداخت انتخابی: '.($row['payment_plan']==='installments'?'دو مرحله‌ای، نصف مبلغ برای هر نیمه دوره':'نقدی کامل');
        $lines[]='';$lines[]='🎓 اطلاعات تحصیلی و حرفه‌ای';
        foreach(['education'=>'مقطع تحصیلی','degree'=>'مدرک','university'=>'دانشگاه','field_of_study'=>'رشته','specialization'=>'گرایش','experience'=>'سابقه','approach'=>'رویکرد درمانی','practice_areas'=>'حوزه بالینی','specialty_fields'=>'حوزه تخصصی','practice_status'=>'وضعیت فعالیت','workplace'=>'محل کار','job_title'=>'عنوان شغلی','professional_number'=>'شماره نظام / دانشجویی','motivation'=>'انگیزه حضور'] as $key=>$label){$value=$details[$key]??'';if(!is_scalar($value) || (string)$value==='')continue;$value=MemberProfileService::OPTIONS[$key][$value]??$value;$lines[]=$label.': '.mb_strimwidth((string)$value,0,$key==='motivation'?450:140,'…');}
        if(!empty($item['price_amount']))$lines[]="\n💳 هزینه کل: ".number_format((int)$item['price_amount']).' تومان';
        if((new ManualPaymentService($this->hub))->available()){
            $discount=(new DiscountCodeService($this->hub))->redemption($type,$id);if($discount)$lines[]='کد '.$discount['code'].' | تخفیف '.number_format((int)$discount['discount_amount']).' | مبلغ نهایی '.number_format((int)$discount['final_amount']).' تومان';
            foreach($this->hub->rows('SELECT part,amount,status FROM manual_payments WHERE request_type=:type AND request_id=:id ORDER BY part',['type'=>$type,'id'=>$id]) as $payment)$lines[]='رسید بخش '.$payment['part'].': '.number_format((int)$payment['amount']).' تومان · '.(['pending'=>'منتظر تطبیق واریز','approved'=>'دریافت وجه تأیید شده','rejected'=>'رسید رد شده'][$payment['status']]??$payment['status']);
            foreach((new PaymentLifecycleService($this->hub))->refunds($type,$id) as $refund)$lines[]='بازگشت '.number_format((int)$refund['amount']).' تومان · '.($refund['status']==='refunded'?'انجام شده، پیگیری '.$refund['transfer_reference']:'نیازمند اقدام');
        }
        return mb_strimwidth(implode("\n",$lines),0,3450,'… جزئیات کامل در صفحه بررسی');
    }
    public function receipt(array $row): string
    {
        $file=json_decode($row['receipt'],true)?:[];
        return '🧾 رسید پرداخت · '.(['pending'=>'منتظر بررسی','approved'=>'واریز تأیید شده','rejected'=>'رسید رد شده'][$row['status']]??$row['status'])."\n".'مبلغ رسید: '.number_format((int)$row['amount']).' تومان | بخش '.$row['part']."\nنسخه: ".$row['version'].' | زمان: '.self::time($row['updated_at'])."\nفایل: ".($file['filename']??'تصویر رسید')."\nشناسه رسید: ".$row['id']."\n\n".$this->request($row['request_type'],$row['request_id']);
    }
}

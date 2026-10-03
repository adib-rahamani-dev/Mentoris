<?php
declare(strict_types=1);
namespace App\Services;
use App\Repositories\TelegramRepository;

final class TelegramBotService
{
    public function __construct(private readonly TelegramRepository $repository = new TelegramRepository()) {}
    public function handle(array $update): void
    {
        $callback=$update['callback_query'] ?? null;
        $message=$callback['message'] ?? $update['message'] ?? [];
        $from=$callback['from'] ?? $message['from'] ?? [];
        $chat=$message['chat'] ?? [];
        if(($chat['type'] ?? '')!=='private' || (string)($chat['id'] ?? '')!==(string)($from['id'] ?? '') || !empty($from['is_bot'])) return;
        $id=(string)$chat['id']; $user=$this->repository->user($id,$from);
        if($callback) {
            $this->repository->queue('answerCallbackQuery',['callback_query_id'=>(string)$callback['id']]);
            $action=(string)($callback['data'] ?? '');
            if($action==='menu') { $this->menu($id); return; }
            if($action==='ask') { $this->repository->state($id,'question'); $this->repository->message($id,'سؤال خود درباره رویدادها، دوره‌ها یا خدمات منتوریس را در یک پیام بنویسید (حداکثر ۲۰۰۰ کاراکتر). پاسخ تیم در همین گفت‌وگو ارسال می‌شود. اطلاعات خصوصی مراجعان را ارسال نکنید. برای برگشت /cancel را بزنید.'); return; }
            if($action==='tools') { $this->repository->message($id,"جعبه‌ابزار رایگان منتوریس\nبرگه مرور جلسه، برنامه تمرین عامدانه و راهنمای آماده‌شدن برای سوپرویژن؛ قابل چاپ و ذخیره.",$this->links([['دریافت ابزارها','/resources']])); return; }
            if($action==='faq') { $this->repository->message($id,"عضویت سایت رایگان است و فعلاً تأیید پیامکی ندارد.\nاطلاعات تکمیلی را می‌توانید بعداً ذخیره کنید.\nدرخواست جامعه حرفه‌ای با یک تیک در پروفایل ثبت می‌شود.\nدو نشست قبلی پایان یافته‌اند و ثبت‌نامشان بسته است.\nبرای ارسال سؤال از گزینه «پرسیدن سؤال» استفاده کنید.",$this->links([['پروفایل و عضویت','/profile'],['بازخورد نشست‌ها','/feedback']])); return; }
            if($action==='events') {
                $rows=[]; foreach(PublicContentService::events() as $event) $rows[]=[['text'=>mb_substr($event['title'],0,55),'callback_data'=>'e:'.self::eventKey($event['slug'])]];
                $rows[]=[['text'=>'برگشت به منو','callback_data'=>'menu']];
                $this->repository->message($id,'نشست موردنظر را انتخاب کنید:',['inline_keyboard'=>$rows]); return;
            }
            if(str_starts_with($action,'e:') || str_starts_with($action,'j:')) {
                $event=$this->eventByKey(substr($action,2));
                if(!$event) { $this->repository->message($id,'این رویداد در دسترس نیست. /menu'); return; }
                if(str_starts_with($action,'j:')) {
                    if(empty($event['can_register'])) { $this->repository->message($id,'ثبت‌نام این نشست بسته است.',$this->links([['مشاهده نشست','/events/'.$event['slug']]])); return; }
                    $this->repository->state($id,'event_name',['event'=>$event['slug']]);
                    $this->repository->message($id,'برای ثبت درخواست شرکت، نام و نام خانوادگی خود را بنویسید. اطلاعات شما برای پیگیری درخواست در پنل تیم منتوریس ذخیره می‌شود. لغو: /cancel'); return;
                }
                $rows=$this->links([['جزئیات نشست','/events/'.$event['slug']],['ثبت بازخورد','/feedback?event='.$event['slug']]])['inline_keyboard'];
                if(!empty($event['can_register'])) array_unshift($rows,[['text'=>'درخواست شرکت در نشست','callback_data'=>'j:'.self::eventKey($event['slug'])]]);
                $this->repository->message($id,$event['title']."\n".($event['date'] ?? '')." | ".($event['time'] ?? '')."\n".($event['location'] ?? '')."\n".(empty($event['can_register']) ? 'ثبت‌نام بسته است.' : 'ثبت درخواست نیازمند بررسی تیم است؛ بلیت یا تأیید پرداخت محسوب نمی‌شود.'),['inline_keyboard'=>$rows]); return;
            }
            if($action==='confirm' && $user['state']==='event_confirm') {
                $data=$user['data']; $event=PublicContentService::event($data['event']);
                if(!$event || empty($event['can_register'])) { $this->repository->state($id,''); $this->repository->message($id,'ثبت‌نام این رویداد اکنون بسته است. /menu'); return; }
                $created=$this->repository->eventRequest($id,$data['event'],$data); $this->repository->state($id,'');
                $this->repository->message($id,$created ? 'درخواست شرکت شما ثبت شد و در انتظار بررسی تیم است. این پیام تأیید پرداخت یا صدور بلیت نیست.' : 'درخواست شما برای این نشست قبلاً ثبت شده است.'); return;
            }
            return;
        }
        $text=trim((string)($message['text'] ?? ''));
        if(preg_match('~^/(start|menu|help|cancel)(?:@\w+)?(?:\s|$)~',$text)) { $this->menu($id); return; }
        if($user['state']==='question') {
            if(mb_strlen($text)<3 || mb_strlen($text)>2000) { $this->repository->message($id,'لطفاً سؤال را در یک پیام متنی بین ۳ تا ۲۰۰۰ کاراکتر بنویسید.'); return; }
            $s=$this->repository->pdo()->prepare('SELECT COUNT(*) FROM telegram_questions WHERE chat_id=:id AND created_at>=:since'); $s->execute(['id'=>$id,'since'=>gmdate('Y-m-d H:i:s',time()-3600)]);
            if((int)$s->fetchColumn()>=10) { $this->repository->message($id,'درخواست‌های شما دریافت شده‌اند. لطفاً برای ارسال سؤال جدید کمی صبر کنید.'); return; }
            $this->repository->question($id,(int)$update['update_id'],$text); $this->repository->state($id,'');
            $this->repository->message($id,'سؤال شما ثبت شد. پس از بررسی، پاسخ تیم همین‌جا ارسال می‌شود. /menu'); return;
        }
        $data=$user['data'];
        if($user['state']==='event_name') {
            if(mb_strlen($text)<3 || mb_strlen($text)>120) { $this->repository->message($id,'نام کامل را بین ۳ تا ۱۲۰ کاراکتر بنویسید.'); return; }
            $data['name']=$text; $this->repository->state($id,'event_phone',$data);
            $this->repository->message($id,'شماره موبایل خود را بنویسید یا دکمه زیر را بزنید.',['keyboard'=>[[['text'=>'ارسال شماره موبایل من','request_contact'=>true]]],'resize_keyboard'=>true,'one_time_keyboard'=>true]); return;
        }
        if($user['state']==='event_phone') {
            $contact=$message['contact'] ?? null;
            if($contact && (string)($contact['user_id'] ?? '')!==$id) { $this->repository->message($id,'لطفاً شماره خودتان را ارسال کنید.'); return; }
            $phone=strtr((string)($contact['phone_number'] ?? $text),array_combine(mb_str_split('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩'),str_split('01234567890123456789')));
            $phone=preg_replace('/[\s()+-]/','',$phone); if(str_starts_with($phone,'98')) $phone='0'.substr($phone,2);
            if(!preg_match('/^09\d{9}$/',$phone)) { $this->repository->message($id,'شماره موبایل را مانند 09123456789 وارد کنید.'); return; }
            $data['phone']=$phone; $this->repository->state($id,'event_city',$data); $this->repository->message($id,'شهر محل سکونت خود را بنویسید.',['remove_keyboard'=>true]); return;
        }
        if($user['state']==='event_city') {
            if(mb_strlen($text)<2 || mb_strlen($text)>80) { $this->repository->message($id,'نام شهر را بین ۲ تا ۸۰ کاراکتر بنویسید.'); return; }
            $data['city']=$text; $this->repository->state($id,'event_confirm',$data);
            $this->repository->message($id,"درخواست شرکت\nنام: ".$data['name']."\nموبایل: ".$data['phone']."\nشهر: ".$text,['inline_keyboard'=>[[['text'=>'تأیید و ثبت درخواست','callback_data'=>'confirm']],[['text'=>'لغو','callback_data'=>'menu']]]]); return;
        }
        $this->menu($id);
    }
    public static function eventKey(string $slug): string { return substr(hash('sha256',$slug),0,12); }
    private function eventByKey(string $key): ?array { foreach(PublicContentService::events() as $event) if(hash_equals(self::eventKey($event['slug']),$key)) return PublicContentService::event($event['slug']); return null; }
    private function links(array $links): array { $rows=[]; foreach($links as [$text,$path]) $rows[]=[['text'=>$text,'url'=>rtrim((string)env('APP_URL','https://mentorisacademy.com'),'/').$path]]; $rows[]=[['text'=>'منوی اصلی','callback_data'=>'menu']]; return ['inline_keyboard'=>$rows]; }
    private function menu(string $id): void
    {
        $this->repository->state($id,'');
        $this->repository->message($id,'به منتوریس خوش آمدید 🌱 چه کاری برایتان انجام دهیم؟',['remove_keyboard'=>true]);
        $this->repository->message($id,'یکی از گزینه‌ها را انتخاب کنید:',['inline_keyboard'=>[[['text'=>'همایش‌ها و نشست‌ها','callback_data'=>'events']],[['text'=>'ابزارهای رایگان','callback_data'=>'tools'],['text'=>'پرسیدن سؤال','callback_data'=>'ask']],[['text'=>'عضویت و پروفایل','url'=>rtrim((string)env('APP_URL','https://mentorisacademy.com'),'/').'/profile']],[['text'=>'پرسش‌های متداول','callback_data'=>'faq']]]]);
    }
}

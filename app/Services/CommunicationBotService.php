<?php
declare(strict_types=1);
namespace App\Services;
use App\Repositories\{CommunicationRepository,SupportRepository};
use App\Core\{Authorization,Database,PhoneNumber};

final class CommunicationBotService
{
    private array $account;
    private ?array $user=null;
    private BotAdminService $admin;
    public function __construct(private readonly CommunicationRepository $hub=new CommunicationRepository(),private readonly string $channel='telegram') { $this->admin=new BotAdminService($hub); }
    private function say(string $text,?array $markup=null,?array $user=null): void { $this->hub->message($this->account,$text,$markup,null,$user); }
    private function buttons(array $rows): array { return ['inline_keyboard'=>$rows]; }
    private function link(string $text,string $path): array { return ['text'=>$text,'url'=>rtrim((string)env('APP_URL','https://mentorisacademy.com'),'/').$path]; }
    private function returnMenu(): array {return [['text'=>'منوی اصلی','callback_data'=>'menu']];}
    public function handle(array $update): void
    {
        $callback=is_array($update['callback_query'] ?? null)?$update['callback_query']:null;
        $message=$callback['message'] ?? $update['message'] ?? []; $from=$callback['from'] ?? $message['from'] ?? []; $chat=$message['chat'] ?? [];
        if(!is_array($message) || !is_array($from) || !is_array($chat) || ($chat['type'] ?? '')!=='private' || (string)($chat['id'] ?? '')!==(string)($from['id'] ?? '') || !empty($from['is_bot'])) return;
        $this->account=$this->hub->account($this->channel,(string)$chat['id'],$from);$this->user=$this->hub->linkedUser($this->account);
        // Limit incoming interactions; a long receipt list must not lock its manager out.
        $limiter=new \App\Core\FileRateLimiter();$bucket=$limiter->hit('bot:'.$this->channel.':'.$this->account['chat_id'],45,60);
        if(!$bucket['allowed']){
            if($callback)$this->hub->queue($this->channel,$this->account['chat_id'],'answerCallbackQuery',['callback_query_id'=>(string)$callback['id'],'text'=>'کمی صبر کنید؛ تا یک دقیقه دیگر دوباره تلاش کنید.']);
            elseif($limiter->hit('bot-limit-notice:'.$this->channel.':'.$this->account['chat_id'],1,60)['allowed'])$this->say('تعداد درخواست‌ها زیاد شده است؛ تا یک دقیقه دیگر دوباره تلاش کنید.');
            return;
        }
        if($callback) {
            $this->hub->queue($this->channel,$this->account['chat_id'],'answerCallbackQuery',['callback_query_id'=>(string)$callback['id']]);
            try{$this->callback(is_string($callback['data'] ?? null)?$callback['data']:'');}catch(\RuntimeException $e){if($e instanceof \PDOException)throw $e;$this->say($e->getMessage(),$this->buttons([$this->returnMenu()]));}return;
        }
        $text=is_string($message['text'] ?? null)?trim($message['text']):'';
        if(preg_match('~^/(?:start(?:@\w+)?\s+link_|link(?:@\w+)?\s+)([a-f0-9]{32})$~',$text,$match)) {
            $ok=$this->hub->claimLink($this->account,$match[1]);$this->say($ok?'حساب سایت متصل شد. مدیریت و اعلان‌ها مطابق دسترسی فعلی شما فعال‌اند.':'کد معتبر نیست، منقضی شده یا حساب قبلاً به پیام‌رسان دیگری متصل است. از صفحهٔ اتصال پیام‌رسان کد تازه بگیرید.');
            $this->account=$this->hub->account($this->channel,$this->account['chat_id']);$this->user=$this->hub->linkedUser($this->account);$this->menu();return;
        }
        if(preg_match('~^/(admin|stats)(?:@\w+)?$~',$text)) {$this->adminMenu($text==='/stats');return;}
        if(preg_match('~^/(orders|account|notifications)(?:@\w+)?$~',$text,$command)){$this->callback(['orders'=>'my:requests:0','account'=>'my:account','notifications'=>'notify'][$command[1]]);return;}
        if(preg_match('~^/help(?:@\w+)?$~',$text)){$this->callback('help');return;}
        if(preg_match('~^/(start|menu|cancel)(?:@\w+)?(?:\s|$)~',$text)) {$this->menu();return;}
        if(preg_match('~^/user(?:@\w+)?\s+(.+)$~',$text,$match)) {
            $this->findMember($match[1]);return;
        }
        try {
            if($this->account['state']==='admin_search'){$this->findMember($text);return;}
            if($this->account['state']==='connect') {
                $contact=$message['contact'] ?? null;
                $ok=is_array($contact) && $this->hub->contactMatch($this->account,$contact);
                $this->say($ok?'شمارهٔ خودتان با حساب عضو سایت تطبیق یافت.':'برای اتصال خودکار، شمارهٔ خودتان را با دکمهٔ اشتراک شماره ارسال کنید. حساب مدیر فقط با کد اتصال داخل سایت متصل می‌شود.',$this->buttons([[$this->link('اتصال امن از سایت','/messengers')],$this->returnMenu()]));return;
            }
            $attachment=(new SupportFileService())->metadata($message,$this->channel);
            $body=$text!==''?$text:(is_string($message['caption'] ?? null)?trim($message['caption']):'');
            $source=$this->channel.':update:'.(int)$update['update_id'];$data=$this->account['data'];$support=new SupportRepository($this->hub);
            if($this->account['state']==='new_support') {
                $category=$data['category'];$subject=$body!==''?mb_substr($body,0,100):$support->categories()[$category];
                $ticket=$support->create($this->user['id'] ?? null,$this->channel,$this->account['chat_id'],$subject,$category,$body,$attachment,$source);
                $this->hub->state($this->account,'ticket',['id'=>$ticket['id']]);$this->say('تیکت ثبت شد. می‌توانید پیام یا فایل بعدی را همین‌جا بفرستید. پاسخ تیم در همین گفتگو می‌رسد.',$this->buttons([[$this->link('مرکز پشتیبانی سایت','/support')],$this->returnMenu()]));return;
            }
            if($this->account['state']==='ticket') {
                $ticket=$support->find((string)$data['id']);if(!$ticket || !$this->ownsTicket($ticket)) throw new \RuntimeException('این گفتگو متعلق به شما نیست.');
                $support->append($ticket,$this->user['id'] ?? null,'member',$body,$attachment,$source);$this->say('پیام شما به گفتگو اضافه شد.',$this->buttons([$this->returnMenu()]));return;
            }
            if($this->account['state']==='admin_reply') {
                $actor=$this->admin->authorized($this->account,'engagements.manage');if(!$actor){$this->denied();return;}
                if(mb_strlen($body)>3500 || ($attachment===null && mb_strlen($body)<2)) throw new \RuntimeException('پاسخ تا ۳۵۰۰ کاراکتر یا فایل بفرستید.');
                $id=$this->hub->prepareAction($this->account,$actor,'reply',['id'=>$data['id'],'text'=>$body,'attachment'=>$attachment]);$this->hub->state($this->account,'');$this->confirm($id,'پاسخ زیر برای صاحب تیکت ارسال شود؟'."\n\n".$body.($attachment?"\nفایل: ".$attachment['filename']:''),$actor);return;
            }
            if(in_array($this->account['state'],['article_title','article_body'],true)) {
                $actor=$this->admin->authorized($this->account,'content.manage');if(!$actor){$this->denied();return;}
                if($this->account['state']==='article_title') {
                    if(mb_strlen($body)<5 || mb_strlen($body)>140) throw new \RuntimeException('عنوان را بین ۵ و ۱۴۰ کاراکتر بنویسید.');
                    $this->hub->state($this->account,'article_body',['title'=>$body]);$this->say('متن مقاله را تا ۳۵۰۰ کاراکتر بفرستید. تیترها را با ## شروع کنید. ابتدا پیش‌نویس ذخیره می‌شود.',null,$actor);return;
                }
                if(mb_strlen($body)<80 || mb_strlen($body)>3500) throw new \RuntimeException('متن را بین ۸۰ و ۳۵۰۰ کاراکتر بفرستید.');
                $id=$this->hub->prepareAction($this->account,$actor,'article_draft',['title'=>$data['title'],'text'=>$body]);$this->hub->state($this->account,'');$this->confirm($id,'پیش‌نویس «'.$data['title'].'» ذخیره شود؟',$actor);return;
            }
            $this->menu();
        } catch(\RuntimeException $e){if($e instanceof \PDOException)throw $e;$this->say($e->getMessage(),$this->buttons([$this->returnMenu()]));}
    }
    private function ownsTicket(array $ticket): bool {return ($ticket['channel']===$this->channel && (string)$ticket['chat_id']===$this->account['chat_id']) || ($this->user && $ticket['user_id']===$this->user['id']);}
    private function findMember(string $input): void
    {
        $actor=$this->admin->authorized($this->account,'users.view');if(!$actor){$this->denied();return;}
        $phone=PhoneNumber::normalize($input);
        if(!preg_match('/^09[0-9]{9}$/',$phone)){$this->say('شماره موبایل عضو را با ۰۹ یا +۹۸ بفرستید. لغو: /cancel',null,$actor);return;}
        $row=$this->hub->one('SELECT id,name,phone,email,account_role,status,created_at FROM users WHERE phone=:phone LIMIT 1',['phone'=>$phone]);
        $this->say($row?'👤 '.$row['name']."\nموبایل: ".$row['phone']."\nایمیل: ".($row['email']?:'ثبت نشده')."\nنقش: ".Authorization::roleLabel($row['account_role'])."\nوضعیت: ".($row['status']==='active'?'فعال':'تعلیق‌شده')."\nعضویت: ".CommunicationDetailService::time($row['created_at']):'کاربری با این شماره پیدا نشد. شماره دیگری را بفرستید.',$this->buttons(array_merge($row?[[$this->link('پرونده کامل عضو','/admin/users/'.$row['id'])]]:[],[[['text'=>'جست‌وجوی عضو دیگر','callback_data'=>'admin:search']],[['text'=>'مدیریت','callback_data'=>'admin']]])),$actor);
        if($row)$this->hub->state($this->account,'');
    }
    private function memberArea(string $action): void
    {
        if(!$this->user){$this->say('برای دیدن اطلاعات شخصی، ابتدا حساب سایت را به ربات متصل کنید.',$this->buttons([[['text'=>'اتصال حساب من','callback_data'=>'connect']],$this->returnMenu()]));return;}
        $user=$this->user;
        if($action==='my:account'){
            $this->say('👤 '.$user['name']."\nشماره: ".$user['phone']."\nاعلان پیام‌رسان: ".(!empty($this->account['notifications_enabled'])?'فعال':'خاموش'),$this->buttons([[['text'=>'ثبت‌نام‌ها و پرداخت‌های من','callback_data'=>'my:requests:0']],[$this->link('پروفایل و اطلاعات تکمیلی','/profile')],[['text'=>'تنظیم اعلان‌ها','callback_data'=>'notify']],$this->returnMenu()]),$user);return;
        }
        if(preg_match('/^my:requests:([0-9]{1,4})$/',$action,$match)){
            $page=(int)$match[1];$offset=$page*8;
            $items=$this->hub->rows("SELECT id,'circle' kind,event_slug slug,status,created_at FROM event_signups WHERE user_id=:event_user UNION ALL SELECT id,'courses' kind,course_slug slug,status,created_at FROM course_requests WHERE user_id=:course_user ORDER BY created_at DESC,id DESC LIMIT 9 OFFSET $offset",['event_user'=>$user['id'],'course_user'=>$user['id']]);$rows=[];
            foreach(array_slice($items,0,8) as $item){$program=$item['kind']==='circle'?PublicContentService::event($item['slug']):PublicContentService::course($item['slug']);$rows[]=[['text'=>mb_substr($program['title']??$item['slug'],0,40),'callback_data'=>'my:'.$item['kind'].':'.$item['id']]];}
            $nav=[];if($page>0)$nav[]=['text'=>'قبلی','callback_data'=>'my:requests:'.($page-1)];if(count($items)>8)$nav[]=['text'=>'بعدی','callback_data'=>'my:requests:'.($page+1)];if($nav)$rows[]=$nav;$rows[]=$this->returnMenu();
            $this->say($items?'ثبت‌نام خود را برای مشاهده وضعیت پذیرش و پرداخت انتخاب کنید:':'هنوز درخواستی ثبت نکرده‌اید. دوره‌ها و حلقه‌ها را از منوی اصلی ببینید.',$this->buttons($rows),$user);return;
        }
        preg_match('/^my:(circle|courses):([a-f0-9]{32})$/',$action,$match);$type=$match[1];$table=$type==='circle'?'event_signups':'course_requests';
        $row=$this->hub->one("SELECT * FROM $table WHERE id=:id AND user_id=:user",['id'=>$match[2],'user'=>$user['id']]);
        if(!$row){$this->say('این ثبت‌نام در حساب شما در دسترس نیست.',$this->buttons([[['text'=>'ثبت‌نام‌های من','callback_data'=>'my:requests:0']]]),$user);return;}
        $slug=$row['event_slug']??$row['course_slug'];$program=$type==='circle'?PublicContentService::event($slug):PublicContentService::course($slug);
        $text='📋 '.($program['title']??$slug)."\nپذیرش: ".(['requested'=>'در انتظار پرداخت یا بررسی پذیرش','approved'=>'تأیید شده','attended'=>'حضور ثبت شده','rejected'=>'تأیید نشد','canceled'=>'لغو شده'][$row['status']]??$row['status']);
        if((new ManualPaymentService($this->hub))->available()){
            foreach($this->hub->rows('SELECT part,amount,status FROM manual_payments WHERE request_type=:type AND request_id=:id AND user_id=:user ORDER BY part',['type'=>$type,'id'=>$row['id'],'user'=>$user['id']]) as $payment)$text.="\nپرداخت بخش ".$payment['part'].': '.number_format((int)$payment['amount']).' تومان · '.(['pending'=>'رسید منتظر بررسی','approved'=>'واریز تأیید شده','rejected'=>'رسید رد شده؛ تصویر جدید بفرستید'][$payment['status']]??$payment['status']);
            foreach((new PaymentLifecycleService($this->hub))->refunds($type,$row['id']) as $refund)$text.="\nبازگشت وجه: ".number_format((int)$refund['amount']).' تومان · '.($refund['status']==='refunded'?'انجام شده؛ پیگیری '.$refund['transfer_reference']:'در انتظار اقدام تیم');
        }
        if($type==='circle')$text.="\nزمان: ".($program['date']??'').' | '.($program['time']??'')."\nمکان: ".($program['location']??'اعلام می‌شود');
        $this->say($text,$this->buttons([[$this->link('پرداخت، ارسال رسید و پیگیری','/payments/'.$type.'/'.$row['id'])],[['text'=>'ثبت‌نام‌های من','callback_data'=>'my:requests:0']],$this->returnMenu()]),$user);
    }
    private function callback(string $action): void
    {
        // Navigation leaves any pending text-entry flow; an unrelated message must not become a ticket reply.
        $this->hub->state($this->account,'');
        if($action==='menu') {$this->menu();return;}
        if($action==='help'){$this->say("راهنمای ربات منتوریس 🌱\n\n۱. از «اتصال حساب» حساب سایت را متصل کنید.\n۲. در «ثبت‌نام‌ها و پرداخت‌ها» وضعیت پذیرش، رسید و بازگشت وجه را ببینید. ارسال رسید از صفحه خصوصی سایت انجام می‌شود.\n۳. از پشتیبانی انسانی، پیام یا فایل بفرستید و پاسخ را همین‌جا دریافت کنید.\n۴. مدیران پس از اتصال امن حساب مدیر، از /admin رسیدها، پذیرش و گفتگوها را مدیریت می‌کنند.\n\n/account حساب من\n/orders ثبت‌نام‌ها\n/notifications تنظیم اعلان\n/cancel لغو مرحله جاری\n/menu منوی اصلی",$this->buttons([[['text'=>'اتصال حساب','callback_data'=>'connect']],[$this->link('تماس با آکادمی','/contact')],$this->returnMenu()]));return;}
        if($action==='my:account' || preg_match('/^my:requests:([0-9]{1,4})$/',$action,$m) || preg_match('/^my:(circle|courses):([a-f0-9]{32})$/',$action,$m)){
            $this->memberArea($action);return;
        }
        if($action==='about') {$this->say("به آکادمی منتوریس خوش آمدید 🌱\nفضایی برای یادگیری، ارتباط و رشد حرفه‌ای درمانگران و دانشجویان.\nدر تمرین عامدانه، یک مهارت مشخص را انتخاب می‌کنیم، آن را در سطح مناسب تمرین می‌کنیم و با بازخورد، مسیر پیشرفت را مرور می‌کنیم.",$this->buttons([[$this->link('آشنایی با منتوریس','/about')],[$this->link('تمرین عامدانه و تبحر','/articles/deliberate-practice-first-step-mastery')],$this->returnMenu()]));return;}
        if($action==='connect') {$this->hub->state($this->account,'connect');$this->say('برای تطبیق با حساب عضو، شمارهٔ خودتان را با دکمه بفرستید. برای دسترسی مدیریتی، کد صفحهٔ اتصال سایت لازم است.',['keyboard'=>[[['text'=>'اشتراک شمارهٔ من','request_contact'=>true]]],'resize_keyboard'=>true,'one_time_keyboard'=>true]);$this->say('اتصال از پنل:',$this->buttons([[$this->link('اتصال امن حساب سایت','/messengers')],$this->returnMenu()]));return;}
        if($action==='faq') {$rows=[];foreach(CommunicationFaq::topics() as $key=>$topic)$rows[]=[['text'=>$topic['title'],'callback_data'=>'faq:'.$key]];$rows[]=$this->returnMenu();$this->say('موضوع پرسش را انتخاب کنید:',$this->buttons($rows));return;}
        if(str_starts_with($action,'faq:')) {$topic=CommunicationFaq::topics()[substr($action,4)] ?? null;if(!$topic) return;$text=$topic['title']."\n";foreach($topic['items'] as $item)$text.="\n".$item['q']."\n".$item['a']."\n";$this->say($text,$this->buttons([[['text'=>'پشتیبانی انسانی','callback_data'=>'support']],$this->returnMenu()]));return;}
        if($action==='tools') {$this->say('سه ابزار رایگان خودارزیابی؛ نتیجه آموزشی و قابل چاپ.',$this->buttons([[$this->link('ابزارهای خودارزیابی','/resources?lang=fa')],$this->returnMenu()]));return;}
        if($action==='events' || $action==='courses') {
            $items=$action==='events'?PublicContentService::events():PublicContentService::courses();$rows=[];
            foreach(array_slice($items,0,20) as $item)$rows[]=[['text'=>($action==='events' && in_array($item['status']??'',['completed','canceled'],true)?'پایان‌یافته · ':'').mb_substr($item['title'],0,45),'callback_data'=>($action==='events'?'e:':'c:').TelegramBotService::eventKey($item['slug'])]];
            $rows[]=$this->returnMenu();$this->say($items?'گزینهٔ موردنظر را انتخاب کنید:':'برنامهٔ تازه پس از آماده‌شدن در سایت اعلام می‌شود.',$this->buttons($rows));return;
        }
        if(str_starts_with($action,'e:') || str_starts_with($action,'c:')) {
            $event=str_starts_with($action,'e:');$items=$event?PublicContentService::events():PublicContentService::courses();
            foreach($items as $item)if(hash_equals(TelegramBotService::eventKey($item['slug']),substr($action,2))) {
                $text=$item['title']."\n".mb_substr($item['description'] ?? $item['short_description'] ?? '',0,650);
                if($event)$text.="\n".($item['date'] ?? '').' | '.($item['time'] ?? '')."\n".($item['location'] ?? '')."\n".mb_substr($item['registration_note'] ?? '',0,400);
                else {foreach(array_slice($item['curriculum'] ?? [],0,8) as $topic)$text.="\n• ".mb_substr(is_array($topic)?($topic['title'] ?? ''):$topic,0,130);}
                $text.="\n\nهزینه: ".(!empty($item['price_amount'])?number_format((int)$item['price_amount']).' تومان':($item['price']??'در صفحه برنامه اعلام می‌شود'));
                if(!$event){$text.="\nمدت: ".($item['duration']??'اعلام می‌شود')."\nزمان: ".($item['schedule']??'اعلام می‌شود');if(in_array('installments',$item['payment_options']??[],true))$text.="\nپرداخت دو مرحله‌ای: نصف مبلغ برای هر نیمه دوره";}
                $open=$event?($item['can_register']??false):(($item['can_request']??false)||($item['can_enroll']??false));
                $text.=$open?"\nثبت‌نام: پرداخت و ارسال رسید، سپس بررسی پذیرش؛ در صورت عدم پذیرش، وجه واریزشده بازگردانده می‌شود.":"\nثبت‌نام این برنامه در حال حاضر باز نیست.";
                $this->say(mb_substr($text,0,3700),$this->buttons([[$this->link($open?'جزئیات و ثبت درخواست در سایت':'مشاهده جزئیات برنامه',($event?'/events/':'/courses/').$item['slug'])],$this->returnMenu()]));return;
            }return;
        }
        if($action==='support') {$support=new SupportRepository($this->hub);$rows=[];foreach($support->categories() as $key=>$label)$rows[]=[['text'=>$label,'callback_data'=>'support:'.$key]];$rows[]=[['text'=>'گفتگوهای من','callback_data'=>'tickets']];$rows[]=$this->returnMenu();$this->say('موضوع پشتیبانی را انتخاب کنید:',$this->buttons($rows));return;}
        if(str_starts_with($action,'support:')) {$key=substr($action,8);if(!isset((new SupportRepository($this->hub))->categories()[$key]))return;$this->hub->state($this->account,'new_support',['category'=>$key]);$this->say('پیام متنی، صوتی، تصویر یا فایل تا ۸ مگابایت بفرستید. تیم پشتیبانی از همین گفتگو پاسخ می‌دهد. اطلاعات خصوصی مراجعان را ارسال نکنید. لغو: /cancel');return;}
        if($action==='tickets') {$rows=$this->hub->rows('SELECT id,subject,status FROM support_tickets WHERE (channel=:channel AND chat_id=:chat) OR user_id=:user ORDER BY updated_at DESC LIMIT 10',['channel'=>$this->channel,'chat'=>$this->account['chat_id'],'user'=>$this->user['id'] ?? '']);$buttons=[];foreach($rows as $row)$buttons[]=[['text'=>mb_substr($row['subject'],0,40).' · '.$row['status'],'callback_data'=>'t:'.$row['id']]];$buttons[]=$this->returnMenu();$this->say($rows?'گفتگوهای شما:':'هنوز تیکتی ندارید.',$this->buttons($buttons));return;}
        if(str_starts_with($action,'t:')) {$ticket=(new SupportRepository($this->hub))->find(substr($action,2));if(!$ticket || !$this->ownsTicket($ticket)) throw new \RuntimeException('گفتگو در دسترس نیست.');if($ticket['status']==='closed')throw new \RuntimeException('این تیکت بسته شده است.');$this->hub->state($this->account,'ticket',['id'=>$ticket['id']]);$this->say('پیام بعدی شما به «'.$ticket['subject'].'» اضافه می‌شود. /cancel');return;}
        if($action==='notify') {$this->say('اعلان‌های اختصاصی: '.(!empty($this->account['notifications_enabled'])?'فعال':'خاموش')."\nبرای دریافت نتیجه ثبت‌نام و پرداخت، اعلان‌ها را فعال نگه دارید.",$this->buttons([[['text'=>'فعال','callback_data'=>'notify:on'],['text'=>'خاموش','callback_data'=>'notify:off']],$this->returnMenu()]));return;}
        if(in_array($action,['notify:on','notify:off'],true)) {$this->hub->execute('UPDATE comm_accounts SET notifications_enabled=:enabled WHERE channel=:channel AND chat_id=:chat',['enabled'=>$action==='notify:on'?1:0,'channel'=>$this->channel,'chat'=>$this->account['chat_id']]);$this->say('تنظیم اعلان ذخیره شد.');return;}
        $this->adminCallback($action);
    }
    private function denied(): void { $this->say('ابتدا از حساب مدیر سایت، صفحهٔ اتصال پیام‌رسان را باز کنید. دسترسی فعلی شما این عملیات را مجاز نمی‌کند.',$this->buttons([[$this->link('اتصال حساب','/messengers')],$this->returnMenu()])); }
    private function confirm(string $id,string $text,array $actor): void {$this->say($text,$this->buttons([[['text'=>'تأیید عملیات','callback_data'=>'do:'.$id]],[['text'=>'انصراف','callback_data'=>'admin']]]),$actor);}
    private function adminMenu(bool $stats=false): void
    {
        $actor=$this->admin->authorized($this->account,'admin.access');if(!$actor){$this->denied();return;}
        $rows=[];$text='مدیریت منتوریس · '.$actor['name'];
        if(Authorization::can($actor,'analytics.view')){$counts=$this->hub->pendingCounts();$text.="\nاعضا: ".$counts['users']."\nحلقه‌های در انتظار: ".$counts['circle']."\nدرخواست دوره: ".$counts['courses']."\nتیکت‌های باز: ".$counts['tickets']."\nارسال ناموفق: ".$counts['failed'];}
        if(Authorization::can($actor,'engagements.view')){$rows[]=[['text'=>'درخواست‌های حلقه','callback_data'=>'pending:circle'],['text'=>'درخواست‌های دوره','callback_data'=>'pending:courses']];$rows[]=[['text'=>'صندوق پشتیبانی','callback_data'=>'admin:tickets'],['text'=>'اعلان مدیریتی','callback_data'=>'admin:alerts']];$rows[]=[['text'=>'وضعیت ارسال اعلان‌ها','callback_data'=>'admin:delivery']];}
        if(Authorization::can($actor,'payments.manage')){$rows[]=[['text'=>'رسیدها و وضعیت پرداخت','callback_data'=>'admin:receipts:pending:0'],['text'=>'بازگشت وجه','callback_data'=>'admin:refunds']];$counts=$this->hub->pendingCounts();$text.="\nرسیدهای منتظر تطبیق: ".$counts['receipts']."\nبازگشت وجه نیازمند اقدام: ".$counts['refunds'];}
        if(Authorization::can($actor,'content.manage')){$rows[]=[['text'=>'محتوای سایت','callback_data'=>'admin:content'],['text'=>'پیش‌نویس مقاله','callback_data'=>'admin:draft']];}
        if(Authorization::can($actor,'users.view'))$rows[]=[['text'=>'🔎 جست‌وجوی عضو با شماره','callback_data'=>'admin:search']];
        $rows[]=[$this->link('پنل کامل سایت','/admin')];$rows[]=$this->returnMenu();$this->say($text,$this->buttons($rows),$actor);
    }
    private function adminCallback(string $action): void
    {
        if($action==='admin:delivery'){
            $actor=$this->admin->authorized($this->account,'engagements.view');if(!$actor){$this->denied();return;}
            $counts=array_column($this->hub->rows('SELECT status,COUNT(*) total FROM comm_outbox WHERE channel=:channel GROUP BY status',['channel'=>$this->channel]),'total','status');
            $last=$this->hub->one("SELECT MAX(updated_at) last_sent FROM comm_outbox WHERE channel=:channel AND status='sent'",['channel'=>$this->channel]);
            $text="📨 وضعیت واقعی صف پیام‌رسان\nدر صف: ".($counts['queued']??0)."\nدر حال ارسال: ".($counts['processing']??0)."\nارسال موفق: ".($counts['sent']??0)."\nناموفق: ".($counts['failed']??0)."\nلغوشده به‌دلیل وضعیت یا دسترسی: ".($counts['canceled']??0)."\nآخرین ارسال موفق: ".(!empty($last['last_sent'])?CommunicationDetailService::time($last['last_sent']).' تهران':'هنوز ثبت نشده')."\nثبت در صف به معنی تحویل پیام نیست. خطاها و اتصال هاست را از مرکز ارتباطات سایت بررسی کنید.";
            $this->say($text,$this->buttons([[['text'=>'به‌روزرسانی وضعیت','callback_data'=>'admin:delivery']],[$this->link('مرکز ارتباطات و بررسی اتصال','/admin/communications')],[['text'=>'مدیریت','callback_data'=>'admin']]]),$actor);return;
        }
        if($action==='admin:search'){$actor=$this->admin->authorized($this->account,'users.view');if(!$actor){$this->denied();return;}$this->hub->state($this->account,'admin_search');$this->say('شماره موبایل عضو را بفرستید؛ ارقام فارسی و +۹۸ هم پذیرفته می‌شوند. لغو: /cancel',$this->buttons([[['text'=>'بازگشت به مدیریت','callback_data'=>'admin']]]),$actor);return;}
        if(preg_match('/^admin:receipts:(pending|approved|rejected):([0-9]{1,4})$/',$action,$match)){
            $actor=$this->admin->authorized($this->account,'payments.manage');if(!$actor){$this->denied();return;}
            if(!(new ManualPaymentService($this->hub))->available()){$this->say('بخش رسیدها هنوز آماده نیست.',null,$actor);return;}
            $page=(int)$match[2];$offset=$page*5;$items=$this->hub->rows("SELECT p.*,u.name member_name FROM manual_payments p LEFT JOIN users u ON u.id=p.user_id WHERE p.status=:status ORDER BY p.updated_at DESC,p.id DESC LIMIT 6 OFFSET $offset",['status'=>$match[1]]);
            $rows=[[['text'=>'منتظر بررسی','callback_data'=>'admin:receipts:pending:0'],['text'=>'تأییدشده','callback_data'=>'admin:receipts:approved:0'],['text'=>'ردشده','callback_data'=>'admin:receipts:rejected:0']]];
            foreach(array_slice($items,0,5) as $item)$rows[]=[['text'=>mb_substr($item['member_name']?:'عضو',0,22).' · '.mb_substr($item['item_title'],0,20).' · '.number_format((int)$item['amount']).' تومان · بخش '.$item['part'],'callback_data'=>'mpd:'.$item['id']]];
            $nav=[];if($page>0)$nav[]=['text'=>'قبلی','callback_data'=>'admin:receipts:'.$match[1].':'.($page-1)];if(count($items)>5)$nav[]=['text'=>'بعدی','callback_data'=>'admin:receipts:'.$match[1].':'.($page+1)];if($nav)$rows[]=$nav;$rows[]=[['text'=>'مدیریت','callback_data'=>'admin']];
            $this->say('رسیدهای '.(['pending'=>'منتظر بررسی','approved'=>'تأییدشده','rejected'=>'ردشده'][$match[1]])."\n".($items?'رسید را برای دیدن مشخصات عضو، تصویر و تصمیم‌گیری انتخاب کنید.':'رسیدی با این وضعیت نداریم.'),$this->buttons($rows),$actor);return;
        }
        if(preg_match('/^mpd:([a-f0-9]{32})$/',$action,$match)){
            $actor=$this->admin->authorized($this->account,'payments.manage');if(!$actor){$this->denied();return;}$row=(new ManualPaymentService($this->hub))->find($match[1]);if(!$row)throw new \RuntimeException('رسید در دسترس نیست.');
            $rows=[[['text'=>'تصویر رسید در ربات','callback_data'=>'mpv:'.$row['id']]],[$this->link('بررسی کامل و بازگشت وجه','/admin/manual-payments/'.$row['id'])]];
            if($row['status']==='pending'){$rows[]=[['text'=>'تأیید واریز','callback_data'=>'mpa:'.$row['id'].':'.$row['version']],['text'=>'رد رسید','callback_data'=>'mpr:'.$row['id'].':'.$row['version']]];if(Authorization::can($actor,'engagements.manage'))$rows[]=[['text'=>'تأیید واریز و پذیرش','callback_data'=>'mpx:'.$row['id'].':'.$row['version']]];}
            $rows[]=[['text'=>'فهرست رسیدها','callback_data'=>'admin:receipts:'.$row['status'].':0']];$this->say((new CommunicationDetailService($this->hub))->receipt($row),$this->buttons($rows),$actor);return;
        }
        if(preg_match('/^mpv:([a-f0-9]{32})$/',$action,$match)){
            $actor=$this->admin->authorized($this->account,'payments.manage');if(!$actor){$this->denied();return;}$row=(new ManualPaymentService($this->hub))->find($match[1]);if(!$row)return;$actor['__permission']='payments.manage';$this->hub->queue($this->channel,$this->account['chat_id'],'_manual_receipt',['payment_id'=>$row['id'],'version'=>$row['version']],null,$actor);return;
        }
        if(preg_match('/^admin:refunds(?::([0-9]{1,4}))?$/',$action,$match)){
            $actor=$this->admin->authorized($this->account,'payments.manage');if(!$actor){$this->denied();return;}
            if(!(new PaymentLifecycleService($this->hub))->available()){$this->say('پیگیری بازگشت وجه پس از به‌روزرسانی پایگاه داده فعال می‌شود.',null,$actor);return;}
            $page=(int)($match[1]??0);$offset=$page*5;$items=$this->hub->rows("SELECT r.*,p.item_title,u.name,u.phone FROM payment_refunds r JOIN manual_payments p ON p.id=r.payment_id JOIN users u ON u.id=r.user_id WHERE r.status='pending' ORDER BY r.created_at,r.id LIMIT 6 OFFSET $offset");
            foreach(array_slice($items,0,5) as $item)$this->say('🔴 بازگشت وجه در انتظار اقدام'."\nعضو: ".$item['name']."\nشماره: ".$item['phone']."\nبرنامه: ".$item['item_title']."\nمبلغ: ".number_format((int)$item['amount']).' تومان',$this->buttons([[$this->link('اطلاعات رسید و ثبت واریز برگشتی','/admin/manual-payments/'.$item['payment_id'])]]),$actor);
            $rows=[];if($page>0)$rows[]=['text'=>'قبلی','callback_data'=>'admin:refunds:'.($page-1)];if(count($items)>5)$rows[]=['text'=>'بعدی','callback_data'=>'admin:refunds:'.($page+1)];$this->say($items?'پرونده‌های بازگشت وجه:':'بازگشت وجه معوق نداریم.',$this->buttons([$rows?:[ $this->link('همه پرونده‌ها در سایت','/admin/manual-payments?status=refunds')]]),$actor);return;
        }
        if(preg_match('/^(mpa|mpr|mpx):([a-f0-9]{32}):([1-9][0-9]{0,9})$/',$action,$match)) {
            $actor=$this->admin->authorized($this->account,'payments.manage');if(!$actor){$this->denied();return;}
            $row=(new ManualPaymentService($this->hub))->find($match[2]);if(!$row || $row['status']!=='pending')throw new \RuntimeException('رسید قبلاً بررسی شده یا در دسترس نیست.');
            if((int)$row['version']!==(int)$match[3])throw new \RuntimeException('تصویر رسید تغییر کرده؛ پیام جدید و تصویر تازه را بررسی کنید.');
            $combined=$match[1]==='mpx';if($combined && !Authorization::can($actor,'engagements.manage')){$this->denied();return;}$status=$match[1]==='mpr'?'rejected':'approved';
            $id=$this->hub->prepareAction($this->account,$actor,$combined?'payment_admission':'manual_payment',['id'=>$row['id'],'version'=>$row['version'],'status'=>$status,'type'=>'payment']);
            $this->confirm($id,($combined?'تأیید واریز و پذیرش':($status==='approved'?'تأیید دریافت وجه':'رد رسید')).'؛ '.number_format((int)$row['amount']).' تومان. رسید را با گردش حساب تطبیق داده‌اید؟',$actor);return;
        }
        if($action==='admin'){$this->adminMenu();return;}
        if(str_starts_with($action,'do:')) {$actor=$this->admin->authorized($this->account,'admin.access');if(!$actor){$this->denied();return;}$this->say($this->admin->execute($this->account,substr($action,3)),$this->buttons([[['text'=>'مدیریت','callback_data'=>'admin']]]),$actor);return;}
        if(in_array($action,['admin:alerts','admin:alerts:on','admin:alerts:off'],true)){
            $actor=$this->admin->authorized($this->account,'engagements.view');if(!$actor){$this->denied();return;}
            if($action!=='admin:alerts'){$enabled=$action==='admin:alerts:on'?1:0;$this->hub->execute("UPDATE comm_accounts SET admin_alerts=:enabled,notifications_enabled=CASE WHEN :turn_on='1' THEN 1 ELSE notifications_enabled END WHERE channel=:channel AND chat_id=:chat",['enabled'=>$enabled,'turn_on'=>$enabled,'channel'=>$this->channel,'chat'=>$this->account['chat_id']]);$this->account=$this->hub->account($this->channel,$this->account['chat_id']);}
            $this->say('اعلان مدیریتی: '.(!empty($this->account['admin_alerts'])?'فعال':'خاموش')."\nاعلان عمومی ربات: ".(!empty($this->account['notifications_enabled'])?'فعال':'خاموش')."\nبرای دریافت رسیدها و درخواست‌ها، هر دو باید فعال باشند. روشن‌کردن اعلان مدیریتی، هر دو را فعال می‌کند.",$this->buttons([[['text'=>'فعال‌کردن اعلان مدیریتی','callback_data'=>'admin:alerts:on']],[['text'=>'خاموش‌کردن اعلان مدیریتی','callback_data'=>'admin:alerts:off']],[['text'=>'مدیریت','callback_data'=>'admin']]]),$actor);return;
        }
        if(preg_match('/^pending:(circle|courses)(?::([0-9]{1,4}))?$/',$action,$match)) {
            $actor=$this->admin->authorized($this->account,'engagements.view');if(!$actor){$this->denied();return;}$type=$match[1];$table=$type==='circle'?'event_signups':'course_requests';$page=(int)($match[2]??0);$offset=$page*5;
            $items=(new ManualPaymentService($this->hub))->available()?$this->hub->rows("SELECT * FROM $table WHERE status='requested' AND EXISTS (SELECT 1 FROM manual_payments p WHERE p.request_type=:type AND p.request_id=$table.id AND p.part=1 AND p.status='approved') ORDER BY created_at,id LIMIT 6 OFFSET $offset",['type'=>$type]):[];
            foreach(array_slice($items,0,5) as $item){$rows=[[$this->link('اطلاعات کامل، رزومه و تصمیم پذیرش','/admin/requests/'.$type.'/'.$item['id'])]];if(Authorization::can($actor,'engagements.manage'))$rows[]=[['text'=>'تأیید پذیرش','callback_data'=>'p:'.($type==='circle'?'c':'k').':'.$item['id'].':a'],['text'=>'رد پذیرش','callback_data'=>'p:'.($type==='circle'?'c':'k').':'.$item['id'].':r']];$this->say((new CommunicationDetailService($this->hub))->request($type,$item['id']),$this->buttons($rows),$actor);}
            $rows=[];if($page>0)$rows[]=['text'=>'قبلی','callback_data'=>'pending:'.$type.':'.($page-1)];if(count($items)>5)$rows[]=['text'=>'بعدی','callback_data'=>'pending:'.$type.':'.($page+1)];$this->say($items?'مشاهده درخواست‌های دیگر:':'درخواستی در انتظار بررسی نیست.',$this->buttons([$rows?:[['text'=>'مدیریت','callback_data'=>'admin']]]),$actor);return;
        }
        if(preg_match('/^p:([ck]):([a-f0-9]{32}):([ar])$/',$action,$m)) {
            $actor=$this->admin->authorized($this->account,'engagements.manage');if(!$actor){$this->denied();return;}$type=$m[1]==='c'?'circle':'courses';$table=$m[1]==='c'?'event_signups':'course_requests';$row=$this->hub->one("SELECT * FROM $table WHERE id=:id",['id'=>$m[2]]);if(!$row)throw new \RuntimeException('درخواست در دسترس نیست.');
            $status=$m[3]==='a'?'approved':'rejected';$id=$this->hub->prepareAction($this->account,$actor,'engagement',['type'=>$type,'id'=>$row['id'],'status'=>$status,'previous'=>$row['status']]);$this->confirm($id,($status==='approved'?'تأیید':'رد').' درخواست '.$row['name'].'؟',$actor);return;
        }
        if(preg_match('/^admin:tickets(?::(active|answered|closed):([0-9]{1,4}))?$/',$action,$match)){
            $actor=$this->admin->authorized($this->account,'engagements.view');if(!$actor){$this->denied();return;}$status=$match[1]??'active';$page=(int)($match[2]??0);$offset=$page*8;
            $where=['active'=>"t.status IN ('open','waiting')",'answered'=>"t.status='answered'",'closed'=>"t.status='closed'"][$status];
            $items=$this->hub->rows("SELECT t.id,t.subject,t.status,u.name FROM support_tickets t LEFT JOIN users u ON u.id=t.user_id WHERE $where ORDER BY t.updated_at DESC,t.id DESC LIMIT 9 OFFSET $offset");
            $rows=[[['text'=>'نیازمند پاسخ','callback_data'=>'admin:tickets:active:0'],['text'=>'پاسخ‌داده‌شده','callback_data'=>'admin:tickets:answered:0'],['text'=>'بسته','callback_data'=>'admin:tickets:closed:0']]];
            foreach(array_slice($items,0,8) as $item)$rows[]=[['text'=>mb_substr(($item['name']?:'عضو پیام‌رسان').' · '.$item['subject'],0,55),'callback_data'=>'at:'.$item['id']]];
            $nav=[];if($page>0)$nav[]=['text'=>'قبلی','callback_data'=>'admin:tickets:'.$status.':'.($page-1)];if(count($items)>8)$nav[]=['text'=>'بعدی','callback_data'=>'admin:tickets:'.$status.':'.($page+1)];if($nav)$rows[]=$nav;$rows[]=[['text'=>'مدیریت','callback_data'=>'admin']];
            $this->say($items?'گفتگو را برای دیدن آخرین پیام‌ها و پاسخ انتخاب کنید:':'گفتگویی با این وضعیت نداریم.',$this->buttons($rows),$actor);return;
        }
        if(str_starts_with($action,'at:')) {$actor=$this->admin->authorized($this->account,'engagements.view');if(!$actor){$this->denied();return;}$support=new SupportRepository($this->hub);$ticket=$support->find(substr($action,3));if(!$ticket)return;$text=$ticket['subject']."\n".($ticket['user_name'] ?: 'عضو پیام‌رسان')."\n";$items=array_slice($support->messages($ticket['id']),-3);foreach($items as $item){$text.="\n".($item['sender_role']==='staff'?'تیم: ':'عضو: ').CommunicationDetailService::time($item['created_at'])."\n".mb_substr($item['body'],0,850);foreach($item['attachments'] as $attachment)$text.="\nفایل: ".$attachment['filename'];}
            $rows=[];if(Authorization::can($actor,'engagements.manage') && $ticket['status']!=='closed')$rows[]=[['text'=>'پاسخ','callback_data'=>'reply:'.$ticket['id']],['text'=>'بستن تیکت','callback_data'=>'close:'.$ticket['id']]];$rows[]=[$this->link('متن کامل و فایل‌ها','/admin/support/'.$ticket['id'])];$rows[]=[['text'=>'صندوق پشتیبانی','callback_data'=>'admin:tickets']];$this->say(mb_substr($text,0,3700),$this->buttons($rows),$actor);return;}
        if(str_starts_with($action,'reply:')) {$actor=$this->admin->authorized($this->account,'engagements.manage');if(!$actor){$this->denied();return;}$ticket=(new SupportRepository($this->hub))->find(substr($action,6));if(!$ticket) return;$this->hub->state($this->account,'admin_reply',['id'=>$ticket['id']]);$this->say('پاسخ متنی یا فایل را بفرستید؛ قبل از ارسال نهایی تأیید می‌گیرید. /cancel',null,$actor);return;}
        if(str_starts_with($action,'close:')) {$actor=$this->admin->authorized($this->account,'engagements.manage');if(!$actor){$this->denied();return;}$id=$this->hub->prepareAction($this->account,$actor,'ticket_close',['id'=>substr($action,6)]);$this->confirm($id,'این تیکت بسته شود؟',$actor);return;}
        if($action==='admin:draft') {$actor=$this->admin->authorized($this->account,'content.manage');if(!$actor){$this->denied();return;}$this->hub->state($this->account,'article_title');$this->say('عنوان مقاله را بفرستید. لغو: /cancel',null,$actor);return;}
        if($action==='admin:content') {$actor=$this->admin->authorized($this->account,'content.manage');if(!$actor){$this->denied();return;}$items=$this->hub->rows("SELECT e.id,e.entity_type,e.status,t.title FROM content_entities e JOIN content_translations t ON t.entity_id=e.id AND t.locale='fa' ORDER BY e.updated_at DESC LIMIT 8");foreach($items as $item)$this->say($item['title'].' | '.$item['status'],$this->buttons([[['text'=>'انتشار','callback_data'=>'pub:'.$item['id']],['text'=>'بایگانی','callback_data'=>'arc:'.$item['id']]]]),$actor);return;}
        if(preg_match('/^(pub|arc):([a-f0-9]{32})$/',$action,$m)) {$actor=$this->admin->authorized($this->account,'content.manage');if(!$actor){$this->denied();return;}$row=$this->hub->one('SELECT status,slug FROM content_entities WHERE id=:id',['id'=>$m[2]]);if(!$row)return;$id=$this->hub->prepareAction($this->account,$actor,'content_status',['id'=>$m[2],'status'=>$m[1]==='pub'?'published':'archived','previous'=>$row['status'],'type'=>'content']);$this->confirm($id,($m[1]==='pub'?'انتشار':'بایگانی').' «'.$row['slug'].'» تأیید شود؟',$actor);return;}
        $this->say('این دکمه قدیمی یا در دسترس نیست؛ از منوی تازه ادامه دهید.',$this->buttons([$this->returnMenu()]));
    }
    private function menu(): void
    {
        $this->hub->state($this->account,'');$this->say('منتوریس 🌱 کنار شما برای یادگیری، ارتباط و رشد حرفه‌ای.',['remove_keyboard'=>true]);
        $rows=[[['text'=>'🟢 معرفی آکادمی','callback_data'=>'about']],[['text'=>'👥 حلقه درمانگران','callback_data'=>'events'],['text'=>'📚 دوره‌ها','callback_data'=>'courses']],[['text'=>'👤 حساب من','callback_data'=>'my:account'],['text'=>'🧾 ثبت‌نام‌ها و پرداخت‌ها','callback_data'=>'my:requests:0']],[$this->link('پنل کامیونیتی و ورود','/profile')],[['text'=>'❓ پرسش‌های متداول','callback_data'=>'faq'],['text'=>'📞 پشتیبانی انسانی','callback_data'=>'support']],[['text'=>'اتصال حساب و شماره','callback_data'=>'connect'],['text'=>'اعلان‌های من','callback_data'=>'notify']],[['text'=>'ابزارهای کاربردی','callback_data'=>'tools']]];
        if($this->admin->authorized($this->account,'admin.access'))$rows[]=[['text'=>'⚙️ مدیریت سایت','callback_data'=>'admin']];
        $this->say('از کجا شروع کنیم؟',$this->buttons($rows));
    }
}

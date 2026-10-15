<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Session,Authorization};
use App\Repositories\CommunicationRepository;
use App\Services\{AuthService,MessengerApi,CommunicationWorker,CommunicationFaq};
final class CommunicationController extends Controller
{
    private static bool $deliveryScheduled=false;
    public function connections(Request $request): Response
    {
        $user=(new AuthService())->user();if(!$user)return $this->redirect('/login');$hub=new CommunicationRepository();$ready=$hub->available();
        return $this->view('pages.messenger-connections',['title'=>'اتصال پیام‌رسان‌ها | منتوریس','indexable'=>false,'user'=>$user,'ready'=>$ready,'links'=>$ready?$hub->links($user['id']):[],'pending'=>(new Session())->pull('messenger.pending'),'notice'=>(new Session())->pull('messenger.notice'),'baleConfigured'=>(new MessengerApi('bale'))->configured()])->withHeader('Cache-Control','private, no-store');
    }
    public function issue(Request $request): Response
    {
        $user=(new AuthService())->user();if(!$user)return $this->redirect('/login');$channel=is_string($request->input('channel'))?$request->input('channel'):'';$hub=new CommunicationRepository();
        if($hub->available() && in_array($channel,['telegram','bale'],true)) {
            $token=$hub->issueLink($user,$channel);(new Session())->put('messenger.pending',['channel'=>$channel,'token'=>$token,'expires'=>time()+600]);
        }else (new Session())->put('messenger.notice','ابتدا SQL شمارهٔ ۰۱۰ را نصب کنید.');
        return $this->redirect('/messengers',303);
    }
    public function disconnect(Request $request): Response
    {
        $user=(new AuthService())->user();if(!$user)return $this->redirect('/login');$channel=is_string($request->input('channel'))?$request->input('channel'):'';$hub=new CommunicationRepository();
        if($hub->available() && in_array($channel,['telegram','bale'],true)){$hub->disconnect($user['id'],$channel);(new Session())->put('messenger.notice','اتصال و دسترسی این پیام‌رسان قطع شد.');}
        return $this->redirect('/messengers',303);
    }
    public function webhookBale(Request $request): Response
    {
        $secret=(string)env('BALE_WEBHOOK_SECRET','');$given=(string)$request->route('secret');
        if(!preg_match('/^[a-f0-9]{64}$/',$secret) || !hash_equals($secret,$given)) return $this->json(['ok'=>false],401);
        if(strlen($request->raw())>65536)return $this->json(['ok'=>false],413);$update=json_decode($request->raw(),true);
        if(!is_array($update) || !is_int($update['update_id'] ?? null) || $update['update_id']<0)return $this->json(['ok'=>false],422);
        $hub=new CommunicationRepository();if(!$hub->available())return $this->json(['ok'=>false],503);$hub->enqueue('bale',$update);self::afterWebhook();return $this->json(['ok'=>true]);
    }
    public static function afterWebhook(): void
    {
        // Shared hosts often use LiteSpeed rather than PHP-FPM. Process interactive
        // replies after acknowledging the webhook; Cron still recovers queued work.
        if(PHP_SAPI==='cli' || self::$deliveryScheduled)return;
        self::$deliveryScheduled=true;
        register_shutdown_function(static function(): void {
            ignore_user_abort(true);
            if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
            if(function_exists('fastcgi_finish_request'))fastcgi_finish_request();
            elseif(function_exists('litespeed_finish_request'))litespeed_finish_request();
            else {
                while(ob_get_level()>0){if(!@ob_end_flush())break;}
                flush();
            }
            try{(new CommunicationWorker())->run(8,8);}catch(\Throwable) {error_log('Webhook reply processing deferred to Cron.');}
        });
    }
    public function help(Request $request): Response { if(\App\Core\Translator::locale()!=='fa')return $this->redirect('/help?lang=fa',301);return $this->view('pages.communication-help',['title'=>'پرسش‌های متداول و پشتیبانی منتوریس','description'=>'راهنمای ثبت‌نام، گواهی، منتورینگ، ضبط جلسات و ارتباط با پشتیبانی انسانی منتوریس.','seoLanguages'=>['fa'],'topics'=>CommunicationFaq::topics()]); }
    public function admin(Request $request): Response
    {
        $user=(new AuthService())->user();if(!$user || !Authorization::can($user,'engagements.view'))return Response::html('دسترسی مجاز نیست.',403);
        $hub=new CommunicationRepository();$ready=$hub->available();$health=[];
        foreach(['telegram','bale'] as $channel)$health[$channel]=['configured'=>(new MessengerApi($channel))->configured(),'secret'=>trim((string)env(strtoupper($channel).'_WEBHOOK_SECRET',''))!==''];
        $workerFile=BASE_PATH.'/storage/data/communications-worker-status.json';$worker=is_file($workerFile)?json_decode((string)file_get_contents($workerFile),true):null;
        return $this->view('admin.communications',['title'=>'مرکز پیام‌رسان‌ها','admin'=>$user,'ready'=>$ready,'counts'=>$ready?$hub->pendingCounts():[],'health'=>$health,'worker'=>$worker,'notice'=>(new Session())->pull('communications.notice')],'layouts.admin');
    }
    public function retry(Request $request): Response
    {
        $user=(new AuthService())->user();if(!$user || !Authorization::can($user,'engagements.manage'))return Response::html('دسترسی مجاز نیست.',403);
        $hub=new CommunicationRepository();if($hub->available()) foreach(['comm_updates','comm_outbox'] as $table)$hub->execute("UPDATE $table SET status='queued',attempts=0,next_attempt_at=:now WHERE status='failed'",['now'=>\App\Core\Database::now()]);
        \App\Core\Audit::record('communications.retry','communications_queue',null,$user['id'],[],[],$request->ip());(new Session())->put('communications.notice','ارسال‌های ناموفق برای بررسی دوباره در صف قرار گرفتند.');return $this->redirect('/admin/communications',303);
    }
    public function diagnose(Request $request): Response
    {
        $admin=(new AuthService())->user();if(!$admin || !Authorization::can($admin,'engagements.manage'))return Response::html('دسترسی مجاز نیست.',403);
        $channel=$request->input('channel');if(!in_array($channel,['telegram','bale'],true))return Response::html('پیام‌رسان معتبر نیست.',422);
        try{
            $api=new MessengerApi($channel);$me=$api->call('getMe');$hook=$api->call('getWebhookInfo');$hub=new CommunicationRepository();$receivers=0;
            foreach($hub->rows('SELECT * FROM comm_accounts WHERE channel=:channel AND admin_alerts=1 AND notifications_enabled=1',['channel'=>$channel]) as $account){$user=$hub->linkedUser($account);if($user && $account['link_method']==='site' && (Authorization::can($user,'payments.manage')||Authorization::can($user,'engagements.view')))$receivers++;}
            $notice='اتصال API برقرار است. ربات: '.preg_replace('/[^a-zA-Z0-9_]/','',(string)($me['username']??'')).' | وب‌هوک: '.(!empty($hook['url'])?'ثبت شده':'ثبت نشده').' | آپدیت منتظر: '.(int)($hook['pending_update_count']??0).' | مدیر دریافت‌کننده اعلان: '.$receivers;
            if(!$receivers)$notice.='؛ حساب مدیر را از «اتصال امن حساب من» وصل و اعلان مدیریتی ربات را روشن کنید.';
            if(!empty($hook['last_error_date']))$notice.='؛ آخرین خطای وب‌هوک در '.gmdate('Y-m-d H:i',(int)$hook['last_error_date']).' UTC ثبت شده است.';
        }catch(\RuntimeException $e){$notice=$e->getCode()===401?'توکن ربات از سوی سرویس پذیرفته نشد.':'ارتباط سرور با API پیام‌رسان برقرار نشد؛ تنظیمات و اتصال شبکه هاست بررسی شود.';}
        (new Session())->put('communications.notice',$notice);return $this->redirect('/admin/communications',303);
    }
}

<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Audit;
use App\Core\Session;
use App\Repositories\TelegramRepository;
use App\Services\AuthService;

final class AdminTelegramController extends Controller
{
    public function index(Request $request): Response
    {
        $filters=(array)$request->query();
        return $this->view('admin.telegram',['title'=>'همراه تلگرامی منتوریس','admin'=>(new AuthService())->user(),'filters'=>$filters,'report'=>(new TelegramRepository())->dashboard($filters),'notice'=>(new Session())->pull('telegram.notice'),'configured'=>trim((string)env('TELEGRAM_BOT_TOKEN',''))!==''],'layouts.admin');
    }
    public function answer(Request $request): Response
    {
        $answer=trim(is_string($request->input('answer')) ? $request->input('answer') : '');
        $notice='پاسخ باید بین ۳ و ۳۰۰۰ کاراکتر باشد.';
        if(mb_strlen($answer)>=3 && mb_strlen($answer)<=3000) {
            $repository=new TelegramRepository();
            if($repository->available() && $repository->answer((string)$request->route('id'),$answer)) {
                Audit::record('telegram.answer','telegram_question',(string)$request->route('id'),(new AuthService())->user()['id'],[],['status'=>'answered'],$request->ip());
                $notice='پاسخ در صف ارسال تلگرام قرار گرفت.';
            } else $notice='این پرسش قبلاً پاسخ داده شده یا در دسترس نیست.';
        }
        (new Session())->put('telegram.notice',$notice); return $this->redirect('/admin/telegram');
    }
    public function updateRequest(Request $request): Response
    {
        $status=(string)$request->input('status',''); $repository=new TelegramRepository();
        if($repository->available() && in_array($status,['requested','contacted','confirmed','cancelled'],true)) {
            $s=$repository->pdo()->prepare('UPDATE telegram_event_requests SET status=:status,updated_at=:now WHERE id=:id');
            $s->execute(['status'=>$status,'now'=>\App\Core\Database::now(),'id'=>$request->route('id')]);
            Audit::record('telegram.request.status','telegram_request',(string)$request->route('id'),(new AuthService())->user()['id'],[],['status'=>$status],$request->ip());
        }
        return $this->redirect('/admin/telegram');
    }
    public function retry(Request $request): Response
    {
        $repository=new TelegramRepository();
        if($repository->available()) {
            foreach(['telegram_updates','telegram_outbox'] as $table) $repository->pdo()->prepare("UPDATE $table SET status='queued',attempts=0,next_attempt_at=:now WHERE status='failed'")->execute(['now'=>\App\Core\Database::now()]);
            Audit::record('telegram.retry','telegram_queue',null,(new AuthService())->user()['id'],[],[],$request->ip());
        }
        (new Session())->put('telegram.notice','ارسال‌های ناموفق دوباره در صف قرار گرفتند.'); return $this->redirect('/admin/telegram');
    }
}

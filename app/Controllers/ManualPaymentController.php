<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Session,Authorization};
use App\Services\{AuthService,ManualPaymentService,SupportFileService};
use App\Repositories\CommunicationRepository;
final class ManualPaymentController extends Controller
{
    private function listing(Request $request,bool $history): Response
    {
        $user=(new AuthService())->user();$hub=new CommunicationRepository();$payments=new ManualPaymentService($hub);
        $page=max(1,(int)$request->query('page',1));$offset=($page-1)*20;
        $requests=$hub->rows("SELECT id,event_slug AS slug,status,'circle' AS type,created_at FROM event_signups WHERE user_id=:user UNION ALL SELECT id,course_slug,status,'courses',created_at FROM course_requests WHERE user_id=:other ORDER BY created_at DESC LIMIT 20 OFFSET $offset",['user'=>$user['id'],'other'=>$user['id']]);
        $contexts=[];foreach($requests as $row){try{$contexts[]=$payments->context($row['type'],$row['id'],$user);}catch(\RuntimeException){}}
        if(!$history)$contexts=array_values(array_filter($contexts,static function($context){return in_array($context['request']['status'],['requested','approved'],true) && (count(array_filter($context['rows'],static fn($row)=>$row['status']==='approved'))<$context['parts'] || $context['request']['status']==='requested');}));
        if(!$history && count($contexts)===1)return $this->redirect('/payments/'.$contexts[0]['type'].'/'.$contexts[0]['id']);
        return $this->view('payment.index',['title'=>$history?'سوابق پرداخت':'پرداخت ثبت‌نام','indexable'=>false,'contexts'=>$contexts,'history'=>$history,'page'=>$page,'hasMore'=>count($requests)===20,'notice'=>(new Session())->pull('manual.notice')])->withHeader('Cache-Control','private, no-store');
    }
    public function index(Request $request): Response{return $this->listing($request,false);}
    public function history(Request $request): Response{return $this->listing($request,true);}
    public function checkout(Request $request): Response
    {
        $user=(new AuthService())->user();$service=new ManualPaymentService();
        try{$context=$service->context((string)$request->route('type'),(string)$request->route('id'),$user);}catch(\RuntimeException){return Response::html('سفارش پرداخت در دسترس نیست.',404);}
        return $this->view('payment.manual',['title'=>'پرداخت '.$context['item']['title'],'indexable'=>false,'context'=>$context,'user'=>$user,'ready'=>$service->available(),'profileReady'=>ManualPaymentService::profileReady($user),'notice'=>(new Session())->pull('manual.notice'),'card'=>ManualPaymentService::CARD])->withHeader('Cache-Control','private, no-store');
    }
    public function submit(Request $request): Response
    {
        $user=(new AuthService())->user();$file=null;$saved=false;
        try {
            $service=new ManualPaymentService();$context=$service->context((string)$request->route('type'),(string)$request->route('id'),$user);
            if(!ManualPaymentService::enrollmentReady($user,$context['type']))throw new \RuntimeException('ابتدا پروفایل را تکمیل کنید.');
            $upload=$request->file('receipt');
            if(!$upload || !in_array(strtolower(pathinfo((string)($upload['name']??''),PATHINFO_EXTENSION)),['jpg','jpeg','png'],true))throw new \RuntimeException('تصویر رسید را به صورت JPG یا PNG انتخاب کنید.');
            $file=(new SupportFileService())->upload($upload);if(!$file)throw new \RuntimeException('تصویر رسید الزامی است.');
            $service->submit($context,$user,(int)$request->input('part',0),$file);$saved=true;
            CommunicationController::afterWebhook();
            (new Session())->put('manual.notice','رسید ثبت شد و منتظر بررسی مدیر است. تا اعلام نتیجه، دوباره پرداخت نکنید.');
        }catch(\RuntimeException $e){(new Session())->put('manual.notice',$e instanceof \PDOException?'ثبت رسید انجام نشد؛ دوباره تلاش کنید.':$e->getMessage());}
        finally{if($file && !$saved && preg_match('/^[a-f0-9]{32}\.bin$/',$file['local_name']))@unlink(BASE_PATH.'/storage/uploads/support/'.$file['local_name']);}
        return $this->redirect('/payments/'.rawurlencode((string)$request->route('type')).'/'.rawurlencode((string)$request->route('id')),303);
    }
    public function admin(Request $request): Response
    {
        $service=new ManualPaymentService();$status=(string)$request->query('status','pending');if(!in_array($status,['pending','approved','rejected','refunds'],true))$status='pending';
        $hub=new CommunicationRepository();$page=max(1,(int)$request->query('page',1));$offset=($page-1)*20;
        $refundReady=(new \App\Services\PaymentLifecycleService($hub))->available();
        $where=$status==='refunds'?"EXISTS (SELECT 1 FROM payment_refunds r WHERE r.payment_id=p.id AND r.status='pending')":'p.status=:status';
        $rows=$service->available() && ($status!=='refunds'||$refundReady)?$hub->rows("SELECT p.*,u.name,u.phone FROM manual_payments p JOIN users u ON u.id=p.user_id WHERE $where ORDER BY p.updated_at DESC LIMIT 20 OFFSET $offset",$status==='refunds'?[]:['status'=>$status]):[];
        $discounts=new \App\Services\DiscountCodeService($hub);foreach($rows as &$row)$row['discount']=$discounts->redemption($row['request_type'],$row['request_id']);unset($row);
        $counts=$service->available()?$hub->rows('SELECT status,COUNT(*) AS total FROM manual_payments GROUP BY status'):[];
        if($refundReady)$counts[]=['status'=>'refunds','total'=>(int)($hub->one("SELECT COUNT(*) total FROM payment_refunds WHERE status='pending'")['total']??0)];
        return $this->view('admin.manual-payments',['title'=>'بررسی رسیدهای کارت‌به‌کارت','admin'=>(new AuthService())->user(),'items'=>$rows,'counts'=>$counts,'status'=>$status,'page'=>$page,'ready'=>$service->available(),'notice'=>(new Session())->pull('manual.notice')],'layouts.admin');
    }
    public function review(Request $request): Response
    {
        try{
            $admin=(new AuthService())->user();$service=new ManualPaymentService();$id=(string)$request->route('id');$combined=$request->input('decision')==='accept';
            \App\Core\Database::transaction((new CommunicationRepository())->pdo(),function()use($service,$id,$request,$admin,$combined):void{
                $row=$service->find($id);if(!$row)throw new \RuntimeException('رسید پیدا نشد.');
                if($combined && !Authorization::can($admin,'engagements.manage'))throw new \RuntimeException('دسترسی پذیرش مجاز نیست.');
                $service->review($id,(int)$request->input('version'),$combined?'approved':(string)$request->input('status'),$admin,is_string($request->input('note'))?$request->input('note'):'',!$combined);
                if($combined){$table=$row['request_type']==='circle'?'event_signups':'course_requests';$admission=(new CommunicationRepository())->one("SELECT status FROM $table WHERE id=:id",['id'=>$row['request_id']]);if(!$admission || !in_array($admission['status'],['requested','approved'],true))throw new \RuntimeException('پذیرش این درخواست لغو یا رد شده است.');if($admission['status']==='requested'){(new \App\Repositories\AdminRepository())->updateEngagementStatus($row['request_type'],$row['request_id'],'approved');\App\Core\Audit::record('engagement.status.updated',$row['request_type'],$row['request_id'],$admin['id'],['status'=>'requested'],['status'=>'approved'],$request->ip());}}
            });
            (new Session())->put('manual.notice',$combined?'واریز و پذیرش تأیید شد؛ ثبت‌نام نهایی است.':'نتیجه بررسی ثبت شد.');
        }
        catch(\RuntimeException $e){(new Session())->put('manual.notice',$e instanceof \PDOException?'ثبت نتیجه انجام نشد.':$e->getMessage());}
        CommunicationController::afterWebhook();
        return $this->redirect('/admin/manual-payments'.($request->input('detail')==='1'?'/'.rawurlencode((string)$request->route('id')):''),303);
    }
    public function detail(Request $request): Response
    {
        $admin=(new AuthService())->user();if(!$admin || !Authorization::can($admin,'payments.manage'))return Response::html('دسترسی مجاز نیست.',403);
        $service=new ManualPaymentService();$row=$service->find((string)$request->route('id'));
        if(!$row)return Response::html('رسید پیدا نشد.',404);
        $hub=new CommunicationRepository();$member=$hub->one('SELECT id,name,email,phone FROM users WHERE id=:id',['id'=>$row['user_id']]);
        if(!$member)return Response::html('حساب عضو پیدا نشد.',404);
        try{$context=$service->context($row['request_type'],$row['request_id'],$member);}catch(\RuntimeException){$context=['discount'=>(new \App\Services\DiscountCodeService($hub))->redemption($row['request_type'],$row['request_id'])];}
        $refund=null;foreach((new \App\Services\PaymentLifecycleService($hub))->refunds($row['request_type'],$row['request_id']) as $candidate)if($candidate['payment_id']===$row['id'])$refund=$candidate;
        return $this->view('admin.payment-review',['title'=>'بررسی رسید پرداخت','admin'=>(new AuthService())->user(),'payment'=>$row,'member'=>$member,'context'=>$context,'refund'=>$refund,'notice'=>(new Session())->pull('manual.notice')],'layouts.admin')->withHeader('Cache-Control','private, no-store');
    }
    public function refund(Request $request): Response
    {
        try{(new \App\Services\PaymentLifecycleService())->complete((string)$request->route('id'),(new AuthService())->user(),is_string($request->input('reference'))?$request->input('reference'):'',is_string($request->input('note'))?$request->input('note'):'',$request->input('transferred')==='1');(new Session())->put('manual.notice','انجام بازگشت وجه با شماره پیگیری ثبت شد.');}
        catch(\RuntimeException $e){(new Session())->put('manual.notice',$e instanceof \PDOException?'ثبت بازگشت وجه انجام نشد.':$e->getMessage());}
        CommunicationController::afterWebhook();return $this->redirect('/admin/manual-payments/'.rawurlencode((string)$request->route('id')),303);
    }
    public function receipt(Request $request): Response
    {
        $user=(new AuthService())->user();$service=new ManualPaymentService();$row=$service->find((string)$request->route('id'));
        if(!$row || ($row['user_id']!==$user['id'] && !Authorization::can($user,'payments.manage')))return Response::html('فایل در دسترس نیست.',404);
        try{$file=$service->receipt($row);$bytes=(new SupportFileService())->bytes($file);}catch(\RuntimeException){return Response::html('فایل در دسترس نیست.',404);}
        return new Response($bytes,200,['Content-Type'=>$file['mime_type'],'Content-Disposition'=>'inline','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff','X-Robots-Tag'=>'noindex, nofollow']);
    }
}

<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Session,Authorization};
use App\Services\{AuthService,ManualPaymentService,SupportFileService};
use App\Repositories\CommunicationRepository;
final class ManualPaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $user=(new AuthService())->user();$hub=new CommunicationRepository();$payments=new ManualPaymentService($hub);
        $requests=$hub->rows("SELECT id,event_slug AS slug,status,'circle' AS type FROM event_signups WHERE user_id=:user UNION ALL SELECT id,course_slug,status,'courses' FROM course_requests WHERE user_id=:other",['user'=>$user['id'],'other'=>$user['id']]);
        $contexts=[];foreach($requests as $row){try{$contexts[]=$payments->context($row['type'],$row['id'],$user);}catch(\RuntimeException){}}
        return $this->view('payment.manual',['title'=>'پرداخت کارت‌به‌کارت | منتوریس','indexable'=>false,'user'=>$user,'contexts'=>$contexts,'ready'=>$payments->available(),'profileReady'=>ManualPaymentService::profileReady($user),'notice'=>(new Session())->pull('manual.notice'),'card'=>ManualPaymentService::CARD])->withHeader('Cache-Control','private, no-store');
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
        return $this->redirect('/payments',303);
    }
    public function admin(Request $request): Response
    {
        $service=new ManualPaymentService();$status=(string)$request->query('status','pending');if(!in_array($status,['pending','approved','rejected'],true))$status='pending';
        $hub=new CommunicationRepository();$page=max(1,(int)$request->query('page',1));$offset=($page-1)*20;
        $rows=$service->available()?$hub->rows("SELECT p.*,u.name,u.phone FROM manual_payments p JOIN users u ON u.id=p.user_id WHERE p.status=:status ORDER BY p.updated_at DESC LIMIT 20 OFFSET $offset",['status'=>$status]):[];
        $discounts=new \App\Services\DiscountCodeService($hub);foreach($rows as &$row)$row['discount']=$discounts->redemption($row['request_type'],$row['request_id']);unset($row);
        $counts=$service->available()?$hub->rows('SELECT status,COUNT(*) AS total FROM manual_payments GROUP BY status'):[];
        return $this->view('admin.manual-payments',['title'=>'بررسی رسیدهای کارت‌به‌کارت','admin'=>(new AuthService())->user(),'items'=>$rows,'counts'=>$counts,'status'=>$status,'page'=>$page,'ready'=>$service->available(),'notice'=>(new Session())->pull('manual.notice')],'layouts.admin');
    }
    public function review(Request $request): Response
    {
        try{(new ManualPaymentService())->review((string)$request->route('id'),(int)$request->input('version'),(string)$request->input('status'),(new AuthService())->user(),is_string($request->input('note'))?$request->input('note'):'');(new Session())->put('manual.notice','نتیجه بررسی ثبت شد.');}
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
        return $this->view('admin.payment-review',['title'=>'بررسی رسید پرداخت','admin'=>(new AuthService())->user(),'payment'=>$row,'member'=>$member,'context'=>$context,'notice'=>(new Session())->pull('manual.notice')],'layouts.admin')->withHeader('Cache-Control','private, no-store');
    }
    public function receipt(Request $request): Response
    {
        $user=(new AuthService())->user();$service=new ManualPaymentService();$row=$service->find((string)$request->route('id'));
        if(!$row || ($row['user_id']!==$user['id'] && !Authorization::can($user,'payments.manage')))return Response::html('فایل در دسترس نیست.',404);
        try{$file=$service->receipt($row);$bytes=(new SupportFileService())->bytes($file);}catch(\RuntimeException){return Response::html('فایل در دسترس نیست.',404);}
        return new Response($bytes,200,['Content-Type'=>$file['mime_type'],'Content-Disposition'=>'inline','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff','X-Robots-Tag'=>'noindex, nofollow']);
    }
}

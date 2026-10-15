<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Session,Database,Authorization,Audit};
use App\Repositories\{CommunicationRepository,CircleRepository,MemberProfileRepository,AdminRepository};
use App\Services\{AuthService,PublicContentService};
use RuntimeException;
final class AdminRequestReviewController extends Controller
{
    private function find(string $type,string $id): ?array
    {
        $table=['circle'=>'event_signups','courses'=>'course_requests'][$type]??null;
        return $table?(new CommunicationRepository())->one("SELECT * FROM $table WHERE id=:id",['id'=>$id]):null;
    }
    public function show(Request $request): Response
    {
        $admin=(new AuthService())->user();if(!$admin || !Authorization::can($admin,'engagements.view'))return Response::html('دسترسی مجاز نیست.',403);
        $type=(string)$request->route('type');$row=$this->find($type,(string)$request->route('id'));if(!$row)return Response::html('درخواست پیدا نشد.',404);
        $hub=new CommunicationRepository();$member=!empty($row['user_id'])?$hub->one('SELECT id,name,email,phone FROM users WHERE id=:id',['id'=>$row['user_id']]):null;
        $item=$type==='circle'?PublicContentService::event($row['event_slug']):PublicContentService::course($row['course_slug']);
        $application=$type==='circle'?(new CircleRepository())->application($row['id']):null;
        $reviewReady=(new \App\Services\PaymentLifecycleService($hub))->reviewReady($type,$row['id']);
        $payments=[];try{$payments=$hub->rows('SELECT id,part,amount,status FROM manual_payments WHERE request_type=:type AND request_id=:id ORDER BY part',['type'=>$type,'id'=>$row['id']]);}catch(\PDOException $e){if(!MemberProfileRepository::missingTable($e))throw $e;}
        return $this->view('admin.request-review',['title'=>'بررسی درخواست ثبت‌نام','admin'=>$admin,'requestRow'=>$row,'type'=>$type,'member'=>$member,'item'=>$item,'application'=>$application,'payments'=>$payments,'reviewReady'=>$reviewReady,'notice'=>(new Session())->pull('review.notice')],'layouts.admin')->withHeader('Cache-Control','private, no-store');
    }
    public function review(Request $request): Response
    {
        $admin=(new AuthService())->user();if(!$admin || !Authorization::can($admin,'engagements.manage'))return Response::html('دسترسی مجاز نیست.',403);
        $type=(string)$request->route('type');$id=(string)$request->route('id');if(!in_array($type,['circle','courses'],true))return Response::html('درخواست پیدا نشد.',404);
        $status=$request->input('status');if(!in_array($status,['approved','rejected'],true))return Response::html('نتیجه معتبر نیست.',422);
        $hub=new CommunicationRepository();
        try {
            Database::transaction($hub->pdo(),function()use($hub,$type,$id,$status,$admin,$request):void{
                $table=$type==='circle'?'event_signups':'course_requests';$lock=Database::driver($hub->pdo())==='mysql'?' FOR UPDATE':'';
                $row=$hub->one("SELECT * FROM $table WHERE id=:id".$lock,['id'=>$id]);
                if(!$row || $row['status']!=='requested' || $row['updated_at']!==$request->input('expected_at'))throw new RuntimeException('این درخواست قبلاً بررسی شده یا تغییر کرده است؛ وضعیت تازه را ببینید.');
                if(!(new AdminRepository($hub->pdo()))->updateEngagementStatus($type,$id,$status))throw new RuntimeException('ثبت نتیجه انجام نشد.');
                Audit::record('engagement.status.updated',$type,$id,$admin['id'],['status'=>'requested'],['status'=>$status],$request->ip());
            });
            (new Session())->put('review.notice',$status==='approved'?'پذیرش تأیید شد و ثبت‌نام نهایی است.':'درخواست رد شد؛ وجه تأییدشده برای بازگشت پیگیری می‌شود.');
        }catch(RuntimeException $e){(new Session())->put('review.notice',$e instanceof \PDOException?'ثبت نتیجه انجام نشد؛ دوباره تلاش کنید.':$e->getMessage());}
        CommunicationController::afterWebhook();
        return $this->redirect('/admin/requests/'.$type.'/'.$id,303);
    }
}

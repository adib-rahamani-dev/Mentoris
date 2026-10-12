<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Authorization,Session,Audit};
use App\Services\{AuthService,SupportFileService};
use App\Repositories\SupportRepository;
final class SupportController extends Controller
{
    private function user(): ?array {return (new AuthService())->user();}
    private function scalar(Request $request,string $key): string {return is_string($request->input($key))?trim($request->input($key)):'';}
    public function index(Request $request): Response {return $this->listing($request,false);}
    public function adminIndex(Request $request): Response {return $this->listing($request,true);}
    private function listing(Request $request,bool $admin): Response
    {
        $user=$this->user();if(!$user)return $this->redirect('/login');if($admin && !Authorization::can($user,'engagements.view'))return Response::html('دسترسی مجاز نیست.',403);
        $repo=new SupportRepository();$ready=$repo->hub->available();$filters=['status'=>$this->scalar($request,'status'),'channel'=>$this->scalar($request,'channel')];
        return $this->view('pages.support',['title'=>$admin?'صندوق پشتیبانی':'پشتیبانی منتوریس','indexable'=>false,'user'=>$user,'admin'=>$user,'adminMode'=>$admin,'ready'=>$ready,'tickets'=>$ready?$repo->list($admin?null:$user['id'],$filters):[],'categories'=>$repo->categories(),'filters'=>$filters,'notice'=>(new Session())->pull('support.notice')],$admin?'layouts.admin':'layouts.main')->withHeader('Cache-Control','private, no-store');
    }
    public function create(Request $request): Response
    {
        $user=$this->user();if(!$user)return $this->redirect('/login');$repo=new SupportRepository();$stored=null;
        try {
            if(!$repo->hub->available())throw new \RuntimeException('پشتیبانی پس از نصب به‌روزرسانی دیتابیس فعال می‌شود.');
            $key=$this->scalar($request,'request_key');$source=preg_match('/^[a-f0-9]{32}$/',$key)?'web-create:'.$user['id'].':'.$key:null;
            if($source && ($existing=$repo->hub->one('SELECT ticket_id FROM support_messages WHERE source_key=:key',['key'=>$source]))) {
                $url='/support/'.$existing['ticket_id'];return $request->expectsJson()?$this->json(['message'=>'گفتگو قبلاً ثبت شده است.','url'=>$url]):$this->redirect($url,303);
            }
            $stored=(new SupportFileService())->upload($request->file('attachment'));
            $ticket=$repo->create($user['id'],'site',null,$this->scalar($request,'subject'),$this->scalar($request,'category'),$this->scalar($request,'message'),$stored,$source);
            if($request->expectsJson())return $this->json(['message'=>'گفتگو ثبت شد.','url'=>'/support/'.$ticket['id']]);
            return $this->redirect('/support/'.$ticket['id'],303);
        }catch(\RuntimeException $e){$this->cleanup($stored);$message=$e instanceof \PDOException?'ثبت گفتگو فعلاً ممکن نیست؛ دوباره تلاش کنید.':$e->getMessage();if($request->expectsJson())return $this->json(['message'=>$message],422);(new Session())->put('support.notice',$message);return $this->redirect('/support',303);}
    }
    public function show(Request $request): Response {return $this->thread($request,false);}
    public function adminShow(Request $request): Response {return $this->thread($request,true);}
    private function thread(Request $request,bool $admin): Response
    {
        $user=$this->user();if(!$user)return $this->redirect('/login');$repo=new SupportRepository();if(!$repo->hub->available())return Response::html('پشتیبانی آماده نیست.',503);
        $ticket=$repo->find((string)$request->route('id'));if(!$ticket || !$repo->readable($ticket,$user,$admin))return Response::html('گفتگو پیدا نشد.',404);
        $messages=$repo->messages($ticket['id']);
        if($request->expectsJson())return Response::json(['ticket'=>['id'=>$ticket['id'],'status'=>$ticket['status']],'messages'=>array_map(static fn($m)=>array_intersect_key($m,array_flip(['id','sender_role','body','created_at','attachments'])),$messages)],200,['Cache-Control'=>'private, no-store']);
        return $this->view('pages.support-thread',['title'=>'گفتگو با پشتیبانی | منتوریس','indexable'=>false,'user'=>$user,'admin'=>$user,'adminMode'=>$admin,'ticket'=>$ticket,'messages'=>$messages,'notice'=>(new Session())->pull('support.notice'),'canReply'=>$admin?Authorization::can($user,'engagements.manage'):$ticket['status']!=='closed'],$admin?'layouts.admin':'layouts.main')->withHeader('Cache-Control','private, no-store');
    }
    public function reply(Request $request): Response {return $this->postReply($request,false);}
    public function adminReply(Request $request): Response {return $this->postReply($request,true);}
    private function postReply(Request $request,bool $admin): Response
    {
        $user=$this->user();if(!$user)return $this->redirect('/login');$repo=new SupportRepository();if(!$repo->hub->available())return $this->json(['message'=>'پشتیبانی آماده نیست.'],503);
        $ticket=$repo->find((string)$request->route('id'));if(!$ticket || !$repo->readable($ticket,$user,$admin) || ($admin && !Authorization::can($user,'engagements.manage')))return $this->json(['message'=>'گفتگو پیدا نشد.'],404);
        $stored=null;$url=($admin?'/admin/support/':'/support/').$ticket['id'];
        try {
            $key=$this->scalar($request,'request_key');$source=preg_match('/^[a-f0-9]{32}$/',$key)?'web-reply:'.$ticket['id'].':'.$user['id'].':'.$key:null;
            if($source && $repo->hub->one('SELECT id FROM support_messages WHERE source_key=:key',['key'=>$source]))return $request->expectsJson()?$this->json(['message'=>'پیام قبلاً ارسال شده است.','url'=>$url]):$this->redirect($url,303);
            $stored=(new SupportFileService())->upload($request->file('attachment'));
            $repo->append($ticket,$user['id'],$admin?'staff':'member',$this->scalar($request,'message'),$stored,$source);
            if($admin)Audit::record('support.reply','support_ticket',$ticket['id'],$user['id'],[],['status'=>'answered'],$request->ip());
            if($request->expectsJson())return $this->json(['message'=>'پیام ارسال شد.','url'=>$url]);return $this->redirect($url,303);
        }catch(\RuntimeException $e){$this->cleanup($stored);$message=$e instanceof \PDOException?'ارسال فعلاً ممکن نیست؛ پیام را نگه دارید و دوباره تلاش کنید.':$e->getMessage();if($request->expectsJson())return $this->json(['message'=>$message],422);(new Session())->put('support.notice',$message);return $this->redirect($url,303);}
    }
    public function close(Request $request): Response
    {
        $user=$this->user();if(!$user || !Authorization::can($user,'engagements.manage'))return Response::html('دسترسی مجاز نیست.',403);$repo=new SupportRepository();
        if($repo->hub->available())$repo->hub->execute("UPDATE support_tickets SET status='closed',updated_at=:now WHERE id=:id",['now'=>\App\Core\Database::now(),'id'=>$request->route('id')]);
        Audit::record('support.closed','support_ticket',(string)$request->route('id'),$user['id'],[],['status'=>'closed'],$request->ip());return $this->redirect('/admin/support/'.$request->route('id'),303);
    }
    public function attachment(Request $request): Response
    {
        $user=$this->user();if(!$user)return $this->redirect('/login');$repo=new SupportRepository();if(!$repo->hub->available())return Response::html('فایل در دسترس نیست.',503);
        $file=$repo->attachment((string)$request->route('id'));$ticket=$file?$repo->find($file['ticket_id']):null;
        if(!$file || !$ticket || (!$repo->readable($ticket,$user,false) && !Authorization::can($user,'engagements.view')))return Response::html('فایل پیدا نشد.',404);
        try {$service=new SupportFileService();$bytes=$service->bytes($file);$mime=$service->validate($bytes,$file['filename']);}
        catch(\RuntimeException){return Response::html('دریافت فایل فعلاً ممکن نیست؛ دوباره تلاش کنید.',503);}
        $audio=str_starts_with($mime,'audio/') || in_array($mime,['application/ogg','video/webm'],true);
        return new Response($bytes,200,['Content-Type'=>$mime,'Content-Length'=>(string)strlen($bytes),'Content-Disposition'=>($audio?'inline':'attachment').'; filename="attachment"; filename*=UTF-8\'\''.rawurlencode($file['filename']),'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff','X-Robots-Tag'=>'noindex, nofollow','Content-Security-Policy'=>"default-src 'none'; sandbox"]);
    }
    private function cleanup(?array $stored): void {if($stored && !empty($stored['local_name']) && preg_match('/^[a-f0-9]{32}\.bin$/',$stored['local_name'])) { $path=BASE_PATH.'/storage/uploads/support/'.$stored['local_name'];if(is_file($path))unlink($path); }}
}

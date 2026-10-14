<?php
declare(strict_types=1);
ob_start(); require __DIR__.'/profile-continuation.php';
use App\Repositories\CircleRepository;
use App\Controllers\EventsController;
use App\Core\Request;
$reuseChecks=0;$assertReuse=static function(bool $condition,string $message)use(&$reuseChecks):void{if(!$condition)throw new RuntimeException($message);$reuseChecks++;};
$pdo->exec('CREATE TABLE circle_applications (signup_id TEXT PRIMARY KEY REFERENCES event_signups(id),details TEXT NOT NULL,resume TEXT NOT NULL,created_at TEXT NOT NULL)');
(new \App\Services\ContentReleaseService())->sync($pdo);
$user=(new \App\Services\AuthService())->user();$repo=new CircleRepository();$events=new EventsController();
$input=['name'=>'نام جعل‌شده','phone'=>'09120000000','city'=>'تبریز','education'=>'کارشناسی ارشد','university'=>'دانشگاه تبریز','specialization'=>'بالینی','experience'=>'۳ سال','approach'=>'ACT','practice_areas'=>'بزرگسال','professional_number'=>'','motivation'=>'برای رشد حرفه‌ای'];
$response=$events->register(new Request(body:$input),'therapists-circle-third');
$signup=$repo->findSignup('therapists-circle-third',$user['phone']);
$assertReuse($response->status()===303 && $signup!==null,'An authenticated complete member can submit without a resume.');
$application=$repo->application($signup['id']);
$assertReuse($application!==null && $application['resume']===[],'Optional resume retains an actual application with empty resume metadata.');
$assertReuse($pdo->query("SELECT resume FROM circle_applications WHERE signup_id='".$signup['id']."'")->fetchColumn()==='null','JSON null is stored in the existing NOT NULL JSON column without a schema change.');
$assertReuse($signup['name']===$user['name'] && $signup['phone']===$user['phone'],'Application identity is bound to the signed-in account.');
$saved=$repo->savedApplication($user['id']);
$assertReuse($saved['details']['approach']==='ACT' && $saved['details']['city']==='تبریز','Professional information and city persist as reusable database data.');
$assertReuse($repo->savedApplication('another-user')===null,'Another account cannot reuse or see this private saved information.');
(new \App\Core\Session())->forget('event.receipt.therapists-circle-third');
$html=$events->show(new Request(),'future-meeting')->content();
$assertReuse(str_contains($html,'value="ACT"') && str_contains($html,'data-circle-application'),'The next form is prefilled and exposes the review step.');
$assertReuse(!preg_match('/name="resume"[^>]*required/',$html),'Resume upload is optional in the real rendered form.');
$assertReuse($repo->application($signup['id'])['details']['motivation']===$input['motivation'],'Optional explanatory text persists with the request.');
$file=(new \App\Services\SupportFileService())->store("%PDF-1.4\n% Resume reuse test\n",'saved-resume.pdf');
try {
 $pdo->prepare('UPDATE circle_applications SET resume=:resume WHERE signup_id=:id')->execute(['resume'=>json_encode($file),'id'=>$signup['id']]);
 $assertReuse($repo->savedApplication($user['id'])['resume']['local_name']===$file['local_name'],'Saved private resume metadata belongs to the same account.');
 $html=$events->show(new Request(),'future-meeting')->content();
 $assertReuse(str_contains($html,'name="reuse_resume"') && str_contains($html,'saved-resume.pdf'),'Reuse of a saved resume is a visible user choice.');
 $input['reuse_resume']='1';$response=$events->register(new Request(body:$input),'future-meeting');
 $next=$repo->findSignup('future-meeting',$user['phone']);
 $assertReuse($response->status()===303 && $next && $repo->application($next['id'])['resume']['local_name']===$file['local_name'],'A subsequent event reuses the private resume without requiring another upload.');
 $events->register(new Request(body:$input),'future-meeting');
 $assertReuse(is_file(BASE_PATH.'/storage/uploads/support/'.$file['local_name']),'A duplicate or failed request never deletes the reused original file.');
} finally {if(is_file(BASE_PATH.'/storage/uploads/support/'.$file['local_name']))unlink(BASE_PATH.'/storage/uploads/support/'.$file['local_name']);}
echo "PASS: {$reuseChecks} optional resume, saved information and private reuse checks.\n";ob_end_flush();

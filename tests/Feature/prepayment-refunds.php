<?php
declare(strict_types=1);
ob_start();require __DIR__.'/admin-review-flow.php';
use App\Core\{Database,Request};
use App\Services\{ManualPaymentService,PaymentLifecycleService,CommunicationDetailService,AuthService,CommunicationBotService};
use App\Repositories\{AdminRepository,CourseRequestRepository};
$flowChecks=0;$verify=static function(bool $value,string $message)use(&$flowChecks):void{if(!$value)throw new RuntimeException($message);$flowChecks++;};
$denyFlow=static function(callable $fn,string $message)use($verify):void{try{$fn();throw new LogicException($message);}catch(RuntimeException){$verify(true,$message);}};
$sql=file_get_contents(BASE_PATH.'/database/migrations/014_payment_refunds.mysql.sql');preg_match('/CREATE TABLE IF NOT EXISTS (\w+) \((.*?)\) ENGINE=/s',$sql,$block);$body=preg_replace('/ CHARACTER SET \w+ COLLATE \w+/','',$block[2]);$body=preg_replace('/,\s*KEY \w+\s*\([^)]*\)/','',$body);$pdo->exec('CREATE TABLE '.$block[1].' ('.$body.')');
$sql=file_get_contents(BASE_PATH.'/database/migrations/013_discount_codes.mysql.sql');preg_match_all('/CREATE TABLE IF NOT EXISTS (\w+) \((.*?)\) ENGINE=/s',$sql,$couponBlocks,PREG_SET_ORDER);foreach($couponBlocks as $block){$body=preg_replace('/ CHARACTER SET \w+ COLLATE \w+/','',$block[2]);$body=preg_replace('/,\s*KEY \w+\s*\([^)]*\)/','',$body);$body=preg_replace('/UNIQUE KEY \w+\s*\(([^)]+)\)/','UNIQUE($1)',$body);$pdo->exec('CREATE TABLE IF NOT EXISTS '.$block[1].' ('.$body.')');}
$lifecycle=new PaymentLifecycleService($hub);$service=new ManualPaymentService($hub);$repo=new AdminRepository($pdo);$slug='psychotherapy-mastery-deliberate-practice';
$create=static function($digit,$phone,$plan='installments')use($makeUser,$hub,$slug):array{$user=$makeUser('student',$digit,$phone);(new CourseRequestRepository())->request($slug,['name'=>$user['name'],'phone'=>$phone,'payment_plan'=>$plan],$user['id']);return [$user,$hub->one('SELECT * FROM course_requests WHERE user_id=:user AND course_slug=:slug',['user'=>$user['id'],'slug'=>$slug])];};
$file=(new \App\Services\SupportFileService())->store(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA1sAAAAASUVORK5CYII='),'prepayment.png');
try{
 [$first,$firstRequest]=$create('9','09129990001');$context=$service->context('courses',$firstRequest['id'],$first);
 $verify($context['payable'] && $context['refundReady'],'A pending admission becomes payable only with refund tracking installed.');
 $receipt=$service->submit($context,$first,1,$file);$verify((int)$receipt['amount']===7500000,'The first installment can actually be submitted before admission.');
 $service->review($receipt['id'],1,'approved',$manager);
 $verify(!$hub->one('SELECT id FROM enrollments WHERE user_id=:user AND course_slug=:slug',['user'=>$first['id'],'slug'=>$slug]),'Verified funds do not grant course access while admission is pending.');
 $denyFlow(fn()=>$service->submit($service->context('courses',$firstRequest['id'],$first),$first,2,$file),'A second installment cannot be paid before admission.');
 $repo->updateEngagementStatus('courses',$firstRequest['id'],'approved');$lifecycle->sync('courses',$firstRequest['id']);
 $verify((int)$hub->one('SELECT COUNT(*) n FROM enrollments WHERE user_id=:user AND course_slug=:slug',['user'=>$first['id'],'slug'=>$slug])['n']===1,'Admission after verified prepayment activates access exactly once.');
 $repo->updateEngagementStatus('courses',$firstRequest['id'],'rejected');$lifecycle->sync('courses',$firstRequest['id']);$refunds=$lifecycle->refunds('courses',$firstRequest['id']);
 $verify(count($refunds)===1 && (int)$refunds[0]['amount']===7500000 && $refunds[0]['status']==='pending','Rejected admission creates exactly one refund for the money actually verified.');
 $verify($hub->one('SELECT status FROM enrollments WHERE user_id=:user AND course_slug=:slug',['user'=>$first['id'],'slug'=>$slug])['status']==='canceled','Rejected admission revokes existing access without deleting its history.');
 $denyFlow(fn()=>$lifecycle->complete($receipt['id'],$first,'BANK123','',true),'A member cannot mark a refund completed.');
 $denyFlow(fn()=>$lifecycle->complete($receipt['id'],$manager,'BANK123','',false),'A refund cannot be marked complete without explicit confirmation of an actual transfer.');
 $lifecycle->complete($receipt['id'],$manager,'BANK12345','واریز برگشتی انجام شد',true);$refunded=$lifecycle->refunds('courses',$firstRequest['id'])[0];
 $verify($refunded['status']==='refunded' && $refunded['transfer_reference']==='BANK12345' && $refunded['completed_by']===$manager['id'],'A manual transfer records its tracking reference, actor and completed status.');
 $denyFlow(fn()=>$lifecycle->complete($receipt['id'],$manager,'BANK12345','',true),'Repeating a completion cannot execute or announce a second refund.');
 $denyFlow(fn()=>$repo->updateEngagementStatus('courses',$firstRequest['id'],'approved'),'A refunded request cannot be silently reopened.');
 $verify($hub->one('SELECT status FROM course_requests WHERE id=:id',['id'=>$firstRequest['id']])['status']==='rejected','A rejected reopening is rolled back atomically.');
 [$second,$secondRequest]=$create('6','09129990002','cash');
 $coupons=new \App\Services\DiscountCodeService($hub);$coupons->create(['code'=>'PREPAY10','discount_type'=>'fixed','value'=>'1000000','applies_to'=>'all','item_slug'=>'','max_uses'=>'','starts_at'=>'','ends_at'=>''],$manager);$discount=$coupons->apply($service->context('courses',$secondRequest['id'],$second),$second,'PREPAY10');
 $verify((int)$discount['final_amount']===14000000,'A pending admission can apply a valid coupon before any receipt.');
 $secondReceipt=$service->submit($service->context('courses',$secondRequest['id'],$second),$second,1,$file);
 $repo->updateEngagementStatus('courses',$secondRequest['id'],'rejected');$verify($lifecycle->refunds('courses',$secondRequest['id'])===[],'An unverified screenshot does not count as money received or create a financial refund.');
 $service->review($secondReceipt['id'],1,'approved',$manager);$verify(count($lifecycle->refunds('courses',$secondRequest['id']))===1 && (int)$lifecycle->refunds('courses',$secondRequest['id'])[0]['amount']===14000000,'A late verification refunds the discounted amount actually paid, rather than the catalog price.');
 $verify(!$service->context('courses',$secondRequest['id'],$second)['payable'],'A rejected request cannot accept another payment.');
 $details=(new CommunicationDetailService($hub))->receipt($secondReceipt);$verify(str_contains($details,'15,000,000') && str_contains($details,$second['phone']) && str_contains($details,'prepayment.png') && str_contains($details,'شناسه'),'Receipt notifications include amount, applicant contact, filename and identifiers.');
 (new AuthService())->refresh($second);$get=new Request();$get->setRouteParams(['type'=>'courses','id'=>$secondRequest['id']]);$response=(new \App\Controllers\ManualPaymentController())->checkout($get);
 $verify($response->status()===200 && str_contains($response->content(),'پیگیری بازگشت وجه') && !str_contains($response->content(),'data-copy-card'),'The single-order page shows refund progress instead of another transfer form.');
 (new AuthService())->refresh($outsider);$verify((new \App\Controllers\ManualPaymentController())->checkout($get)->status()===404,'A member cannot open another member’s checkout.');
 $hub->execute("UPDATE comm_outbox SET created_at='2026-01-01 00:00:00'");$bot=new CommunicationBotService($hub,'telegram');$before=(int)$pdo->query('SELECT COUNT(*) FROM comm_outbox')->fetchColumn();$bot->handle($callback(501,'admin:receipts:pending:0',1991));
 $verify((int)$pdo->query('SELECT COUNT(*) FROM comm_outbox')->fetchColumn()>$before,'The manager bot can browse payment statuses and detailed receipt entries.');
 $before=(int)$pdo->query('SELECT COUNT(*) FROM comm_outbox')->fetchColumn();$bot->handle($callback(501,'admin:refunds',1992));$verify((int)$pdo->query('SELECT COUNT(*) FROM comm_outbox')->fetchColumn()>$before,'The manager bot exposes actionable pending refunds.');
 $supportTicket=(new \App\Repositories\SupportRepository($hub))->create($first['id'],'site',null,'پرسش درباره پرداخت','registration','متن کامل درخواست پشتیبانی نمونه',$file,'notification-detail-test');$supportNotice=false;
 foreach($hub->rows("SELECT payload FROM comm_outbox WHERE chat_id='501' AND method='sendMessage'") as $job){$payload=\App\Repositories\CommunicationRepository::open($job['payload']);if(($payload['reply_markup']['inline_keyboard'][0][0]['url']??null)===rtrim((string)env('APP_URL'),'/').'/admin/support/'.$supportTicket['id'] && str_contains($payload['text'],'متن کامل درخواست پشتیبانی نمونه') && str_contains($payload['text'],'prepayment.png'))$supportNotice=true;}
 $verify($supportNotice,'Support notifications include the actual message, attachment name and exact protected conversation link.');
 $schema=file_get_contents(BASE_PATH.'/database/cpanel/latest_schema_2026_10_14.mysql.sql');$verify(substr_count($schema,'CREATE TABLE IF NOT EXISTS ')===48 && str_contains($schema,'014_payment_refunds.mysql.sql'),'The complete repeatable SQL includes the new refund table and migration marker.');
}finally{if(is_file(BASE_PATH.'/storage/uploads/support/'.$file['local_name']))unlink(BASE_PATH.'/storage/uploads/support/'.$file['local_name']);}
echo "PASS: {$flowChecks} prepayment, admission, refund, privacy and bot detail checks.\n";ob_end_flush();

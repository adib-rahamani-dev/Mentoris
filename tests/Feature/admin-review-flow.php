<?php
declare(strict_types=1);
ob_start();require __DIR__.'/manual-payments.php';
use App\Core\Request;
use App\Services\{AuthService,EnrollmentReturnService,ContentReleaseService};
use App\Controllers\{CoursesController,AdminRequestReviewController,ManualPaymentController};
$reviewChecks=0;$checkReview=static function(bool $condition,string $message)use(&$reviewChecks):void{if(!$condition)throw new RuntimeException($message);$reviewChecks++;};
(new ContentReleaseService())->sync($pdo);
$slug='psychotherapy-mastery-deliberate-practice';$target='/courses/'.$slug.'#request';
(new AuthService())->refresh($outsider);
$checkReview(!\App\Services\ManualPaymentService::profileReady($outsider) && \App\Services\ManualPaymentService::enrollmentReady($outsider,'courses') && !\App\Services\ManualPaymentService::enrollmentReady($outsider,'circle'),'Courses need an account; circles still require a complete profile.');
EnrollmentReturnService::remember($target);$checkReview(EnrollmentReturnService::afterLogin($outsider)===$target,'Login returns an incomplete course member directly to the course.');
$courseController=new CoursesController();$html=$courseController->show(new Request(),$slug)->content();
$checkReview(str_contains($html,'course-account-summary') && !str_contains($html,'name="name"') && !str_contains($html,'name="phone"'),'The course form does not ask for identity or profile information again.');
$response=$courseController->requestPlace(new Request(body:['payment_plan'=>'installments']),$slug);
$requestRow=$hub->one('SELECT * FROM course_requests WHERE user_id=:user AND course_slug=:slug',['user'=>$outsider['id'],'slug'=>$slug]);
$checkReview($response->status()===303 && $requestRow && $requestRow['status']==='requested' && $requestRow['name']===$outsider['name'],'An incomplete profile can actually submit a course request using its account identity.');
$expectedUrl=rtrim((string)env('APP_URL'),'/').'/admin/requests/courses/'.$requestRow['id'];$matchingNotice=false;
foreach($hub->rows("SELECT payload FROM comm_outbox WHERE chat_id='501' AND method='sendMessage'") as $queued){$payload=\App\Repositories\CommunicationRepository::open($queued['payload']);if(($payload['reply_markup']['inline_keyboard'][0][0]['url']??null)===$expectedUrl)$matchingNotice=true;}
$checkReview($matchingNotice,'The queued Telegram notification opens the exact request review page.');
$request=new Request();$request->setRouteParams(['type'=>'courses','id'=>$requestRow['id']]);$controller=new AdminRequestReviewController();
$checkReview($controller->show($request)->status()===403,'A normal member cannot view the admin review page.');
(new AuthService())->refresh($manager);$detail=$controller->show($request);
$checkReview($detail->status()===200 && str_contains($detail->content(),'تأیید پذیرش') && str_contains($detail->content(),$outsider['phone']) && $detail->headers()['Cache-Control']==='private, no-store','Authorized managers see private details and approve/reject controls.');
$approve=new Request(body:['status'=>'approved','expected_at'=>$requestRow['updated_at']]);$approve->setRouteParams(['type'=>'courses','id'=>$requestRow['id']]);
$checkReview($controller->review($approve)->status()===303 && $hub->one('SELECT status FROM course_requests WHERE id=:id',['id'=>$requestRow['id']])['status']==='approved','The detail page approves the real persisted request.');
$stale=new Request(body:['status'=>'rejected','expected_at'=>$requestRow['updated_at']]);$stale->setRouteParams(['type'=>'courses','id'=>$requestRow['id']]);$controller->review($stale);
$checkReview($hub->one('SELECT status FROM course_requests WHERE id=:id',['id'=>$requestRow['id']])['status']==='approved','A stale or double review cannot overwrite an existing decision.');
$context=$payment->context('courses',$requestRow['id'],$outsider);
$newImage=(new \App\Services\SupportFileService())->store(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA1sAAAAASUVORK5CYII='),'review.png');
try {
 $newReceipt=$payment->submit($context,$outsider,1,$newImage);
 $checkReview($newReceipt['status']==='pending' && (int)$newReceipt['amount']===7500000,'Approved course members can upload a receipt without completing their profile.');
 $noticeRow=$hub->one('SELECT payload FROM comm_outbox WHERE dedupe_key=:key',['key'=>'manual:'.$newReceipt['id'].':1:notice']);$payload=\App\Repositories\CommunicationRepository::open($noticeRow['payload']);
 $checkReview($payload['reply_markup']['inline_keyboard'][0][0]['url']===rtrim((string)env('APP_URL'),'/').'/admin/manual-payments/'.$newReceipt['id'],'Receipt notification links to the exact receipt instead of the general inbox.');
 $receiptRequest=new Request();$receiptRequest->setRouteParams(['id'=>$newReceipt['id']]);$paymentsController=new ManualPaymentController();$detail=$paymentsController->detail($receiptRequest);
 $checkReview($detail->status()===200 && str_contains($detail->content(),'name="version"') && str_contains($detail->content(),'name="detail"'),'The dedicated receipt page renders the version-bound approval form.');
 (new AuthService())->refresh($outsider);$checkReview($paymentsController->detail($receiptRequest)->status()===403,'Members cannot open receipt administration pages.');
 (new AuthService())->refresh($manager);$reject=new Request(body:['status'=>'rejected','version'=>'1','detail'=>'1','note'=>'تصویر واضح نیست']);$reject->setRouteParams(['id'=>$newReceipt['id']]);$response=$paymentsController->review($reject);
 $checkReview($response->headers()['Location']==='/admin/manual-payments/'.$newReceipt['id'] && $payment->find($newReceipt['id'])['status']==='rejected','Receipt review records the result and returns to its own detail page.');
}finally{if(is_file(BASE_PATH.'/storage/uploads/support/'.$newImage['local_name']))unlink(BASE_PATH.'/storage/uploads/support/'.$newImage['local_name']);}
echo "PASS: {$reviewChecks} admin review, Telegram links and simplified course flow checks.\n";ob_end_flush();

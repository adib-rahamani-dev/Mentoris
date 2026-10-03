<?php
declare(strict_types=1);
// Run: php tests/Feature/member-flow.php [--preview]. Uses only an isolated SQLite database.
require dirname(__DIR__,2).'/bootstrap/constants.php';
require BASE_PATH.'/bootstrap/autoload.php';
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Translator;
use App\Core\Authorization;
use App\Repositories\UserRepository;
use App\Repositories\MemberProfileRepository;
use App\Repositories\CircleRepository;
use App\Repositories\FeedbackReportRepository;
use App\Services\MemberProfileService;
use App\Services\AuthService;
use App\Services\NotificationService;
use App\Services\PublicContentService;
use App\Controllers\AuthController;
use App\Controllers\MemberProfileController;
use App\Controllers\FeedbackController;

if (($argv[1] ?? '') === '--signup-worker') {
 $_ENV['SMS_REGISTRATION_REQUIRED']='false';
 $db=Database::connect(['driver'=>'sqlite','database'=>$argv[2]]);
 try {
  (new UserRepository($db))->create(['name'=>'عضو هم‌زمان','email'=>'worker'.$argv[3].'@example.test','phone'=>$argv[3]%2 ? '+98 (912) 555-4444' : '۰۹۱۲۵۵۵۴۴۴۴','password'=>'Concurrent123']);
  echo 'created';
 } catch (RuntimeException $e) { if ($e->getCode()!==409) throw $e; echo 'duplicate'; }
 exit;
}

$_ENV['APP_ENV']='local'; $_ENV['MAIL_MAILER']='log'; $_ENV['SESSION_DRIVER']='files'; $_ENV['APP_URL']='http://127.0.0.1:8098'; $_ENV['SMS_REGISTRATION_REQUIRED']='false';
$_ENV['APP_KEY']=\App\Core\Crypto::generateKey();
$initialMailFiles=\App\Core\PrivateRecords::files('mail-outbox');
register_shutdown_function(static function() use ($initialMailFiles): void { foreach(array_diff(\App\Core\PrivateRecords::files('mail-outbox'),$initialMailFiles) as $path) if(is_file($path)) unlink($path); });
Translator::boot([],[],[]);
set_error_handler(static function(int $severity,string $message,string $file,int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message,0,$severity,$file,$line);
});
$pdo=Database::connect(['driver'=>'sqlite','database'=>':memory:']); Database::use($pdo);
$schemas=[
 'rate_limits'=>'key_hash TEXT PRIMARY KEY,hits INTEGER,reset_at TEXT,updated_at TEXT',
 'content_entities'=>'id TEXT PRIMARY KEY,entity_type TEXT,slug TEXT,status TEXT,sort_order INTEGER,author_id TEXT,published_at TEXT,created_at TEXT,updated_at TEXT',
 'content_translations'=>'id TEXT PRIMARY KEY,entity_id TEXT,locale TEXT,title TEXT,subtitle TEXT,excerpt TEXT,body TEXT,metadata TEXT,created_at TEXT,updated_at TEXT,UNIQUE(entity_id,locale)',
 'feedback_windows'=>'event_slug TEXT PRIMARY KEY,starts_at TEXT,ends_at TEXT,enabled INTEGER,updated_by TEXT,updated_at TEXT',
 'audit_logs'=>'id TEXT PRIMARY KEY,actor_id TEXT,action TEXT,subject_type TEXT,subject_id TEXT,old_values TEXT,new_values TEXT,ip_hash TEXT,created_at TEXT',
 'users'=>"id TEXT PRIMARY KEY,name TEXT,email TEXT UNIQUE,password_hash TEXT,phone TEXT,professional_role TEXT,bio TEXT,account_role TEXT,status TEXT,auth_version INTEGER,email_verified_at TEXT,last_login_at TEXT,password_changed_at TEXT,created_at TEXT,updated_at TEXT",
 'enrollments'=>"id TEXT,user_id TEXT,course_slug TEXT,status TEXT,enrolled_at TEXT",
 'event_registrations'=>"user_id TEXT,event_slug TEXT,status TEXT,created_at TEXT",
 'certificates'=>"id TEXT,user_id TEXT,course_slug TEXT,certificate_number TEXT,issued_at TEXT,revoked_at TEXT",
 'notifications'=>"id TEXT PRIMARY KEY,user_id TEXT,title TEXT,message TEXT,created_at TEXT,read_at TEXT",
 'password_reset_tokens'=>"id TEXT PRIMARY KEY,user_id TEXT,token_hash TEXT,expires_at TEXT,used_at TEXT,created_at TEXT",
 'member_profiles'=>"user_id TEXT PRIMARY KEY REFERENCES users(id),member_type TEXT,education_status TEXT,field_of_study TEXT,degree TEXT,university TEXT,city TEXT,practice_status TEXT,specialty_fields TEXT,details TEXT,training_courses TEXT,marketing_consent INTEGER,terms_accepted_at TEXT,completed_at TEXT,updated_at TEXT",
 'event_signups'=>"id TEXT PRIMARY KEY,user_id TEXT,event_slug TEXT,name TEXT,phone TEXT,city TEXT,status TEXT,attended_at TEXT,created_at TEXT,updated_at TEXT,UNIQUE(event_slug,phone)",
 'community_memberships'=>"id TEXT PRIMARY KEY,user_id TEXT,email TEXT UNIQUE,name TEXT,professional_role TEXT,interests TEXT,description TEXT,status TEXT,created_at TEXT,updated_at TEXT",
 'event_feedback'=>"id TEXT PRIMARY KEY,signup_id TEXT UNIQUE REFERENCES event_signups(id),content_rating INTEGER,hosting_rating INTEGER,challenge TEXT,comment TEXT,created_at TEXT",
];
foreach($schemas as $table=>$columns) $pdo->exec('CREATE TABLE '.$table.' ('.$columns.')');
$checks=0;
$check=function(bool $condition,string $message) use (&$checks): void { if(!$condition) throw new RuntimeException($message); $checks++; };
$_ENV['SUPER_ADMIN_EMAILS']='member@example.test';
$users=new UserRepository(); $profiles=new MemberProfileRepository();
$register=['name'=>'عضو آزمایشی','phone'=>'۰۹۱۲۳۴۵۶۷۸۹','email'=>'member@example.test','password'=>'StrongPassword123','password_confirmation'=>'StrongPassword123','member_type'=>'therapist','accept'=>'1','marketing_consent'=>'1'];
$response=(new AuthController())->register(new Request(body:$register));
$check($response->headers()['Location']==='/profile?welcome=1','Registration must land on profile without verification.');
$user=(new AuthService())->user();
$check($user['phone']==='09123456789','Persian phone normalized and saved.');
$check($user['account_role']==='student' && $profiles->find($user['id'])['member_type']==='therapist','Member type must not grant admin access.');
$check(!empty($profiles->find($user['id'])['terms_accepted_at']),'Terms acceptance timestamp saved.');
$draft=['member_type'=>'therapist','education_status'=>'student','practice_status'=>'active','city'=>'تبریز','client_count'=>'۱۲','client_period'=>'week','marketing_consent'=>'1'];
[$valid,$errors]=MemberProfileService::validate($draft,false); $check(!$errors,'Draft can be incomplete.');
$profiles->save($user['id'],$valid);
$check(empty($profiles->find($user['id'])['completed_at']),'Draft must remain incomplete.');
[$valid,$errors]=MemberProfileService::validate($draft,true); $check(isset($errors['field_of_study'],$errors['university']),'Completion requires field and university.');
$full=$draft+['field_of_study'=>'روان‌شناسی','degree'=>'master','university'=>'دانشگاه تبریز','admission_year'=>'۱۴۰۰','training_courses'=>[['name'=>'ACT','organizer'=>'مرکز آموزش','instructor'=>'مدرس','date'=>'۱۴۰۴','duration'=>'۲۰ ساعت','type'=>'online','certificate'=>'yes']],'specialty_fields'=>'تروما، بزرگسال','professional_url'=>'https://example.test','bio'=>'<script>alert(1)</script>'];
$response=(new MemberProfileController())->save(new Request(body:$full+['mode'=>'complete']));
$check($response->headers()['Location']==='/profile?saved=1','Supplementary profile saves and redirects.');
$stored=$profiles->find($user['id']); $check($stored['training_courses'][0]['name']==='ACT' && $stored['client_count']==='12' && $stored['completed_at']!==null,'Course and professional data persisted.');
[$student,$errors]=MemberProfileService::validate(['member_type'=>'student','education_status'=>'student','practice_status'=>'active','client_count'=>'invalid','graduation_year'=>'invalid']);
$check(!$errors && $student['client_count']==='' && $student['practice_status']==='' && $student['graduation_year']==='','Hidden conditional fields cannot leak into student profile.');
[$invalid,$errors]=MemberProfileService::validate(['member_type'=>'therapist','professional_url'=>'javascript:alert(1)','training_courses'=>[['organizer'=>'Missing course name']]]);
$check(isset($errors['professional_url'],$errors['training_courses']),'Invalid URLs and missing course names rejected.');
$result=$profiles->search(['member_type'=>'therapist','city'=>'تبریز','university'=>'تبریز','specialty_fields'=>'تروما']);
$check($result['total']===1 && $result['stats']['completed']===1,'Admin combined filters and summary work.');
$check($profiles->search(['q'=>"' OR 1=1 --"])['total']===0,'Search treats SQL payload as text.');
$second=$users->create(['name'=>'عضو دوم','phone'=>'09120000000','email'=>'second@example.test','password'=>'StrongPassword123','member_type'=>'student']);
$own=$users->findById($user['id'])['notifications'][0];
$users->markNotificationRead($second['id'],$own['id']); $check($users->findById($user['id'])['notifications'][0]['read_at']===null,'Users cannot read another user notification.');
$users->markNotificationRead($user['id'],$own['id']); $check($users->findById($user['id'])['notifications'][0]['read_at']!==null,'Single notification read persists.');
$delivery=(new NotificationService())->notify($second,'عنوان آزمایشی','پیام فقط در پنل',true); $check($delivery==='no-consent','Email respects notification consent.');
$users->markNotificationsRead($second['id']); $check(count(array_filter($users->findById($second['id'])['notifications'],fn($n)=>$n['read_at']===null))===0,'Mark all clears every unread item.');
$users->setManagedPassword($user['id'],'ChangedPassword123');
(new AuthService())->refresh(UserRepository::publicUser($users->findById($user['id'])));
$check((new AuthService())->user()!==null,'Refresh preserves increased authentication version.');
$feedback=new FeedbackController();
$windows=new \App\Services\SurveyWindowService();
$check(!$windows->status('therapists-circle-tabriz')['open'],'An unconfigured survey is closed.');
$date=new DateTimeImmutable('now',new DateTimeZone('Asia/Tehran'));
$schedule=['starts_at'=>$date->modify('-1 day')->format('Y-m-d\TH:i'),'ends_at'=>$date->modify('+1 day')->format('Y-m-d\TH:i'),'enabled'=>'1'];
$check(isset($windows->save('therapists-circle-tabriz',['starts_at'=>'2026-02-30T10:00','ends_at'=>'2026-02-30T12:00'],$user['id'])['starts_at']),'Impossible calendar dates are rejected.');
$check(isset($windows->save('therapists-circle-tabriz',['starts_at'=>'2026-10-10T12:00','ends_at'=>'2026-10-10T11:00'],$user['id'])['ends_at']),'An inverted survey window is rejected.');
foreach(\App\Services\SurveyWindowService::EVENTS as $slug) $check(!$windows->save($slug,$schedule,$user['id']),'Survey windows save per event.');
$window=$windows->status('therapists-circle-tabriz');
$check($window['starts_local']===$schedule['starts_at'] && $window['row']['starts_at']===$date->modify('-1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:00'),'Tehran input round-trips through UTC storage.');
$check($windows->status('therapists-circle-tabriz',$window['start']-1)['state']==='upcoming' && $windows->status('therapists-circle-tabriz',$window['start'])['open'],'Start is inclusive.');
$check($windows->status('therapists-circle-tabriz',$window['end'])['state']==='closed','End is exclusive.');
$windows->save('therapists-circle-tabriz',array_replace($schedule,['enabled'=>'0']),$user['id']);
$check($feedback->feedback(new Request(body:['event'=>'therapists-circle-tabriz','content_rating'=>'5','hosting_rating'=>'5'],server:['HTTP_ACCEPT'=>'application/json']))->status()===403,'Direct POST cannot bypass a disabled survey.');
$check($feedback->access(new Request(body:['event'=>'therapists-circle-tabriz','member_access'=>'1'],server:['HTTP_ACCEPT'=>'application/json']))->status()===403,'Entry into a disabled survey is rejected on the server.');
$windows->save('therapists-circle-tabriz',$schedule,$user['id']);
$windows->save('therapists-circle-second',['starts_at'=>$date->modify('-2 days')->format('Y-m-d\TH:i'),'ends_at'=>$date->modify('-1 day')->format('Y-m-d\TH:i'),'enabled'=>'1'],$user['id']);
$check($feedback->feedback(new Request(body:['event'=>'therapists-circle-second','content_rating'=>'5','hosting_rating'=>'5'],server:['HTTP_ACCEPT'=>'application/json']))->status()===403,'An expired deadline rejects POST even if a form was loaded earlier.');
$windows->save('therapists-circle-second',$schedule,$user['id']);
foreach(['therapists-circle-tabriz','therapists-circle-second'] as $slug) {
 $response=$feedback->access(new Request(body:['event'=>$slug,'member_access'=>'1','participated'=>'1']));
 $check(($response->headers()['Location'] ?? '')==='/feedback','Feedback access works for each archived event.');
 $response=$feedback->feedback(new Request(body:['content_rating'=>'5','hosting_rating'=>'4','challenge'=>'supervision','comment'=>'پیشنهاد آزمایشی']));
 $check(($response->headers()['Location'] ?? '')==='/feedback?done=1','Feedback persists for each event.');
 $signup=(new CircleRepository())->findSignup($slug,$user['phone']);
 $check($signup['status']==='requested' && $signup['attended_at']===null,'Self-report must not certify attendance.');
 $feedback->feedback(new Request(body:['content_rating'=>'5','hosting_rating'=>'4','challenge'=>'supervision','comment'=>'Duplicate']));
 $report=(new FeedbackReportRepository())->report(['event'=>$slug]);
 $check((int)$report['stats']['responses']===1 && (float)$report['stats']['content']===5.0,'Duplicate feedback prevented and report aggregates accurate.');
 $event=PublicContentService::event($slug); $check($event['status']==='completed' && !$event['can_register'] && $event['external_registration_url']==='','Both events closed but visible.');
}
$report=(new FeedbackReportRepository())->report(['q'=>'پیشنهاد','rating'=>'5']); $check($report['total']===2,'Admin feedback filters work across both events.');
$bad=$register; $bad['accept']='0'; $bad['email']='invalid-terms@example.test';
$response=(new AuthController())->register(new Request(body:$bad)); $check($users->findByEmail($bad['email'])===null,'Unchecked terms cannot create an account.');
$savedProfiles=$pdo->query('SELECT * FROM member_profiles')->fetchAll();
$pdo->exec('DROP TABLE member_profiles');
$fallbackRegister=array_replace($register,['email'=>'fallback@example.test','phone'=>'09121111111','password'=>'Member12','password_confirmation'=>'Member12']);
$response=(new AuthController())->register(new Request(body:$fallbackRegister));
$fallbackUser=(new AuthService())->user();
$check(($response->headers()['Location'] ?? '')==='/profile?welcome=1','Eight-character registration works while optional profile migration is missing.');
$check($profiles->find($fallbackUser['id'])['member_type']==='therapist','Missing-table fallback preserves member type.');
$response=(new MemberProfileController())->save(new Request(body:['member_type'=>'student','field_of_study'=>'روان‌شناسی','university'=>'دانشگاه آزمایشی'],server:['HTTP_ACCEPT'=>'application/json']));
$check($response->status()===200 && $profiles->find($fallbackUser['id'])['field_of_study']==='روان‌شناسی','AJAX profile saves to encrypted fallback.');
$raw=file_get_contents(BASE_PATH.'/storage/data/member-profiles/'.$fallbackUser['id'].'.sealed');
$check(!str_contains($raw,'روان‌شناسی') && !str_contains($raw,'student'),'Fallback profiles are encrypted.');
$check($profiles->search(['university'=>'آزمایشی'])['total']===1,'Admin filters work before migration.');
$pdo->exec('CREATE TABLE member_profiles ('.$schemas['member_profiles'].')');
foreach($savedProfiles as $row) $pdo->prepare('INSERT INTO member_profiles ('.implode(',',array_keys($row)).') VALUES (:'.implode(',:',array_keys($row)).')')->execute($row);
$check($profiles->search(['university'=>'آزمایشی'])['total']===1 && !is_file(BASE_PATH.'/storage/data/member-profiles/'.$fallbackUser['id'].'.sealed'),'Admin search automatically imports staged profiles after migration.');
$engagement=new \App\Repositories\EngagementRepository();
$membership=$engagement->setCommunity($fallbackUser,true);
$check($membership['status']==='pending','One checkbox registers community request.');
$engagement->setCommunity($fallbackUser,true);
$check((int)$pdo->query('SELECT COUNT(*) FROM community_memberships')->fetchColumn()===1,'Repeated checkbox action cannot duplicate membership.');
$check($engagement->setCommunity($fallbackUser,false)['status']==='withdrawn','Checkbox can withdraw request.');
$check($engagement->setCommunity($fallbackUser,true)['status']==='pending','Withdrawn request can be reactivated.');
$directFeedback=$feedback->feedback(new Request(body:['event'=>'therapists-circle-tabriz','member_access'=>'1','participated'=>'1','content_rating'=>'4','hosting_rating'=>'5'],server:['HTTP_ACCEPT'=>'application/json']));
$check($directFeedback->status()===200 && json_decode($directFeedback->content(),true)['recorded'],'Two ratings alone save feedback without a separate access step.');
$check((new CircleRepository())->findSignup('therapists-circle-tabriz',$fallbackUser['phone'])['status']==='requested','Simplified survey never grants verified attendance.');
$telegramSchemas=[
 'telegram_users'=>'chat_id INTEGER PRIMARY KEY,display_name TEXT,username TEXT,state TEXT,state_data TEXT,created_at TEXT,updated_at TEXT',
 'telegram_updates'=>'update_id INTEGER PRIMARY KEY,payload TEXT,status TEXT,attempts INTEGER,next_attempt_at TEXT,created_at TEXT,updated_at TEXT',
 'telegram_outbox'=>'id TEXT PRIMARY KEY,method TEXT,payload TEXT,status TEXT,attempts INTEGER,next_attempt_at TEXT,created_at TEXT,updated_at TEXT',
 'telegram_questions'=>'id TEXT PRIMARY KEY,chat_id INTEGER,update_id INTEGER UNIQUE,question TEXT,answer TEXT,status TEXT,created_at TEXT,updated_at TEXT',
 'telegram_event_requests'=>'id TEXT PRIMARY KEY,chat_id INTEGER,event_slug TEXT,name TEXT,phone TEXT,city TEXT,status TEXT,created_at TEXT,updated_at TEXT,UNIQUE(chat_id,event_slug)',
];
foreach($telegramSchemas as $table=>$columns) $pdo->exec('CREATE TABLE '.$table.' ('.$columns.')');
$telegram=new \App\Repositories\TelegramRepository(); $bot=new \App\Services\TelegramBotService($telegram);
$message=['chat'=>['id'=>12345,'type'=>'private'],'from'=>['id'=>12345,'first_name'=>'عضو تلگرام'],'text'=>'/start'];
$bot->handle(['update_id'=>1,'message'=>$message]);
$check((int)$pdo->query('SELECT COUNT(*) FROM telegram_users')->fetchColumn()===1,'Telegram private user and menu created.');
$callback=['id'=>'callback1','from'=>$message['from'],'message'=>$message,'data'=>'ask'];
$bot->handle(['update_id'=>2,'callback_query'=>$callback]);
$bot->handle(['update_id'=>3,'message'=>array_replace($message,['text'=>'چگونه عضو جامعه شوم؟'])]);
$question=$pdo->query('SELECT * FROM telegram_questions')->fetch();
$check($question['status']==='new' && $question['question']==='چگونه عضو جامعه شوم؟','Bot question stored for admin.');
$check($telegram->answer($question['id'],'در پروفایل گزینه عضویت جامعه را فعال کنید.'),'Admin answer queued for same chat.');
$count=(int)$pdo->query('SELECT COUNT(*) FROM telegram_outbox')->fetchColumn();
$check(!$telegram->answer($question['id'],'پاسخ تکراری') && (int)$pdo->query('SELECT COUNT(*) FROM telegram_outbox')->fetchColumn()===$count,'Repeated admin answer cannot send twice.');
foreach(['therapists-circle-tabriz','therapists-circle-second'] as $slug) {
 $callback['data']='j:'.\App\Services\TelegramBotService::eventKey($slug); $bot->handle(['update_id'=>4,'callback_query'=>$callback]);
 $check($telegram->user('12345',$message['from'])['state']==='','Archived events cannot start Telegram registration.');
}
$pdo->exec("INSERT INTO content_entities VALUES ('future-event','event','future-meeting','published',0,NULL,'2026-10-04','2026-10-04','2026-10-04')");
$pdo->prepare('INSERT INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata) VALUES (:id,:entity,:locale,:title,:subtitle,:excerpt,:body,:meta)')->execute(['id'=>'future-fa','entity'=>'future-event','locale'=>'fa','title'=>'نشست آینده','subtitle'=>'','excerpt'=>'نشست آزمایشی','body'=>'نشست آزمایشی','meta'=>json_encode(['event_status'=>'registration-open','starts_at'=>'2026-12-01 18:00:00','location'=>'تبریز'])]);
$callback['data']='j:'.\App\Services\TelegramBotService::eventKey('future-meeting');
$bot->handle(['update_id'=>7,'callback_query'=>$callback]);
$check($telegram->user('12345',$message['from'])['state']==='event_name','Published open event starts bot registration.');
$bot->handle(['update_id'=>8,'message'=>array_replace($message,['text'=>'عضو آزمایشی تلگرام'])]);
$bot->handle(['update_id'=>9,'message'=>array_replace($message,['text'=>'۰۹۱۲۲۲۲۲۲۲۲'])]);
$bot->handle(['update_id'=>10,'message'=>array_replace($message,['text'=>'تبریز'])]);
$check($telegram->user('12345',$message['from'])['state']==='event_confirm','Bot collects name, normalized phone and city.');
$callback['data']='confirm'; $bot->handle(['update_id'=>11,'callback_query'=>$callback]);
$bot->handle(['update_id'=>12,'callback_query'=>$callback]);
$check((int)$pdo->query('SELECT COUNT(*) FROM telegram_event_requests')->fetchColumn()===1 && $pdo->query('SELECT phone FROM telegram_event_requests')->fetchColumn()==='09122222222','Confirmed bot request persists once and never issues a paid ticket.');
$check((new \App\Controllers\ResourcesController())->download((function(){ $r=new Request(); $r->setRouteParams(['slug'=>'session-reflection']); return $r; })())->status()===200,'Resources download stays available before metrics migration.');
$pdo->exec('CREATE TABLE resource_downloads (user_id TEXT,resource_slug TEXT,downloads INTEGER,last_download_at TEXT,PRIMARY KEY(user_id,resource_slug))');
$resourceRepo=new \App\Repositories\ResourceRepository(); $resourceRepo->downloaded($fallbackUser['id'],'session-reflection'); $resourceRepo->downloaded($fallbackUser['id'],'session-reflection');
$resourceStats=$resourceRepo->stats()['rows'][0]; $check((int)$resourceStats['members']===1 && (int)$resourceStats['downloads']===2,'Lead-magnet report counts unique members separately from repeat downloads.');
$telegram->state('12345','event_phone',['event'=>'future','name'=>'نام']);
$bot->handle(['update_id'=>5,'message'=>array_replace($message,['text'=>'','contact'=>['user_id'=>987,'phone_number'=>'09120000000']])]);
$check($telegram->user('12345',$message['from'])['state']==='event_phone','Bot rejects another person contact card.');
$bot->handle(['update_id'=>6,'message'=>array_replace($message,['chat'=>['id'=>-1,'type'=>'group']])]);
$check((int)$pdo->query('SELECT COUNT(*) FROM telegram_users')->fetchColumn()===1,'Bot ignores group messages.');
$_ENV['TELEGRAM_WEBHOOK_SECRET']='test-webhook-secret-long';
$webhook=new \App\Controllers\TelegramController(); $update=['update_id'=>99,'message'=>$message]; $raw=json_encode($update);
$check($webhook->webhook(new Request(rawBody:$raw))->status()===401,'Webhook rejects missing secret.');
$request=new Request(server:['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'=>$_ENV['TELEGRAM_WEBHOOK_SECRET']],rawBody:$raw);
$check($webhook->webhook($request)->status()===200 && $webhook->webhook($request)->status()===200 && (int)$pdo->query('SELECT COUNT(*) FROM telegram_updates')->fetchColumn()===1,'Webhook queues duplicate updates exactly once.');
$_ENV['TELEGRAM_BOT_TOKEN']='';
$worker=(new \App\Services\TelegramWorker())->run();
$check($worker['updates']===1 && $pdo->query('SELECT status FROM telegram_updates WHERE update_id=99')->fetchColumn()==='done','Worker handles queued update and marks it done before network sends.');
$check($worker['failed']>0 && (int)$pdo->query("SELECT MAX(attempts) FROM telegram_outbox")->fetchColumn()===1,'Unavailable network produces retryable jobs without losing questions.');
(new AuthService())->refresh($users->findById($user['id']));
$app=new \App\Core\Application(BASE_PATH,true); $router=$app->router();
foreach(['web','auth','admin'] as $routeFile) require BASE_PATH.'/routes/'.$routeFile.'.php';
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/profile']))->status()===200,'Real authenticated profile route renders successfully.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/profile']))->headers()['Cache-Control']==='private, no-store','Personal profile cannot be cached.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/profile/member','HTTP_ACCEPT'=>'application/json']))->status()===419,'Profile route rejects a missing CSRF token.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/profile/phone/send','HTTP_ACCEPT'=>'application/json']))->status()===419,'SMS send route requires CSRF before calling provider.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/profile/phone/verify','HTTP_ACCEPT'=>'application/json']))->status()===419,'OTP verification route requires CSRF.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/admin/sms','HTTP_ACCEPT'=>'application/json']))->status()===403,'Regular members cannot inspect SMS administration.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/admin/telegram','HTTP_ACCEPT'=>'application/json']))->status()===403,'Regular members cannot open bot administration.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/api/telegram/webhook','HTTP_ACCEPT'=>'application/json','HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'=>$_ENV['TELEGRAM_WEBHOOK_SECRET']],rawBody:$raw))->status()===200,'Real webhook route accepts its secret without browser CSRF.');
$pdo->exec("UPDATE users SET status='suspended' WHERE id='".$user['id']."'");
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/profile','HTTP_ACCEPT'=>'application/json']))->status()===401,'A suspended or stale account gets a clean 401 instead of a broken profile.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/profile/phone/send','HTTP_ACCEPT'=>'application/json']))->status()===401,'Suspended accounts cannot request SMS.');
$pdo->exec("UPDATE users SET status='active' WHERE id='".$user['id']."'");
(new AuthService())->refresh($users->findById($user['id']));
$view=new View(VIEW_PATH); $user=$users->findById($user['id']); $admin=$user+[]; $admin['account_role']='super_admin';
$pdo->prepare("UPDATE users SET account_role='super_admin' WHERE id=:id")->execute(['id'=>$user['id']]);
(new AuthService())->refresh($users->findById($user['id']));
$editor=new \App\Controllers\AdminController();
$articleInput=['entity_type'=>'event','slug'=>'editor-test','status'=>'draft','fa_title'=>'مقالهٔ آزمایشی','fa_body'=>'متن مقاله','fa_excerpt'=>'خلاصهٔ آزمایشی','fa_metadata'=>json_encode(['references'=>[['منبع','https://example.test']],'image'=>'images/example.jpg']),'en_title'=>'English article','en_body'=>'Preserve this translation.'];
$created=$editor->workspaceStore(new Request(body:$articleInput),'articles');
$articleId=$pdo->query("SELECT id FROM content_entities WHERE slug='editor-test'")->fetchColumn();
$check(is_string($articleId) && $created->headers()['Location']==='/admin/articles/'.$articleId.'/edit?saved=1','Article workspace creates an article regardless of a forged entity type.');
$check($pdo->query("SELECT entity_type FROM content_entities WHERE slug='editor-test'")->fetchColumn()==='article','Workspace entity type is fixed on the server.');
$updated=$editor->workspaceUpdate(new Request(body:['entity_type'=>'course','slug'=>'editor-test','status'=>'published','fa_title'=>'مقالهٔ ویرایش‌شده','fa_body'=>'متن جدید','image'=>'','featured_present'=>'1']),'articles',$articleId);
$entry=(new \App\Repositories\AdminRepository())->contentEntry($articleId);
$check(($updated->headers()['Location'] ?? '')==='/admin/articles/'.$articleId.'/edit?saved=1' && $entry['entity_type']==='article','Updating cannot switch content type.');
$check($entry['translations']['en']['body']==='Preserve this translation.' && !empty($entry['translations']['fa']['metadata']['references']),'Editing preserves omitted translations and unknown metadata.');
$check($entry['translations']['fa']['metadata']['image']==='' && $entry['translations']['fa']['metadata']['featured']===false,'Optional metadata can actually be cleared.');
$check($editor->workspaceEdit(new Request(),'events',$articleId)->status()===404 && $editor->workspaceUpdate(new Request(body:$articleInput),'events',$articleId)->status()===404,'A different workspace cannot read or overwrite this entry.');
$check($editor->workspaceUpdate(new Request(body:['slug'=>'bad slug','fa_title'=>'a']),'articles',$articleId)->status()===200,'Editor validation renders errors without warnings.');
$check($entry['translations']['fa']['title']==='مقالهٔ ویرایش‌شده','Validation errors leave the stored article intact.');
$auto=$editor->workspaceStore(new Request(body:['fa_title'=>'مقاله با آدرس خودکار','entity_type'=>'article']),'articles');
$check(str_starts_with($auto->headers()['Location'] ?? '', '/admin/articles/') && (int)$pdo->query("SELECT COUNT(*) FROM content_entities WHERE slug LIKE 'article-%'")->fetchColumn()===1,'The editor creates a usable URL when the optional slug is empty.');
$articlePage=$app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/admin/articles/'.$articleId.'/edit']));
$check($articlePage->status()===200 && !str_contains($articlePage->content(),'name="capacity"') && str_contains($articlePage->content(),'name="author"'),'Real article route renders only article fields.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/admin/articles','HTTP_ACCEPT'=>'application/json']))->status()===419,'Dedicated content mutations require CSRF.');
$seoSchema=\App\Services\SeoService::structuredData(\App\Services\SeoService::metadata('منتوریس','معرفی آکادمی'));
$websites=array_values(array_filter($seoSchema['@graph'],fn($item)=>$item['@type']==='WebSite'));
$check(count($websites)===1 && $websites[0]['name']==='منتوریس' && in_array('Mentoris Academy',$websites[0]['alternateName'],true),'There is one consistent branded WebSite schema.');
$homeController=new \App\Controllers\HomeController();
$futureHome=$homeController->index(new Request())->content();
$check(str_contains($futureHome,'رویداد پیش‌رو') && str_contains($futureHome,'/events/future-meeting'),'A published future event is featured on the homepage.');
$futureMeta=$pdo->query("SELECT metadata FROM content_translations WHERE entity_id='future-event'")->fetchColumn();
$fullMeta=json_decode($futureMeta,true); $fullMeta['event_status']='full';
$pdo->prepare("UPDATE content_translations SET metadata=:meta WHERE entity_id='future-event'")->execute(['meta'=>json_encode($fullMeta)]);
$check(str_contains($homeController->index(new Request())->content(),'event-card--full'),'A full upcoming event remains visible with its real status.');
$pdo->exec("UPDATE content_entities SET status='draft' WHERE id='future-event'");
$archivedHome=$homeController->index(new Request())->content();
preg_match('~<section[^>]*id="events".*?</section>~s',$archivedHome,$homeEventSection);
$check(str_contains($homeEventSection[0],'نشست‌های برگزارشده') && str_contains($homeEventSection[0],'/events/therapists-circle-tabriz') && str_contains($homeEventSection[0],'/events/therapists-circle-second'),'Both completed gatherings remain visible when no future event exists.');
$check(substr_count($homeEventSection[0],'event-card--completed')===2 && !str_contains($homeEventSection[0],'content-empty') && !str_contains($homeEventSection[0],'event-capacity'),'Past events replace the empty placeholder without reopening registration.');
if(in_array('--preview',$argv,true)) file_put_contents(BASE_PATH.'/storage/temp/preview-home-events.html',$archivedHome);
$pdo->exec("UPDATE content_entities SET status='published' WHERE id='future-event'");
$pdo->prepare("UPDATE content_translations SET metadata=:meta WHERE entity_id='future-event'")->execute(['meta'=>$futureMeta]);
$member=$profiles->find($user['id']);
$pages=[
 'register'=>$view->render('auth.register',['errors'=>[],'old'=>[],'title'=>'ثبت‌نام'],'layouts.main'),
 'login-phone'=>(new AuthController())->loginForm(new Request())->content(),
 'login-email'=>(new AuthController())->loginForm(new Request(query:['method'=>'email']))->content(),
 'profile'=>$view->render('user.profile',['user'=>$user,'member'=>$member,'memberEnabled'=>true,'old'=>[],'errors'=>[],'success'=>false,'therapist'=>[],'therapistEnabled'=>false,'title'=>'پروفایل'],'layouts.main'),
 'admin-users'=>$view->render('admin.users',['admin'=>$admin,'users'=>$result['rows'],'result'=>$result,'filters'=>[],'roles'=>Authorization::ROLES,'title'=>'اعضا'],'layouts.admin'),
 'admin-feedback'=>$view->render('admin.feedback',['admin'=>$admin,'report'=>$report,'filters'=>[],'events'=>PublicContentService::events(),'title'=>'بازخورد'],'layouts.admin'),
 'admin-qr'=>$view->render('admin.qr',['admin'=>$admin,'baseUrl'=>'https://mentorisacademy.com','feedbackReady'=>true,'title'=>'QR'],'layouts.admin'),
 'feedback'=>$feedback->index(new Request())->content(),
 'notifications'=>$view->render('user.notifications',['user'=>$user,'title'=>'اعلان‌ها'],'layouts.main'),
 'resources'=>$view->render('pages.resources',['title'=>'ابزارهای رایگان','resources'=>\App\Services\ResourceService::all(),'tool'=>null,'member'=>$user],'layouts.main'),
 'resource-sheet'=>$view->render('pages.resources',['title'=>'ابزار','resources'=>\App\Services\ResourceService::all(),'tool'=>\App\Services\ResourceService::all()['session-reflection'],'slug'=>'session-reflection','member'=>$user],'layouts.main'),
 'admin-telegram'=>$view->render('admin.telegram',['title'=>'تلگرام','admin'=>$admin,'filters'=>[],'notice'=>null,'configured'=>false,'report'=>$telegram->dashboard()],'layouts.admin'),
 'admin-article'=>$articlePage->content(),
 'admin-articles'=>$editor->workspace(new Request(),'articles')->content(),
 'admin-event'=>$editor->workspaceNew(new Request(),'events')->content(),
 'admin-seo'=>$editor->seo(new Request())->content(),
 'admin-sms'=>(new \App\Controllers\AdminSmsController())->index(new Request())->content(),
];
$check(!str_contains($pages['profile'],'<script>alert(1)</script>'),'Profile output escapes user text.');
$check((new AuthService())->attempt($second['phone'],'StrongPassword123'),'Unique phone login works before the optional SMS migration.');
(new AuthService())->refresh($users->findById($user['id']));
$pdo->exec('CREATE TABLE phone_verifications (user_id TEXT PRIMARY KEY REFERENCES users(id),phone TEXT UNIQUE,verified_at TEXT)');
$pdo->exec('CREATE TABLE sms_challenges (user_id TEXT PRIMARY KEY REFERENCES users(id))');
$pdo->prepare('INSERT INTO phone_verifications VALUES (:id,:phone,:now)')->execute(['id'=>$user['id'],'phone'=>$user['phone'],'now'=>Database::now()]);
$pdo->prepare('INSERT INTO sms_challenges VALUES (:id)')->execute(['id'=>$user['id']]);
$users->updateProfile($user['id'],['name'=>'همان عضو']);
$check((int)$pdo->query('SELECT COUNT(*) FROM phone_verifications')->fetchColumn()===1,'Saving unchanged phone must retain verification.');
Database::transaction($pdo,static fn()=>$users->updateProfile($user['id'],['phone'=>'09129999999']));
$check((int)$pdo->query('SELECT COUNT(*) FROM phone_verifications')->fetchColumn()===0 && (int)$pdo->query('SELECT COUNT(*) FROM sms_challenges')->fetchColumn()===0,'Phone change releases verified phone and challenge inside an existing transaction.');
$pdo->prepare('INSERT INTO phone_verifications VALUES (:id,:phone,:now)')->execute(['id'=>$user['id'],'phone'=>'09129999999','now'=>Database::now()]);
$users->updateManagedProfile($user['id'],['name'=>$user['name'],'email'=>$user['email'],'phone'=>'09128888888']);
$check((int)$pdo->query('SELECT COUNT(*) FROM phone_verifications')->fetchColumn()===0,'Admin phone edit also invalidates verification and releases the number.');
$check(str_contains($pages['login-phone'],'type="tel" name="identifier"') && str_contains($pages['login-phone'],'autocomplete="username"'),'Phone is the default login field and supports password managers.');
$check(str_contains($pages['login-email'],'type="email" name="identifier"'),'Email fallback has the right mobile keyboard.');
$loginMember=$users->create(['name'=>'آزمایش ورود','email'=>'login@example.test','phone'=>'09121112222','password'=>'LoginPass123']);
$auth=new AuthService(); $auth->logout();
$check($auth->attempt('۰۹۱۲۱۱۱۲۲۲۲','LoginPass123') && $auth->user()['id']===$loginMember['id'],'Persian phone and existing password sign into the correct account without OTP.');
$auth->logout();
$check($auth->attempt('+98 (912) 111-2222','LoginPass123'),'International formatted phone is normalized.');
$auth->logout();
$check(!$auth->attempt('09121112222','WrongPassword123') && $auth->user()===null,'Wrong phone password cannot authenticate.');
$check(!$auth->attempt('09121119999','LoginPass123'),'Unknown phone cannot authenticate.');
$pdo->prepare("UPDATE users SET status='suspended' WHERE id=:id")->execute(['id'=>$loginMember['id']]);
$check(!$auth->attempt('09121112222','LoginPass123'),'Suspended accounts cannot log in by phone.');
$pdo->prepare("UPDATE users SET status='active' WHERE id=:id")->execute(['id'=>$loginMember['id']]);
$duplicateMember=$users->createManaged(['name'=>'شماره تکراری','email'=>'duplicate@example.test','phone'=>'09121112222','password'=>'OtherPass123']);
$check(!$auth->attempt('09121112222','LoginPass123'),'Ambiguous unverified phones cannot select an arbitrary account.');
$check($auth->attempt('LOGIN@EXAMPLE.TEST','LoginPass123') && $auth->user()['id']===$loginMember['id'],'Email remains available for old and duplicate-phone accounts.');
$auth->logout();
$pdo->prepare('INSERT INTO phone_verifications VALUES (:id,:phone,:now)')->execute(['id'=>$loginMember['id'],'phone'=>'09121112222','now'=>Database::now()]);
$check($auth->attempt('09121112222','LoginPass123') && $auth->user()['id']===$loginMember['id'],'Verified phone owner takes precedence over unverified duplicate.');
$auth->logout();
$check(!$auth->attempt('09121112222','OtherPass123'),'Duplicate account password cannot access verified phone owner.');
$authController=new AuthController();
$check(str_contains($authController->login(new Request(body:['identifier'=>['bad'],'password'=>'LoginPass123']))->content(),'اطلاعات ورود را بررسی کنید'),'Array identifier fails cleanly.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/login','HTTP_ACCEPT'=>'application/json']))->status()===419,'Phone login requires CSRF.');
$pdo->exec('DELETE FROM rate_limits');
for($i=0;$i<10;$i++) $authController->login(new Request(body:['identifier'=>$i%2 ? 'login@example.test' : '۰۹۱۲۱۱۱۲۲۲۲','password'=>'WrongPassword123']));
$response=$authController->login(new Request(body:['identifier'=>'+989121112222','password'=>'LoginPass123']));
$check(str_contains($response->content(),'تلاش‌های ورود بیش از حد مجاز') && $auth->user()===null,'Switching phone/email or digit format cannot bypass the shared account limit.');
$pdo->exec("UPDATE rate_limits SET reset_at='2000-01-01 00:00:00'");
(new Session())->put('auth.intended','//evil.example'); $beforeCsrf=csrf_token();
$response=$authController->login(new Request(body:['identifier'=>'09121112222','password'=>'LoginPass123']));
$check(($response->headers()['Location'] ?? '')==='/dashboard' && $auth->user()['id']===$loginMember['id'],'Valid phone login works after cooldown and rejects external return paths.');
$check(csrf_token()!==$beforeCsrf,'Successful phone login rotates CSRF token.');
$auth->logout();
$legacyResponse=$authController->login(new Request(body:['email'=>'login@example.test','password'=>'LoginPass123']));
$check(($legacyResponse->headers()['Location'] ?? '')==='/dashboard' && $auth->user()['id']===$loginMember['id'],'Cached legacy email form submissions still sign into the correct account.');
// Signup accepts phone formatting, rejects malformed inputs, and prevents a second public account.
foreach(['09127776666','+98 (912) 777-6666','989127776666','00989127776666','۰۹۱۲ ۷۷۷ ۶۶۶۶','+٩٨٩١٢٧٧٧٦٦٦٦'] as $phone) {
 $check(\App\Core\PhoneNumber::normalize($phone)==='09127776666','Supported phone formats share a canonical identity.');
}
$auth->logout();
$formatted=array_replace($register,['email'=>'formatted@example.test','phone'=>'+۹۸ (۹۱۲) ۷۷۷-۶۶۶۶']);
$result=$authController->register(new Request(body:$formatted));
$check(($result->headers()['Location'] ?? '')==='/profile?welcome=1' && $users->findByEmail($formatted['email'])['phone']==='09127776666','Formatted Persian international signup creates a canonical phone and immediately opens profile.');
$auth->logout();
$count=(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
foreach(['09127776666','+989127776666','00989127776666','۰۹۱۲۷۷۷۶۶۶۶'] as $phone) {
 $result=$authController->register(new Request(body:array_replace($formatted,['email'=>'new-address@example.test','phone'=>$phone])));
 $check(str_contains($result->content(),'این شماره موبایل قبلاً ثبت شده') && (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===$count && $auth->user()===null,'Alternate format or email cannot create or authenticate a duplicate account.');
}
foreach([['bad'],'0912abc1234','+979127776666','091277766666',str_repeat('0',241)] as $phone) {
 $result=$authController->register(new Request(body:array_replace($formatted,['email'=>'bad-phone@example.test','phone'=>$phone])));
 $check(str_contains($result->content(),'شماره موبایل معتبر وارد کنید') && $users->findByEmail('bad-phone@example.test')===null,'Malformed phone signup fails cleanly without account creation.');
}
$legacy=$users->createManaged(['name'=>'عضو قدیمی','email'=>'old-format@example.test','phone'=>'+۹۸ (۹۱۲) ۷۷۷-۵۵۵۵','password'=>'LegacyPass123']);
$result=$authController->register(new Request(body:array_replace($formatted,['email'=>'legacy-duplicate@example.test','phone'=>'09127775555'])));
$check(str_contains($result->content(),'این شماره موبایل قبلاً ثبت شده') && $users->findByEmail('legacy-duplicate@example.test')===null,'Historical formatted phone blocks duplicate public registration without modifying its owner.');
$check(strpos($pages['register'],'name="phone"') < strpos($pages['register'],'name="name"') && str_contains($pages['register'],'inputmode="tel"'),'Phone comes first with the mobile keypad.');
$parallelPath=STORAGE_PATH.'/temp/signup-test-'.bin2hex(random_bytes(8)).'.sqlite';
$parallel=Database::connect(['driver'=>'sqlite','database'=>$parallelPath]);
foreach($schemas as $table=>$columns) $parallel->exec('CREATE TABLE '.$table.' ('.$columns.')');
$workers=[];
try {
 for($i=0;$i<8;$i++) {
  $process=proc_open([PHP_BINARY,__FILE__,'--signup-worker',$parallelPath,(string)$i],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
  if(!is_resource($process)) throw new RuntimeException('Cannot launch signup concurrency worker.');
  $workers[]=[$process,$pipes];
 }
 $created=0;
 foreach($workers as [$process,$pipes]) {
  $output=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
  $check(proc_close($process)===0 && $error==='' && in_array($output,['created','duplicate'],true),'Concurrent signup worker must finish cleanly: '.$error);
  $created+=(int)($output==='created');
 }
 $check($created===1 && (int)$parallel->query('SELECT COUNT(*) FROM users')->fetchColumn()===1,'Eight simultaneous public signups with alternate formats create exactly one account.');
} finally { unset($parallel); if(is_file($parallelPath)) unlink($parallelPath); }
// Required SMS registration, with isolated DB and fake transport; never sends a real message.
$pdo->exec('CREATE TABLE registration_sms_challenges (session_hash TEXT PRIMARY KEY,phone_hash TEXT,challenge_id TEXT,code_hash TEXT,expires_at TEXT,attempts INTEGER,status TEXT,sent_at TEXT,updated_at TEXT)');
$pdo->exec('CREATE TABLE sms_deliveries (id TEXT PRIMARY KEY,user_id TEXT REFERENCES users(id),phone_hash TEXT,kind TEXT,status TEXT,provider_message_id INTEGER,provider_code INTEGER,cost NUMERIC,created_at TEXT,updated_at TEXT)');
$pdo->exec('DELETE FROM rate_limits');
$_ENV['SMS_REGISTRATION_REQUIRED']='true'; $_ENV['SMS_ENABLED']='true'; $_ENV['SMS_IR_API_KEY']='fake-provider-key'; $_ENV['SMS_IR_OTP_TEMPLATE_ID']='123456';
$otp=''; $sendCalls=0; $sendMode='sent';
$client=new \App\Services\SmsIrClient(static function(array $payload) use(&$otp,&$sendCalls,&$sendMode): array {
 $sendCalls++; $otp=$payload['parameters'][0]['value'];
 return $sendMode==='sent' ? ['http'=>200,'body'=>'{"status":1,"data":{"messageId":321,"cost":1}}'] : ['http'=>400,'body'=>'{"status":5}'];
});
$service=new \App\Services\RegistrationPhoneService($pdo,$client);
$otpSignup=array_replace($register,['email'=>'otp-member@example.test','phone'=>'+989124440001','password'=>'OtpPass123','password_confirmation'=>'OtpPass123']);
$missing=$authController->register(new Request(body:$otpSignup));
$check($users->findByEmail($otpSignup['email'])===null && str_contains($missing->content(),'ابتدا برای همین شماره'),'Signup cannot bypass required OTP via direct POST.');
$context=\App\Services\RegistrationPhoneService::context();
$check($service->send($context,$otpSignup['phone'],'192.0.2.41')['status']===200 && $sendCalls===1,'Guest signup sends exactly one fixed-template OTP.');
$check($service->send($context,'۰۹۱۲۴۴۴۰۰۰۱','192.0.2.42')['status']===429 && $sendCalls===1,'Alternate format or IP cannot bypass signup phone cooldown.');
$storedOtp=$pdo->query('SELECT * FROM registration_sms_challenges')->fetch();
$check($storedOtp['code_hash']!==$otp && strlen($storedOtp['code_hash'])===64 && !str_contains(json_encode($service->send('different-session','09124440001','192.0.2.43')),$otp),'Only an HMAC is stored and responses do not expose the OTP.');
$check($service->verify('another-browser',$otpSignup['phone'],$otp)['status']===422,'OTP is bound to the browser session.');
$check($service->verify($context,'09124440002',$otp)['status']===422,'Changing the signup phone cannot reuse its OTP.');
$wrong=$otp==='111111' ? '222222' : '111111';
$check($service->verify($context,$otpSignup['phone'],$wrong)['status']===422,'Wrong signup OTP fails.');
$otpRow=$pdo->query('SELECT attempts FROM registration_sms_challenges')->fetchColumn();
$check((int)$otpRow===1,'Wrong-code attempts persist despite a failed signup.');
$persianOtp=strtr($otp,array_combine(str_split('0123456789'),preg_split('//u','۰۱۲۳۴۵۶۷۸۹',-1,PREG_SPLIT_NO_EMPTY)));
$result=$authController->register(new Request(body:$otpSignup+['phone_code'=>$persianOtp]));
$otpUser=$users->findByEmail($otpSignup['email']);
$check(($result->headers()['Location'] ?? '')==='/profile?welcome=1' && $otpUser!==null && $auth->user()['id']===$otpUser['id'],'Correct Persian OTP creates the account and opens profile.');
$check($pdo->query('SELECT phone FROM phone_verifications WHERE user_id=\''.$otpUser['id'].'\'')->fetchColumn()==='09124440001','Signup marks the exact phone verified atomically with account creation.');
$check($pdo->query('SELECT status FROM registration_sms_challenges')->fetchColumn()==='consumed' && $service->verify($context,$otpSignup['phone'],$otp)['status']===422,'Consumed signup code cannot be replayed.');
$check((new Session())->get('registration.phone.context')===null,'Signup removes the pending session context.');
$auth->logout();
try { $users->create(array_replace($otpSignup,['email'=>'bypass@example.test','phone'=>'09124440009'])); $check(false,'Repository OTP bypass must fail.'); }
catch(RuntimeException $e) { $check($users->findByEmail('bypass@example.test')===null,'Repository rolls back an account without verified proof.'); }
$expireSms=static function() use($pdo): void { $pdo->exec("UPDATE rate_limits SET reset_at='2000-01-01 00:00:00'"); };
$expireSms(); $context=\App\Services\RegistrationPhoneService::context(); $service->send($context,'09124440003','192.0.2.41');
$pdo->exec("UPDATE registration_sms_challenges SET expires_at='2000-01-01 00:00:00'");
$check($service->verify($context,'09124440003',$otp)['status']===422,'Expired signup code fails.');
$expireSms(); $service->send($context,'09124440003','192.0.2.41'); $wrong=$otp==='111111' ? '222222' : '111111';
for($i=0;$i<5;$i++) $service->verify($context,'09124440003',$wrong);
$check($service->verify($context,'09124440003',$otp)['status']===422,'Five wrong guesses permanently exhaust that signup challenge.');
$expireSms(); $sendMode='failed'; $check($service->send($context,'09124440004','192.0.2.41')['status']===503 && $service->verify($context,'09124440004',$otp)['status']===422,'Rejected SMS cannot verify a signup.');
$before=$sendCalls; $check($service->send($context,'09124440004','192.0.2.44')['status']===429 && $sendCalls===$before,'Failed signup SMS still consumes its quota.');
$_ENV['SMS_ENABLED']='false'; $check($service->send($context,'09124440005','192.0.2.41')['status']===503,'Disabled provider cannot send a signup code.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/register/phone/send','HTTP_ACCEPT'=>'application/json']))->status()===419,'Guest OTP sending requires CSRF.');
$pages['register-otp']=$view->render('auth.register',['errors'=>[],'old'=>[],'title'=>'ثبت‌نام'],'layouts.main');
$check(str_contains($pages['register-otp'],'autocomplete="one-time-code"') && str_contains($pages['register-otp'],'data-registration-send'),'Required registration view supports mobile OTP autofill and sending.');
if(in_array('--preview',$argv,true)) {
 foreach($pages as $name=>$html) file_put_contents(BASE_PATH.'/storage/temp/preview-'.$name.'.html',$html);
}
session_write_close();
echo "PASS: {$checks} integration checks; ".count($pages)." views rendered without warnings.\n";

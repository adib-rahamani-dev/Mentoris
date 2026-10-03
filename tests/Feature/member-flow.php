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

$_ENV['APP_ENV']='local'; $_ENV['MAIL_MAILER']='log'; $_ENV['SESSION_DRIVER']='files'; $_ENV['APP_URL']='http://127.0.0.1:8098';
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
 'content_entities'=>'id TEXT PRIMARY KEY,entity_type TEXT,slug TEXT,status TEXT,sort_order INTEGER,author_id TEXT,published_at TEXT,created_at TEXT,updated_at TEXT',
 'content_translations'=>'id TEXT PRIMARY KEY,entity_id TEXT,locale TEXT,title TEXT,subtitle TEXT,excerpt TEXT,body TEXT,metadata TEXT',
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
$pdo->prepare('INSERT INTO content_translations VALUES (:id,:entity,:locale,:title,:subtitle,:excerpt,:body,:meta)')->execute(['id'=>'future-fa','entity'=>'future-event','locale'=>'fa','title'=>'نشست آینده','subtitle'=>'','excerpt'=>'نشست آزمایشی','body'=>'نشست آزمایشی','meta'=>json_encode(['event_status'=>'registration-open','starts_at'=>'2026-12-01 18:00:00','location'=>'تبریز'])]);
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
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/profile/member','HTTP_ACCEPT'=>'application/json']))->status()===419,'Profile route rejects a missing CSRF token.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/admin/telegram','HTTP_ACCEPT'=>'application/json']))->status()===403,'Regular members cannot open bot administration.');
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/api/telegram/webhook','HTTP_ACCEPT'=>'application/json','HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'=>$_ENV['TELEGRAM_WEBHOOK_SECRET']],rawBody:$raw))->status()===200,'Real webhook route accepts its secret without browser CSRF.');
$pdo->exec("UPDATE users SET status='suspended' WHERE id='".$user['id']."'");
$check($app->handle(new Request(server:['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/profile','HTTP_ACCEPT'=>'application/json']))->status()===401,'A suspended or stale account gets a clean 401 instead of a broken profile.');
$pdo->exec("UPDATE users SET status='active' WHERE id='".$user['id']."'");
(new AuthService())->refresh($users->findById($user['id']));
$view=new View(VIEW_PATH); $user=$users->findById($user['id']); $admin=$user+[]; $admin['account_role']='super_admin';
$member=$profiles->find($user['id']);
$pages=[
 'register'=>$view->render('auth.register',['errors'=>[],'old'=>[],'title'=>'ثبت‌نام'],'layouts.main'),
 'profile'=>$view->render('user.profile',['user'=>$user,'member'=>$member,'memberEnabled'=>true,'old'=>[],'errors'=>[],'success'=>false,'therapist'=>[],'therapistEnabled'=>false,'title'=>'پروفایل'],'layouts.main'),
 'admin-users'=>$view->render('admin.users',['admin'=>$admin,'users'=>$result['rows'],'result'=>$result,'filters'=>[],'roles'=>Authorization::ROLES,'title'=>'اعضا'],'layouts.admin'),
 'admin-feedback'=>$view->render('admin.feedback',['admin'=>$admin,'report'=>$report,'filters'=>[],'events'=>PublicContentService::events(),'title'=>'بازخورد'],'layouts.admin'),
 'admin-qr'=>$view->render('admin.qr',['admin'=>$admin,'baseUrl'=>'https://mentorisacademy.com','feedbackReady'=>true,'title'=>'QR'],'layouts.admin'),
 'feedback'=>$feedback->index(new Request())->content(),
 'notifications'=>$view->render('user.notifications',['user'=>$user,'title'=>'اعلان‌ها'],'layouts.main'),
 'resources'=>$view->render('pages.resources',['title'=>'ابزارهای رایگان','resources'=>\App\Services\ResourceService::all(),'tool'=>null,'member'=>$user],'layouts.main'),
 'resource-sheet'=>$view->render('pages.resources',['title'=>'ابزار','resources'=>\App\Services\ResourceService::all(),'tool'=>\App\Services\ResourceService::all()['session-reflection'],'slug'=>'session-reflection','member'=>$user],'layouts.main'),
 'admin-telegram'=>$view->render('admin.telegram',['title'=>'تلگرام','admin'=>$admin,'filters'=>[],'notice'=>null,'configured'=>false,'report'=>$telegram->dashboard()],'layouts.admin'),
];
$check(!str_contains($pages['profile'],'<script>alert(1)</script>'),'Profile output escapes user text.');
if(in_array('--preview',$argv,true)) {
 foreach($pages as $name=>$html) file_put_contents(BASE_PATH.'/storage/temp/preview-'.$name.'.html',$html);
}
session_write_close();
echo "PASS: {$checks} integration checks; ".count($pages)." views rendered without warnings.\n";

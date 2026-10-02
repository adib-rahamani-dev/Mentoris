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
Translator::boot([],[],[]);
set_error_handler(static function(int $severity,string $message,string $file,int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message,0,$severity,$file,$line);
});
$pdo=Database::connect(['driver'=>'sqlite','database'=>':memory:']); Database::use($pdo);
$schemas=[
 'users'=>"id TEXT PRIMARY KEY,name TEXT,email TEXT UNIQUE,password_hash TEXT,phone TEXT,professional_role TEXT,bio TEXT,account_role TEXT,status TEXT,auth_version INTEGER,email_verified_at TEXT,last_login_at TEXT,password_changed_at TEXT,created_at TEXT,updated_at TEXT",
 'enrollments'=>"id TEXT,user_id TEXT,course_slug TEXT,status TEXT,enrolled_at TEXT",
 'event_registrations'=>"user_id TEXT,event_slug TEXT,status TEXT,created_at TEXT",
 'certificates'=>"id TEXT,user_id TEXT,course_slug TEXT,certificate_number TEXT,issued_at TEXT,revoked_at TEXT",
 'notifications'=>"id TEXT PRIMARY KEY,user_id TEXT,title TEXT,message TEXT,created_at TEXT,read_at TEXT",
 'password_reset_tokens'=>"id TEXT PRIMARY KEY,user_id TEXT,token_hash TEXT,expires_at TEXT,used_at TEXT,created_at TEXT",
 'member_profiles'=>"user_id TEXT PRIMARY KEY REFERENCES users(id),member_type TEXT,education_status TEXT,field_of_study TEXT,degree TEXT,university TEXT,city TEXT,practice_status TEXT,specialty_fields TEXT,details TEXT,training_courses TEXT,marketing_consent INTEGER,terms_accepted_at TEXT,completed_at TEXT,updated_at TEXT",
 'event_signups'=>"id TEXT PRIMARY KEY,user_id TEXT,event_slug TEXT,name TEXT,phone TEXT,city TEXT,status TEXT,attended_at TEXT,created_at TEXT,updated_at TEXT,UNIQUE(event_slug,phone)",
 'event_feedback'=>"id TEXT PRIMARY KEY,signup_id TEXT UNIQUE REFERENCES event_signups(id),content_rating INTEGER,hosting_rating INTEGER,challenge TEXT,comment TEXT,created_at TEXT",
];
foreach($schemas as $table=>$columns) $pdo->exec('CREATE TABLE '.$table.' ('.$columns.')');
$checks=0;
$check=function(bool $condition,string $message) use (&$checks): void { if(!$condition) throw new RuntimeException($message); $checks++; };
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
];
$check(!str_contains($pages['profile'],'<script>alert(1)</script>'),'Profile output escapes user text.');
if(in_array('--preview',$argv,true)) {
 foreach($pages as $name=>$html) file_put_contents(BASE_PATH.'/storage/temp/preview-'.$name.'.html',$html);
}
session_write_close();
echo "PASS: {$checks} integration checks; ".count($pages)." views rendered without warnings.\n";

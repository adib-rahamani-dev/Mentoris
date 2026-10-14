<?php
declare(strict_types=1);
// Isolated SQLite integration checks; no live database, email or SMS calls.
require dirname(__DIR__,2).'/bootstrap/constants.php';
require BASE_PATH.'/bootstrap/autoload.php';
use App\Core\{Database, Request, Session, Translator, View, Security};
use App\Services\{PublicContentService, ContentReleaseService, AuthService};
use App\Repositories\{AdminRepository, CourseRequestRepository, CircleRepository};
use App\Controllers\{AdminController, EventsController, CoursesController, ArticlesController, MentorsController};
$_ENV['APP_ENV']='local'; $_ENV['APP_KEY']=\App\Core\Crypto::generateKey(); $_ENV['SESSION_DRIVER']='files'; $_ENV['APP_URL']='http://127.0.0.1:8102';
Translator::boot([],[],[]);
set_error_handler(static function(int $severity,string $message,string $file,int $line): bool { if(!(error_reporting()&$severity)) return false; throw new ErrorException($message,0,$severity,$file,$line); });
$pdo=Database::connect(['driver'=>'sqlite','database'=>':memory:']); Database::use($pdo);
$schemas=[
 'users'=>'id TEXT PRIMARY KEY,name TEXT,email TEXT UNIQUE,password_hash TEXT,phone TEXT,professional_role TEXT,bio TEXT,account_role TEXT,status TEXT,auth_version INTEGER,email_verified_at TEXT,last_login_at TEXT,password_changed_at TEXT,created_at TEXT,updated_at TEXT',
 'content_entities'=>'id TEXT PRIMARY KEY,entity_type TEXT,slug TEXT,status TEXT,sort_order INTEGER,author_id TEXT REFERENCES users(id),published_at TEXT,created_at TEXT,updated_at TEXT,UNIQUE(entity_type,slug)',
 'content_translations'=>'id TEXT PRIMARY KEY,entity_id TEXT REFERENCES content_entities(id),locale TEXT,title TEXT,subtitle TEXT,excerpt TEXT,body TEXT,metadata TEXT,created_at TEXT,updated_at TEXT,UNIQUE(entity_id,locale)',
 'event_signups'=>'id TEXT PRIMARY KEY,user_id TEXT REFERENCES users(id),event_slug TEXT,name TEXT,phone TEXT,city TEXT,status TEXT,attended_at TEXT,created_at TEXT,updated_at TEXT,UNIQUE(event_slug,phone)',
 'circle_applications'=>'signup_id TEXT PRIMARY KEY REFERENCES event_signups(id),details TEXT,resume TEXT,created_at TEXT',
 'course_requests'=>'id TEXT PRIMARY KEY,user_id TEXT REFERENCES users(id),course_slug TEXT,name TEXT,phone TEXT,payment_plan TEXT,status TEXT,created_at TEXT,updated_at TEXT,UNIQUE(course_slug,phone)',
 'enrollments'=>'id TEXT,user_id TEXT,course_slug TEXT,status TEXT,enrolled_at TEXT',
 'event_registrations'=>'id TEXT,user_id TEXT,event_slug TEXT,status TEXT,created_at TEXT',
 'certificates'=>'id TEXT,user_id TEXT,course_slug TEXT,certificate_number TEXT,issued_at TEXT,revoked_at TEXT',
 'notifications'=>'id TEXT,user_id TEXT,title TEXT,message TEXT,created_at TEXT,read_at TEXT',
 'password_reset_tokens'=>'id TEXT,user_id TEXT,token_hash TEXT,expires_at TEXT,used_at TEXT,created_at TEXT',
 'audit_logs'=>'id TEXT PRIMARY KEY,actor_id TEXT,action TEXT,subject_type TEXT,subject_id TEXT,old_values TEXT,new_values TEXT,ip_hash TEXT,created_at TEXT',
];
foreach($schemas as $table=>$fields) $pdo->exec('CREATE TABLE '.$table.' ('.$fields.')');
$checks=0; $check=static function(bool $condition,string $message) use(&$checks): void { if(!$condition) throw new RuntimeException($message); $checks++; };
$sync=new ContentReleaseService(); $first=$sync->sync($pdo); $second=$sync->sync($pdo);
$check($first['created']===8 && $second['created']===0 && $second['preserved']===8,'Release imports eight real records exactly once.');
$check((int)$pdo->query('SELECT COUNT(*) FROM content_translations')->fetchColumn()===8,'Repeated import creates no duplicate translations.');
$third=PublicContentService::event('therapists-circle-third');
$check($third!==null && $third['can_register'] && $third['price_amount']===600000 && $third['date_iso']==='2026-10-16' && $third['time']==='۱۷ تا ۱۹','Third circle has the requested price, date and open application flow.');
$check($third['external_registration_url']==='' && $third['location_pending'],'Empty registration URL remains internal, and venue is honestly pending.');
foreach(['therapists-circle-tabriz','therapists-circle-second'] as $slug) $check(PublicContentService::event($slug)['status']==='completed','Past gatherings remain closed.');
$course=PublicContentService::course('psychotherapy-mastery-deliberate-practice');
$check($course['price_amount']===15000000 && $course['can_request'] && !$course['can_enroll'] && count($course['curriculum'])===13,'Course is a priced database-backed application, with thirteen curriculum sections.');
$check(count($course['instructors'])===2 && $course['payment_options']===['cash','installments'],'Both instructors and payment preferences are present.');
$check(!$course['certificate_confirmed'],'Unconfirmed certificate terms are not invented.');
$article=PublicContentService::article('deliberate-practice-first-step-mastery');
$check(count($article['sections'])>=9 && count($article['references'])===3 && $article['author_slug']==='ali-feizi','Article exposes structured headings, references and the real author profile.');
$check(PublicContentService::articles()[0]['slug']===$article['slug'],'Featured release article appears first, including on the homepage.');
$check(count(PublicContentService::mentors())===3,'All three professional profiles resolve from the database.');
$view=new View(VIEW_PATH); $pages=[];
$pages['events']=(new EventsController())->index(new Request())->content();
$pages['third-circle']=(new EventsController())->show(new Request(),'therapists-circle-third')->content();
$pages['workshop']=(new CoursesController())->show(new Request(),$course['slug'])->content();
$pages['journal']=(new ArticlesController())->show(new Request(),$article['slug'])->content();
$pages['ali']=(new MentorsController())->show(new Request(),'ali-feizi')->content();
$pages['hanieh']=(new MentorsController())->show(new Request(),'hanieh-haghani')->content();
$check(str_contains($pages['events'],'therapists-circle-second-v1.jpg') && str_contains($pages['events'],'therapists-circle-third-v1.jpg'),'Archive and upcoming posters both render.');
$check(str_contains($pages['workshop'],'deliberate-practice-workshop-v1.jpg') && str_contains($pages['workshop'],'enrollment-gate') && !str_contains($pages['workshop'],'/checkout/course/'),'Course poster and real request form replace the payment dead end.');
$check(str_contains($pages['journal'],'article-takeaways') && str_contains($pages['journal'],'/mentors/ali-feizi') && str_contains($pages['journal'],'#section-2'),'Article has visual highlights, author and working heading anchors.');
$eventController=new EventsController();
$posted=['name'=>'عضو رویداد','phone'=>'+۹۸ (۹۱۲) ۷۷۷-۶۶۶۶','city'=>'تبریز'];
$response=$eventController->register(new Request(body:$posted),'therapists-circle-third');
$check($response->status()===303 && (new CircleRepository())->findSignup('therapists-circle-third','09127776666')===null,'Anonymous applications require sign-in and profile completion.');
$posted+=['education'=>'ارشد','university'=>'دانشگاه تبریز','specialization'=>'بالینی','experience'=>'۳ سال','approach'=>'ACT','practice_areas'=>'بزرگسال','professional_number'=>'','motivation'=>''];
$response=$eventController->register(new Request(body:$posted),'therapists-circle-third');
$check($response->status()===303 && (int)$pdo->query('SELECT COUNT(*) FROM event_signups')->fetchColumn()===0,'Professional text alone cannot bypass authentication.');
$file=(new \App\Services\SupportFileService())->store("%PDF-1.4\n% Test resume\n",'resume.pdf');
$posted['application']=array_intersect_key($posted,array_flip(['education','university','specialization','experience','approach','practice_areas','professional_number','motivation']));$posted['resume']=$file;
(new CircleRepository())->signup('therapists-circle-third',$posted,null);
$signup=(new CircleRepository())->findSignup('therapists-circle-third','09127776666');
$application=(new CircleRepository())->application($signup['id']);
$check($signup['status']==='requested' && $application['details']['university']==='دانشگاه تبریز' && $application['resume']['filename']==='resume.pdf','Professional details and private resume metadata persist with the pending circle request.');
$check((new EventsController())->resume(new Request())->status()===403,'Anonymous visitors cannot download resumes.');
unlink(BASE_PATH.'/storage/uploads/support/'.$file['local_name']);
$eventController->register(new Request(body:$posted),'therapists-circle-third');
$check((int)$pdo->query('SELECT COUNT(*) FROM event_signups')->fetchColumn()===1,'Duplicate circle request is prevented.');
$eventController->register(new Request(body:$posted),'therapists-circle-second');
$check((int)$pdo->query('SELECT COUNT(*) FROM event_signups')->fetchColumn()===1,'Past circles cannot accept applications.');
$response=$eventController->register(new Request(body:['name'=>['bad'],'phone'=>['bad'],'city'=>['bad']]),'therapists-circle-third');
$check($response->status()===303,'Malformed event input fails cleanly.');
$courseController=new CoursesController(); $requestData=['name'=>'متقاضی دوره','phone'=>'۰۹۱۲۷۷۷۵۵۵۵','payment_plan'=>'installments'];
$response=$courseController->requestPlace(new Request(body:$requestData),$course['slug']);
(new \App\Repositories\CourseRequestRepository())->request($course['slug'],$requestData,null); $stored=$pdo->query('SELECT * FROM course_requests')->fetch();
$check($response->status()===303 && $courseController->show(new Request(),$course['slug'])->headers()['Cache-Control']==='private, no-store','Course application redirects after POST and personal form state cannot be shared by caching.');
$check($stored['status']==='requested' && $stored['payment_plan']==='installments' && $stored['phone']==='09127775555','Installment preference is actually saved, with pending status.');
$courseController->requestPlace(new Request(body:$requestData),$course['slug']);
$check((int)$pdo->query('SELECT COUNT(*) FROM course_requests')->fetchColumn()===1,'Duplicate course application cannot create a second record.');
$courseController->requestPlace(new Request(body:['name'=>'نام آزمایش','phone'=>'09127775554','payment_plan'=>'invalid']),$course['slug']);
$check((int)$pdo->query('SELECT COUNT(*) FROM course_requests')->fetchColumn()===1,'Invalid payment preference cannot persist.');
$adminId='aaaaaaaaaaaaaaaaaaaaaaaa'; $now=Database::now();
$pdo->prepare("INSERT INTO users (id,name,email,password_hash,phone,professional_role,bio,account_role,status,auth_version,created_at,updated_at) VALUES (:id,'مدیر آزمایش','admin@example.test',:hash,'09120000000','','','super_admin','active',1,:created,:updated)")->execute(['id'=>$adminId,'hash'=>Security::hashPassword('TestAdmin123'),'created'=>$now,'updated'=>$now]);
(new AuthService())->refresh(['id'=>$adminId,'name'=>'مدیر آزمایش','email'=>'admin@example.test','account_role'=>'super_admin','auth_version'=>1]);
$admin=new AdminController(); $repository=new AdminRepository();
$circleId=$signup['id']; $repository->updateEngagementStatus('circle',$circleId,'approved');
$check((new CircleRepository())->findSignup('therapists-circle-third','09127776666')['status']==='approved','Admin approval is persisted.');
$repository->updateEngagementStatus('courses',$stored['id'],'approved');
$check($pdo->query('SELECT status FROM course_requests')->fetchColumn()==='approved','Admin can approve a course request.');
$check($repository->engagements('circle',['status'=>'approved','context'=>'therapists-circle-second'])['pagination']['total']===0,'Admin can filter by the selected circle.');
$pages['admin-requests']=$admin->engagements(new Request(query:['type'=>'courses']))->content();
$check(str_contains($pages['admin-requests'],'درخواست‌های دوره‌ها') && str_contains($pages['admin-requests'],'تأییدشده'),'Operations view uses readable statuses and a course inbox.');
$entity=$pdo->query("SELECT id FROM content_entities WHERE entity_type='course'")->fetchColumn();
$pages['admin-course']=$admin->workspaceEdit(new Request(),'courses',$entity)->content();
$pages['admin-article']=$admin->workspaceEdit(new Request(),'articles',$pdo->query("SELECT id FROM content_entities WHERE entity_type='article'")->fetchColumn())->content();
$check(str_contains($pages['admin-course'],'data-content-editor') && str_contains($pages['admin-course'],'curriculum[12][title]'),'Course editor contains a wizard and editable curriculum, not raw JSON only.');
$adminInput=['fa_title'=>'کارگاه ویرایش‌شده','fa_body'=>'معرفی به‌روزشده','fa_excerpt'=>'توضیح دوره','status'=>'published','price_amount'=>'۱۵٬۰۰۰٬۰۰۰','capacity'=>'0','content_status'=>'active','registration_mode'=>'request','payment_options_present'=>'1','payment_options'=>['cash','installments'],'payment_note'=>'هماهنگی پس از بررسی','curriculum_present'=>'1','curriculum'=>[['title'=>'سرفصل تازه','description'=>'شرح تازه','duration'=>'']],'faq_present'=>'1','faq'=>[['question'=>'پرسش تازه','answer'=>'پاسخ تازه']],'related_mentors'=>['maryam-haghani','ali-feizi']];
$saved=$admin->workspaceUpdate(new Request(body:$adminInput),'courses',$entity);
$updated=PublicContentService::course($course['slug']);
$check(isset($saved->headers()['Location']) && $updated['title']==='کارگاه ویرایش‌شده' && $updated['price_amount']===15000000 && $updated['curriculum'][0]['title']==='سرفصل تازه','Admin course changes round-trip through the real database.');
$check($updated['image']===$course['image'] && count($updated['instructors'])===2,'Saving preserves image and both instructors.');
$sync->sync($pdo);
$check(PublicContentService::course($course['slug'])['title']==='کارگاه ویرایش‌شده','Deploy sync preserves subsequent admin edits.');
$admin->workspaceUpdate(new Request(body:array_replace($adminInput,['price_amount'=>'15wrong'])),'courses',$entity);
$check(PublicContentService::course($course['slug'])['price_amount']===15000000,'Invalid admin price cannot corrupt the saved amount.');
$admin->workspaceUpdate(new Request(body:array_replace($adminInput,['curriculum'=>[['title'=>['bad'],'description'=>'bad']]])),'courses',$entity);
$check(PublicContentService::course($course['slug'])['curriculum'][0]['title']==='سرفصل تازه','Malformed curriculum fails cleanly and does not erase data.');
$invalidRows=$admin->workspaceUpdate(new Request(body:array_replace($adminInput,['curriculum'=>[['title'=>'','description'=>'شرحی که نباید از دست برود']]])),'courses',$entity);
$check(str_contains($invalidRows->content(),'شرحی که نباید از دست برود'),'A validation error preserves the operator\'s partially completed curriculum rows.');
$eventId=$pdo->query("SELECT id FROM content_entities WHERE slug='therapists-circle-third'")->fetchColumn();
$eventInput=['fa_title'=>$third['title'],'fa_body'=>$third['description'],'status'=>'published','starts_at'=>'2026-10-16T17:00','ends_at'=>'2026-10-16T19:00','content_status'=>'registration-open','format'=>'offline','price_amount'=>'۶۰۰,۰۰۰'];
$admin->workspaceUpdate(new Request(body:$eventInput),'events',$eventId);
$check(PublicContentService::event('therapists-circle-third')['starts_at']==='2026-10-16T17:00:00+03:30','Admin dates preserve Tehran timezone correctly.');
$admin->workspaceUpdate(new Request(body:array_replace($eventInput,['ends_at'=>'2026-10-16T16:00'])),'events',$eventId);
$check(PublicContentService::event('therapists-circle-third')['ends_at']==='2026-10-16T19:00:00+03:30','End before start is rejected without losing the event date.');
$repository->setContentStatus($pdo->query("SELECT id FROM content_entities WHERE slug='therapists-circle-second'")->fetchColumn(),'archived');
$check(PublicContentService::event('therapists-circle-second')===null,'Admin archiving is respected even for historical seeded events.');
$check((new CourseRequestRepository())->userRequests($adminId)===[],'A logged-in user cannot read someone else\'s guest application.');
$schema=\App\Services\SeoService::eventSchema($third);
$check($schema['offers']['price']===6000000 && $schema['offers']['priceCurrency']==='IRR' && !isset($schema['location']),'Event schema converts toman to rials without inventing a venue.');
$check(\App\Services\SeoService::courseSchema($course)['offers']['price']===150000000,'Course schema uses correct currency conversion.');
$repository->saveContent(null,['entity_type'=>'program','slug'=>'regression-program','status'=>'published','sort_order'=>0,'translations'=>['fa'=>['title'=>'برنامه آزمایشی','subtitle'=>'','excerpt'=>'مسیر یادگیری','body'=>'توضیح برنامه','metadata'=>['line_slug'=>'therapist-development']]]],$adminId);
$check(count(PublicContentService::programs())===1 && PublicContentService::course($course['slug'])!==null,'Managed programs do not recursively re-enter academy line resolution.');
$founderId=$pdo->query("SELECT id FROM content_entities WHERE slug='maryam-haghani'")->fetchColumn();
$admin->workspaceUpdate(new Request(body:['fa_title'=>'دکتر مریم حقانی','fa_subtitle'=>'بنیان‌گذار منتوریس','fa_body'=>'معرفی مدیریت‌شده از دیتابیس','status'=>'published']),'mentors',$founderId);
$check(str_contains((new \App\Controllers\FounderController())->index(new Request())->content(),'معرفی مدیریت‌شده از دیتابیس'),'Founder page also reflects edits to its database profile.');
foreach(\App\Content\Autumn2026::entries() as $entry) $check(is_file(BASE_PATH.'/public/assets/'.$entry['metadata']['image']),'Every release image exists in the deployed assets.');
if(in_array('--preview',$argv,true)) foreach($pages as $name=>$html) file_put_contents(STORAGE_PATH.'/temp/autumn-preview-'.$name.'.html',$html);
session_write_close();
echo "PASS: {$checks} release integration checks; ".count($pages)." rendered views; no live services called.\n";

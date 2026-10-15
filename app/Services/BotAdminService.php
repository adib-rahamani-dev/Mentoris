<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Authorization,Database,Audit,Security};
use App\Repositories\{CommunicationRepository,SupportRepository,AdminRepository};
use RuntimeException;
final class BotAdminService
{
    public function __construct(private readonly CommunicationRepository $hub=new CommunicationRepository()) {}
    public function authorized(array $account,string $permission): ?array
    { $user=$this->hub->linkedUser($account);if(!$user || $account['link_method']!=='site' || !Authorization::can($user,$permission))return null;$user['__permission']=$permission;return $user; }
    public function execute(array $account,string $id): string
    {
        return Database::transaction($this->hub->pdo(),function() use($account,$id){
            $action=$this->hub->one('SELECT * FROM comm_actions WHERE id=:id AND channel=:channel AND chat_id=:chat',['id'=>$id,'channel'=>$account['channel'],'chat'=>$account['chat_id']]);
            if(!$action || $action['used_at']!==null || $action['expires_at']<=Database::now()) throw new RuntimeException('این تأیید منقضی شده یا قبلاً استفاده شده است.');
            $permissions=['payment_admission'=>'payments.manage','manual_payment'=>'payments.manage','engagement'=>'engagements.manage','reply'=>'engagements.manage','ticket_close'=>'engagements.manage','content_status'=>'content.manage','article_draft'=>'content.manage'];
            $permission=$permissions[$action['action']] ?? ''; $user=$this->authorized($account,$permission);
            if(!$user || $user['id']!==$action['user_id'] || (int)$user['auth_version']!==(int)$action['auth_version']) throw new RuntimeException('دسترسی این عملیات در حال حاضر مجاز نیست.');
            if($this->hub->execute('UPDATE comm_actions SET used_at=:now WHERE id=:id AND used_at IS NULL',['now'=>Database::now(),'id'=>$id])!==1) throw new RuntimeException('این عملیات قبلاً انجام شده است.');
            $data=CommunicationRepository::open($action['payload']);$subject=(string)($data['id'] ?? '');$before=[];$after=[];
            switch($action['action']) {
                case 'payment_admission':
                    if(!Authorization::can($user,'engagements.manage'))throw new RuntimeException('دسترسی پذیرش مجاز نیست.');
                    $payment=new ManualPaymentService($this->hub);$row=$payment->find($subject);if(!$row)throw new RuntimeException('رسید پیدا نشد.');
                    $payment->review($subject,(int)$data['version'],'approved',$user,'',false);
                    $table=$row['request_type']==='circle'?'event_signups':'course_requests';$admission=$this->hub->one("SELECT status FROM $table WHERE id=:id",['id'=>$row['request_id']]);
                    if(!$admission || !in_array($admission['status'],['requested','approved'],true))throw new RuntimeException('پذیرش این درخواست لغو یا رد شده است.');
                    if($admission['status']==='requested')(new AdminRepository($this->hub->pdo()))->updateEngagementStatus($row['request_type'],$row['request_id'],'approved');$after=['payment'=>'approved','admission'=>'approved'];break;
                case 'manual_payment':
                    (new ManualPaymentService($this->hub))->review($subject,(int)$data['version'],$data['status'],$user);$after=['status'=>$data['status']];break;
                case 'engagement':
                    (new PaymentLifecycleService($this->hub))->requirePayment((string)($data['type']??''),$subject,(string)($data['status']??''));
                    $tables=['circle'=>'event_signups','courses'=>'course_requests'];$table=$tables[$data['type'] ?? ''] ?? null;
                    if(!$table || !in_array($data['status'] ?? '',['approved','rejected'],true)) throw new RuntimeException('عملیات معتبر نیست.');
                    $before=$this->hub->one("SELECT * FROM $table WHERE id=:id",['id'=>$subject]);
                    if(!$before || $before['status']!==$data['previous']) throw new RuntimeException('وضعیت درخواست تغییر کرده؛ فهرست را دوباره باز کنید.');
                    if($this->hub->execute("UPDATE $table SET status=:status,updated_at=:now WHERE id=:id AND status=:previous",['status'=>$data['status'],'now'=>Database::now(),'id'=>$subject,'previous'=>$data['previous']])!==1) throw new RuntimeException('درخواست هم‌زمان تغییر کرد.');
                    $after=['status'=>$data['status']];(new CommunicationNoticeService($this->hub))->engagement($data['type'],$subject,$data['status']);break;
                case 'reply':
                    $support=new SupportRepository($this->hub);$ticket=$support->find($subject);
                    if(!$ticket || $ticket['status']==='closed') throw new RuntimeException('این گفتگو در دسترس نیست.');
                    $support->append($ticket,$user['id'],'staff',$data['text'],$data['attachment'] ?? null,'bot-reply:'.$id);$after=['status'=>'answered'];break;
                case 'ticket_close':
                    if($this->hub->execute("UPDATE support_tickets SET status='closed',updated_at=:now WHERE id=:id AND status<>'closed'",['now'=>Database::now(),'id'=>$subject])!==1) throw new RuntimeException('تیکت قبلاً بسته شده است.');$after=['status'=>'closed'];break;
                case 'content_status':
                    if(!in_array($data['status'],['published','archived'],true)) throw new RuntimeException('وضعیت محتوا معتبر نیست.');
                    $before=$this->hub->one('SELECT * FROM content_entities WHERE id=:id',['id'=>$subject]);
                    if(!$before || $before['status']!==$data['previous']) throw new RuntimeException('محتوا تغییر کرده؛ فهرست را تازه کنید.');
                    $translation=$this->hub->one("SELECT title,body FROM content_translations WHERE entity_id=:id AND locale='fa'",['id'=>$subject]);
                    if($data['status']==='published' && (!$translation || trim($translation['title'])==='' || trim($translation['body'])==='')) throw new RuntimeException('عنوان و متن فارسی را ابتدا تکمیل کنید.');
                    if($this->hub->execute('UPDATE content_entities SET status=:status,published_at=COALESCE(published_at,:published),updated_at=:now WHERE id=:id AND status=:previous',['status'=>$data['status'],'published'=>Database::now(),'now'=>Database::now(),'id'=>$subject,'previous'=>$data['previous']])!==1) throw new RuntimeException('محتوا هم‌زمان تغییر کرد.');$after=['status'=>$data['status']];break;
                case 'article_draft':
                    $entry=(new AdminRepository($this->hub->pdo()))->saveContent(null,['entity_type'=>'article','slug'=>'journal-'.gmdate('Ymd').'-'.Security::randomToken(4),'status'=>'draft','sort_order'=>0,'translations'=>['fa'=>['title'=>$data['title'],'subtitle'=>'','excerpt'=>mb_substr($data['text'],0,155),'body'=>$data['text'],'metadata'=>['author'=>$user['name'],'category'=>'رشد حرفه‌ای']]]],$user['id']);
                    $subject=$entry['id'];$after=['status'=>'draft'];break;
                default:throw new RuntimeException('عملیات در دسترس نیست.');
            }
            Audit::record('bot.'.$action['action'],$data['type'] ?? 'support',$subject,$user['id'],['status'=>$before['status'] ?? null],$after,'messenger:'.$account['channel'].':'.$account['chat_id']);
            return $action['action']==='article_draft'?'پیش‌نویس مقاله در سایت ذخیره شد؛ تصویر و سئو را از پنل تکمیل کنید.':'عملیات انجام شد و در سابقهٔ مدیریت ثبت شد.';
        });
    }
}

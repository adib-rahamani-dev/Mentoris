<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\{Database,Security,Authorization};
use RuntimeException;
final class SupportRepository
{
    public function __construct(public readonly CommunicationRepository $hub=new CommunicationRepository()) {}
    public function categories(): array { return ['general'=>'پرسش عمومی','registration'=>'ثبت‌نام و رویداد','certificate'=>'گواهی','mentoring'=>'منتورینگ','recordings'=>'ضبط جلسات','technical'=>'مشکل فنی']; }
    public static function dateLabel(string $utc): string
    {
        $date=new \DateTimeImmutable($utc,new \DateTimeZone('UTC'));
        if(class_exists(\IntlDateFormatter::class)) { $formatter=new \IntlDateFormatter('fa_IR@calendar=persian',\IntlDateFormatter::MEDIUM,\IntlDateFormatter::SHORT,'Asia/Tehran',\IntlDateFormatter::TRADITIONAL);return (string)$formatter->format($date); }
        return $date->setTimezone(new \DateTimeZone('Asia/Tehran'))->format('Y/m/d H:i');
    }
    public function find(string $id): ?array { return $this->hub->one('SELECT t.*,u.name user_name FROM support_tickets t LEFT JOIN users u ON u.id=t.user_id WHERE t.id=:id',['id'=>$id]); }
    public function readable(array $ticket,array $user,bool $admin=false): bool { return $admin ? Authorization::can($user,'engagements.view') : ($ticket['user_id']!==null && $ticket['user_id']===$user['id']); }
    public function list(?string $userId=null,array $filters=[]): array
    {
        $where=[];$parameters=[];
        if($userId!==null){$where[]='t.user_id=:user';$parameters['user']=$userId;}
        if(in_array($filters['status'] ?? '',['open','waiting','answered','closed'],true)){$where[]='t.status=:status';$parameters['status']=$filters['status'];}
        if(in_array($filters['channel'] ?? '',['site','telegram','bale'],true)){$where[]='t.channel=:channel';$parameters['channel']=$filters['channel'];}
        return $this->hub->rows('SELECT t.*,u.name user_name FROM support_tickets t LEFT JOIN users u ON u.id=t.user_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY t.updated_at DESC,t.id DESC LIMIT 100',$parameters);
    }
    public function create(?string $userId,string $channel,?string $chat,string $subject,string $category,string $body,?array $attachment=null,?string $sourceKey=null): array
    {
        if($sourceKey && ($existing=$this->hub->one('SELECT ticket_id FROM support_messages WHERE source_key=:key',['key'=>$sourceKey])))return $this->find($existing['ticket_id']);
        if(mb_strlen($subject)<3 || mb_strlen($subject)>160 || !isset($this->categories()[$category])) throw new RuntimeException('موضوع و دسته‌بندی معتبر انتخاب کنید.');
        $condition=$userId!==null?'user_id=:owner':'channel=:channel AND chat_id=:owner';$params=['owner'=>$userId ?? $chat,'since'=>gmdate('Y-m-d H:i:s',time()-86400)];if($userId===null)$params['channel']=$channel;
        if((int)($this->hub->one('SELECT COUNT(*) total FROM support_tickets WHERE '.$condition.' AND created_at>=:since',$params)['total'] ?? 0)>=10) throw new RuntimeException('درخواست‌های شما دریافت شده‌اند؛ برای تیکت جدید کمی صبر کنید.');
        return Database::transaction($this->hub->pdo(),function() use($userId,$channel,$chat,$subject,$category,$body,$attachment,$sourceKey){
            $id=Security::randomToken(16);$now=Database::now();
            $this->hub->execute("INSERT INTO support_tickets (id,user_id,channel,chat_id,subject,category,status,created_at,updated_at) VALUES (:id,:user,:channel,:chat,:subject,:category,'open',:created,:updated)",['id'=>$id,'user'=>$userId,'channel'=>$channel,'chat'=>$chat,'subject'=>$subject,'category'=>$category,'created'=>$now,'updated'=>$now]);
            $ticket=$this->find($id);$this->append($ticket,$userId,'member',$body,$attachment,$sourceKey);return $this->find($id);
        });
    }
    public function append(array $ticket,?string $author,string $role,string $body,?array $attachment=null,?string $sourceKey=null): string
    {
        if(!in_array($role,['member','staff'],true) || mb_strlen($body)>4000 || ($attachment===null && mb_strlen(trim($body))<2)) throw new RuntimeException('پیام باید بین ۲ و ۴۰۰۰ کاراکتر باشد، یا فایل داشته باشد.');
        if($ticket['status']==='closed') throw new RuntimeException('این تیکت بسته است؛ درخواست تازه ثبت کنید.');
        if($sourceKey && ($existing=$this->hub->one('SELECT id FROM support_messages WHERE source_key=:key',['key'=>$sourceKey]))) return $existing['id'];
        $sent=(int)($this->hub->one('SELECT COUNT(*) total FROM support_messages WHERE ticket_id=:ticket AND sender_role=:role AND created_at>=:since',['ticket'=>$ticket['id'],'role'=>$role,'since'=>gmdate('Y-m-d H:i:s',time()-3600)])['total'] ?? 0);
        if($sent>=30) throw new RuntimeException('پیام‌های شما دریافت شدند؛ برای ارسال پیام جدید کمی صبر کنید.');
        return Database::transaction($this->hub->pdo(),function() use($ticket,$author,$role,$body,$attachment,$sourceKey){
            $id=Security::randomToken(16);$now=Database::now();
            $this->hub->execute('INSERT INTO support_messages (id,ticket_id,author_id,sender_role,body,source_key,created_at) VALUES (:id,:ticket,:author,:role,:body,:source,:now)',['id'=>$id,'ticket'=>$ticket['id'],'author'=>$author,'role'=>$role,'body'=>CommunicationRepository::seal(['text'=>$body]),'source'=>$sourceKey,'now'=>$now]);
            if($attachment) $this->hub->execute('INSERT INTO support_attachments (id,message_id,channel,file_id,local_name,filename,mime_type,byte_size,media_kind,created_at) VALUES (:id,:message,:channel,:file,:local,:name,:mime,:size,:kind,:now)',['id'=>Security::randomToken(16),'message'=>$id,'channel'=>$attachment['channel'],'file'=>$attachment['file_id'],'local'=>$attachment['local_name'],'name'=>$attachment['filename'],'mime'=>$attachment['mime_type'],'size'=>$attachment['byte_size'],'kind'=>$attachment['media_kind'],'now'=>$now]);
            $this->hub->execute('UPDATE support_tickets SET status=:status,updated_at=:now WHERE id=:id',['status'=>$role==='staff'?'answered':'open','now'=>$now,'id'=>$ticket['id']]);
            if($role==='member'){
                $member=!empty($ticket['user_id'])?$this->hub->one('SELECT name,phone FROM users WHERE id=:id',['id'=>$ticket['user_id']]):null;
                $text='💬 پیام تازه در پشتیبانی'."\nموضوع: ".$ticket['subject']."\nعضو: ".($member['name']??$ticket['user_name']??'عضو پیام‌رسان')."\nموبایل: ".($member['phone']??'ثبت نشده')."\nدسته: ".($this->categories()[$ticket['category']]??$ticket['category'])."\nزمان: ".self::dateLabel($now)."\n\n".mb_strimwidth($body,0,2200,'…');
                if($attachment)$text.="\nپیوست: ".$attachment['filename'].' | '.number_format((int)$attachment['byte_size']).' بایت';
                $this->hub->notifyAdmins($text,'ticket-message:'.$id,'/admin/support/'.$ticket['id']);
            }
            else $this->deliverReply($ticket,$id,$body);
            return $id;
        });
    }
    private function deliverReply(array $ticket,string $id,string $body): void
    {
        if($ticket['user_id']) {
            $this->hub->notifyUser($ticket['user_id'],'پاسخ پشتیبانی منتوریس',mb_substr($body,0,3000)."\nمشاهده گفتگو: ".rtrim((string)env('APP_URL'),'/').'/support/'.$ticket['id'],'support-reply:'.$id);
            $this->hub->execute('INSERT INTO notifications (id,user_id,title,message,created_at) VALUES (:id,:user,:title,:message,:now)',['id'=>Security::randomToken(8),'user'=>$ticket['user_id'],'title'=>'پاسخ پشتیبانی منتوریس','message'=>'برای مشاهدهٔ پاسخ «'.$ticket['subject'].'» به مرکز پشتیبانی مراجعه کنید.','now'=>Database::now()]);
        }
        if(in_array($ticket['channel'],['telegram','bale'],true) && $ticket['chat_id']) {
            $account=$this->hub->account($ticket['channel'],$ticket['chat_id']);
            // A linked account already received the same reply through the shared notification route.
            if(!$ticket['user_id'] || !$this->hub->linkedUser($account)) $this->hub->message($account,'پاسخ تیم منتوریس به '.$ticket['subject']."\n\n".$body,['inline_keyboard'=>[[['text'=>'ادامهٔ گفتگو','callback_data'=>'t:'.$ticket['id']]]]],'support-reply:'.$id);
        }
        $files=$this->hub->rows('SELECT id FROM support_attachments WHERE message_id=:id',['id'=>$id]);
        if($files) {
            $destinations=$ticket['user_id']?$this->hub->rows('SELECT * FROM comm_accounts WHERE user_id=:id AND notifications_enabled=1',['id'=>$ticket['user_id']]):[];
            if(!$ticket['user_id'] && $ticket['chat_id'])$destinations=[$this->hub->account($ticket['channel'],$ticket['chat_id'])];
            foreach($destinations as $destination){$user=$this->hub->linkedUser($destination);if($ticket['user_id'] && !$user)continue;foreach($files as $file)$this->hub->queue($destination['channel'],$destination['chat_id'],'_attachment',['attachment_id'=>$file['id']],'support-file:'.$file['id'],$user);}
        }
    }
    public function messages(string $ticketId): array
    {
        $rows=$this->hub->rows('SELECT * FROM support_messages WHERE ticket_id=:ticket ORDER BY sequence_id DESC LIMIT 100',['ticket'=>$ticketId]);$rows=array_reverse($rows);
        foreach($rows as &$row){$row['body']=CommunicationRepository::open($row['body'])['text'];$row['attachments']=$this->hub->rows('SELECT id,filename,mime_type,byte_size,media_kind FROM support_attachments WHERE message_id=:id',['id'=>$row['id']]);}return $rows;
    }
    public function attachment(string $id): ?array {return $this->hub->one('SELECT a.*,m.ticket_id FROM support_attachments a JOIN support_messages m ON m.id=a.message_id WHERE a.id=:id',['id'=>$id]);}
}

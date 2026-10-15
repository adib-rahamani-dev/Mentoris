<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\{Database,Security,Crypto,Authorization,PhoneNumber};
use PDO;
use RuntimeException;

final class CommunicationRepository
{
    public function __construct(private readonly ?PDO $database=null) {}
    public function pdo(): PDO { return $this->database ?? Database::connection(); }
    public function one(string $sql,array $parameters=[]): ?array { $q=$this->pdo()->prepare($sql);$q->execute($parameters);return $q->fetch() ?: null; }
    public function rows(string $sql,array $parameters=[]): array { $q=$this->pdo()->prepare($sql);$q->execute($parameters);return $q->fetchAll() ?: []; }
    public function execute(string $sql,array $parameters=[]): int { $q=$this->pdo()->prepare($sql);$q->execute($parameters);return $q->rowCount(); }
    public static function seal(array $value): string { return base64_encode(Crypto::encrypt(json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'mentoris-communications-v1')); }
    public static function open(string $value): array { $plain=Crypto::decrypt((string)base64_decode($value,true),'mentoris-communications-v1');if($plain===null) throw new RuntimeException('Communication payload cannot be decrypted.');return json_decode($plain,true,512,JSON_THROW_ON_ERROR); }
    private function insertIgnore(): string { return Database::driver($this->pdo())==='mysql'?'INSERT IGNORE':'INSERT OR IGNORE'; }
    public function available(): bool
    {
        try { foreach(['comm_accounts','comm_links','comm_updates','comm_outbox','comm_actions','support_tickets','support_messages','support_attachments'] as $table) $this->pdo()->query('SELECT 1 FROM '.$table.' LIMIT 0');return true; }
        catch(\PDOException $e) { if(MemberProfileRepository::missingTable($e)) return false;throw $e; }
    }
    public function account(string $channel,string $chat,array $from=[]): array
    {
        if(!in_array($channel,['telegram','bale'],true) || !preg_match('/^[1-9][0-9]{0,19}$/',$chat)) throw new RuntimeException('Invalid private messenger account.');
        $now=Database::now();$name=mb_substr(trim((is_string($from['first_name'] ?? null)?$from['first_name']:'').' '.(is_string($from['last_name'] ?? null)?$from['last_name']:'')),0,120);
        $this->execute($this->insertIgnore()." INTO comm_accounts (channel,chat_id,display_name,state_data,created_at,updated_at) VALUES (:channel,:chat,:name,:data,:created,:updated)",['channel'=>$channel,'chat'=>$chat,'name'=>$name,'data'=>self::seal([]),'created'=>$now,'updated'=>$now]);
        $row=$this->one('SELECT * FROM comm_accounts WHERE channel=:channel AND chat_id=:chat',['channel'=>$channel,'chat'=>$chat]);$row['data']=self::open($row['state_data']);return $row;
    }
    public function state(array $account,string $state,array $data=[]): void
    { $this->execute('UPDATE comm_accounts SET state=:state,state_data=:data,updated_at=:now WHERE channel=:channel AND chat_id=:chat',['state'=>$state,'data'=>self::seal($data),'now'=>Database::now(),'channel'=>$account['channel'],'chat'=>$account['chat_id']]); }
    public function linkedUser(array $account): ?array
    {
        if(empty($account['user_id'])) return null;
        $user=$this->one('SELECT * FROM users WHERE id=:id AND status=:status',['id'=>$account['user_id'],'status'=>'active']);
        return $user && (int)$user['auth_version']===(int)$account['auth_version']?$user:null;
    }
    public function links(string $userId): array { return $this->rows('SELECT channel,chat_id,link_method,notifications_enabled,admin_alerts,updated_at FROM comm_accounts WHERE user_id=:id',['id'=>$userId]); }
    public function issueLink(array $user,string $channel): string
    {
        $user=$this->one('SELECT id,auth_version,status FROM users WHERE id=:id',['id'=>$user['id']]) ?? [];
        if(!in_array($channel,['telegram','bale'],true) || ($user['status'] ?? '')!=='active') throw new RuntimeException('اتصال در دسترس نیست.');
        $token=Security::randomToken(16);$now=Database::now();
        $this->execute('UPDATE comm_links SET used_at=:now WHERE user_id=:user AND channel=:channel AND used_at IS NULL',['now'=>$now,'user'=>$user['id'],'channel'=>$channel]);
        $this->execute('INSERT INTO comm_links (token_hash,user_id,channel,auth_version,expires_at,created_at) VALUES (:hash,:user,:channel,:version,:expiry,:now)',['hash'=>hash('sha256',$token),'user'=>$user['id'],'channel'=>$channel,'version'=>$user['auth_version'],'expiry'=>gmdate('Y-m-d H:i:s',time()+600),'now'=>$now]);return $token;
    }
    public function claimLink(array $account,string $token): bool
    {
        if(!preg_match('/^[a-f0-9]{32}$/',$token)) return false;
        return Database::transaction($this->pdo(),function() use($account,$token) {
            $hash=hash('sha256',$token);$link=$this->one('SELECT * FROM comm_links WHERE token_hash=:hash AND channel=:channel',['hash'=>$hash,'channel'=>$account['channel']]);
            if(!$link || $link['used_at']!==null || $link['expires_at']<=Database::now()) return false;
            $user=$this->one("SELECT * FROM users WHERE id=:id AND status='active'",['id'=>$link['user_id']]);
            if(!$user || (int)$user['auth_version']!==(int)$link['auth_version']) return false;
            $bound=$this->one('SELECT chat_id FROM comm_accounts WHERE channel=:channel AND user_id=:user',['channel'=>$account['channel'],'user'=>$user['id']]);
            if(($bound && $bound['chat_id']!==$account['chat_id']) || (!empty($account['user_id']) && $account['user_id']!==$user['id'])) return false;
            if($this->execute('UPDATE comm_links SET used_at=:now WHERE token_hash=:hash AND used_at IS NULL AND expires_at>:expiry',['now'=>Database::now(),'hash'=>$hash,'expiry'=>Database::now()])!==1) return false;
            $this->bind($account,$user,'site');return true;
        });
    }
    private function bind(array $account,array $user,string $method): void
    {
        $this->execute('UPDATE comm_accounts SET user_id=:user,auth_version=:version,link_method=:method,state=:state,state_data=:data,updated_at=:now WHERE channel=:channel AND chat_id=:chat',['user'=>$user['id'],'version'=>$user['auth_version'],'method'=>$method,'state'=>'','data'=>self::seal([]),'now'=>Database::now(),'channel'=>$account['channel'],'chat'=>$account['chat_id']]);
        $this->execute('UPDATE support_tickets SET user_id=:user WHERE channel=:channel AND chat_id=:chat AND user_id IS NULL',['user'=>$user['id'],'channel'=>$account['channel'],'chat'=>$account['chat_id']]);
    }
    public function contactMatch(array $account,array $contact): bool
    {
        if((string)($contact['user_id'] ?? '')!==$account['chat_id'] || !is_string($contact['phone_number'] ?? null)) return false;
        $phone=PhoneNumber::normalize($contact['phone_number']); if(!preg_match('/^09[0-9]{9}$/',$phone)) return false;
        $matched=(new UserRepository($this->pdo()))->findByLoginIdentifier($phone);
        if(!$matched || $matched['status']!=='active' || $matched['account_role']!=='student')return false;$users=[$matched];
        if(!empty($account['user_id']) && $account['user_id']!==$matched['id']) return false;
        $bound=$this->one('SELECT chat_id FROM comm_accounts WHERE channel=:channel AND user_id=:user',['channel'=>$account['channel'],'user'=>$users[0]['id']]);if($bound && $bound['chat_id']!==$account['chat_id']) return false;
        $this->bind($account,$users[0],'contact');return true;
    }
    public function disconnect(string $userId,string $channel): void
    {
        $this->execute('UPDATE comm_accounts SET user_id=NULL,auth_version=NULL,link_method=:empty,admin_alerts=0,state=:state,state_data=:data,updated_at=:now WHERE user_id=:user AND channel=:channel',['empty'=>'','state'=>'','data'=>self::seal([]),'now'=>Database::now(),'user'=>$userId,'channel'=>$channel]);
        $this->execute('UPDATE comm_links SET used_at=:now WHERE user_id=:user AND channel=:channel AND used_at IS NULL',['now'=>Database::now(),'user'=>$userId,'channel'=>$channel]);
    }
    public function enqueue(string $channel,array $update): bool
    { $now=Database::now();return $this->execute($this->insertIgnore()." INTO comm_updates (channel,update_id,payload,status,next_attempt_at,created_at,updated_at) VALUES (:channel,:id,:payload,'queued',:next,:created,:updated)",['channel'=>$channel,'id'=>$update['update_id'],'payload'=>self::seal($update),'next'=>$now,'created'=>$now,'updated'=>$now])===1; }
    public function queue(string $channel,string $chat,string $method,array $payload,?string $dedupe=null,?array $user=null): bool
    {
        $now=Database::now();$queued=$this->execute($this->insertIgnore()." INTO comm_outbox (id,channel,chat_id,method,payload,dedupe_key,user_id,auth_version,required_permission,status,next_attempt_at,created_at,updated_at) VALUES (:id,:channel,:chat,:method,:payload,:dedupe,:user,:version,:permission,'queued',:next,:created,:updated)",['id'=>Security::randomToken(16),'channel'=>$channel,'chat'=>$chat,'method'=>$method,'payload'=>self::seal($payload),'dedupe'=>$dedupe,'user'=>$user['id'] ?? null,'version'=>$user['auth_version'] ?? null,'permission'=>$user['__permission'] ?? '','next'=>$now,'created'=>$now,'updated'=>$now])===1;
        if($queued)\App\Controllers\CommunicationController::afterWebhook();return $queued;
    }
    public function message(array $account,string $text,?array $markup=null,?string $dedupe=null,?array $user=null): void
    { $payload=['chat_id'=>$account['chat_id'],'text'=>mb_substr($text,0,3900)]; if($markup) $payload['reply_markup']=$markup;$this->queue($account['channel'],$account['chat_id'],'sendMessage',$payload,$dedupe,$user); }
    public function notifyUser(string $userId,string $title,string $message,string $key): void
    {
        foreach($this->rows('SELECT * FROM comm_accounts WHERE user_id=:user AND notifications_enabled=1',['user'=>$userId]) as $account) {
            $user=$this->linkedUser($account);if(!$user) continue;
            $this->message($account,$title."\n\n".$message,null,$key,$user);
        }
    }
    public function notifyAdmins(string $text,string $key,?string $path=null): void
    {
        foreach($this->rows('SELECT * FROM comm_accounts WHERE admin_alerts=1 AND notifications_enabled=1') as $account) {
            $user=$this->linkedUser($account);if(!$user || $account['link_method']!=='site' || !Authorization::can($user,'engagements.view')) continue;
            $button=$path && preg_match('~^/admin/(?:requests/(?:circle|courses)|support)/[a-f0-9]+$~D',$path)?['text'=>str_starts_with($path,'/admin/support/')?'مشاهده گفتگو و فایل‌ها':'مشاهده اطلاعات و بررسی درخواست','url'=>rtrim((string)env('APP_URL'),'/').$path]:['text'=>'مرکز مدیریت','callback_data'=>'admin'];
            $user['__permission']='engagements.view';$this->message($account,$text,['inline_keyboard'=>[[$button]]],$key,$user);
        }
    }
    public function prepareAction(array $account,array $user,string $action,array $payload): string
    {
        $id=Security::randomToken(16);$this->execute('INSERT INTO comm_actions (id,channel,chat_id,user_id,auth_version,action,payload,expires_at,created_at) VALUES (:id,:channel,:chat,:user,:version,:action,:payload,:expiry,:now)',['id'=>$id,'channel'=>$account['channel'],'chat'=>$account['chat_id'],'user'=>$user['id'],'version'=>$user['auth_version'],'action'=>$action,'payload'=>self::seal($payload),'expiry'=>gmdate('Y-m-d H:i:s',time()+300),'now'=>Database::now()]);return $id;
    }
    public function pendingCounts(): array
    {
        $result=[];foreach(['tickets'=>"SELECT COUNT(*) total FROM support_tickets WHERE status IN ('open','waiting')",'circle'=>"SELECT COUNT(*) total FROM event_signups WHERE status='requested' AND EXISTS (SELECT 1 FROM manual_payments p WHERE p.request_type='circle' AND p.request_id=event_signups.id AND p.part=1 AND p.status='approved')",'courses'=>"SELECT COUNT(*) total FROM course_requests WHERE status='requested' AND EXISTS (SELECT 1 FROM manual_payments p WHERE p.request_type='courses' AND p.request_id=course_requests.id AND p.part=1 AND p.status='approved')",'receipts'=>"SELECT COUNT(*) total FROM manual_payments WHERE status='pending'",'refunds'=>"SELECT COUNT(*) total FROM payment_refunds WHERE status='pending'",'users'=>'SELECT COUNT(*) total FROM users','failed'=>"SELECT COUNT(*) total FROM comm_outbox WHERE status='failed'"] as $key=>$sql) {try {$result[$key]=(int)($this->one($sql)['total'] ?? 0);}catch(\PDOException $e){if(!MemberProfileRepository::missingTable($e)) throw $e;$result[$key]=0;}}return $result;
    }
}

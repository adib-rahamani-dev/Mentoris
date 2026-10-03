<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\Database;
use App\Core\Security;
use PDO;

final class TelegramRepository
{
    public function __construct(private readonly ?PDO $database=null) {}
    public function pdo(): PDO { return $this->database ?? Database::connection(); }
    public function available(): bool
    {
        try { foreach(['telegram_users','telegram_updates','telegram_outbox','telegram_questions','telegram_event_requests'] as $table) $this->pdo()->query('SELECT 1 FROM '.$table.' LIMIT 0'); return true; }
        catch(\PDOException $e) { if(!MemberProfileRepository::missingTable($e)) throw $e; return false; }
    }
    public function enqueueUpdate(array $update): void
    {
        $prefix=Database::driver($this->pdo())==='mysql' ? 'INSERT IGNORE' : 'INSERT OR IGNORE'; $now=Database::now();
        $this->pdo()->prepare($prefix.' INTO telegram_updates (update_id,payload,status,attempts,next_attempt_at,created_at,updated_at) VALUES (:id,:payload,\'queued\',0,:next,:created,:updated)')->execute(['id'=>$update['update_id'],'payload'=>json_encode($update,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'next'=>$now,'created'=>$now,'updated'=>$now]);
    }
    public function user(string $chatId,array $from): array
    {
        $prefix=Database::driver($this->pdo())==='mysql' ? 'INSERT IGNORE' : 'INSERT OR IGNORE'; $now=Database::now();
        $this->pdo()->prepare($prefix.' INTO telegram_users (chat_id,display_name,username,state,state_data,created_at,updated_at) VALUES (:id,:name,:username,\'\',\'{}\',:created,:updated)')->execute(['id'=>$chatId,'name'=>mb_substr(trim(($from['first_name'] ?? '').' '.($from['last_name'] ?? '')),0,120),'username'=>mb_substr((string)($from['username'] ?? ''),0,64),'created'=>$now,'updated'=>$now]);
        $s=$this->pdo()->prepare('SELECT * FROM telegram_users WHERE chat_id=:id'); $s->execute(['id'=>$chatId]); $row=$s->fetch(); $row['data']=json_decode($row['state_data'],true) ?: []; return $row;
    }
    public function state(string $chatId,string $state,array $data=[]): void
    {
        $this->pdo()->prepare('UPDATE telegram_users SET state=:state,state_data=:data,updated_at=:now WHERE chat_id=:id')->execute(['state'=>$state,'data'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'now'=>Database::now(),'id'=>$chatId]);
    }
    public function queue(string $method,array $payload): void
    {
        $now=Database::now();
        $this->pdo()->prepare('INSERT INTO telegram_outbox (id,method,payload,status,attempts,next_attempt_at,created_at,updated_at) VALUES (:id,:method,:payload,\'queued\',0,:next,:created,:updated)')->execute(['id'=>Security::randomToken(16),'method'=>$method,'payload'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'next'=>$now,'created'=>$now,'updated'=>$now]);
    }
    public function message(string $chatId,string $text,?array $markup=null): void
    {
        $payload=['chat_id'=>$chatId,'text'=>mb_substr($text,0,4000),'disable_web_page_preview'=>true]; if($markup) $payload['reply_markup']=$markup;
        $this->queue('sendMessage',$payload);
    }
    public function question(string $chatId,int $updateId,string $text): void
    {
        $now=Database::now(); $this->pdo()->prepare('INSERT INTO telegram_questions (id,chat_id,update_id,question,answer,status,created_at,updated_at) VALUES (:id,:chat,:update,:question,\'\',\'new\',:created,:updated)')->execute(['id'=>Security::randomToken(16),'chat'=>$chatId,'update'=>$updateId,'question'=>$text,'created'=>$now,'updated'=>$now]);
    }
    public function eventRequest(string $chatId,string $slug,array $data): bool
    {
        $prefix=Database::driver($this->pdo())==='mysql' ? 'INSERT IGNORE' : 'INSERT OR IGNORE'; $now=Database::now();
        $s=$this->pdo()->prepare($prefix.' INTO telegram_event_requests (id,chat_id,event_slug,name,phone,city,status,created_at,updated_at) VALUES (:id,:chat,:slug,:name,:phone,:city,\'requested\',:created,:updated)');
        $s->execute(['id'=>Security::randomToken(16),'chat'=>$chatId,'slug'=>$slug,'name'=>$data['name'],'phone'=>$data['phone'],'city'=>$data['city'],'created'=>$now,'updated'=>$now]); return $s->rowCount()===1;
    }
    public function dashboard(array $filters=[]): array
    {
        if(!$this->available()) return ['ready'=>false,'stats'=>['users'=>0,'questions'=>0,'requests'=>0,'pending'=>0,'failed'=>0],'questions'=>[],'requests'=>[]];
        $stats=[]; foreach(['users'=>'telegram_users','questions'=>'telegram_questions','requests'=>'telegram_event_requests'] as $key=>$table) $stats[$key]=(int)$this->pdo()->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
        $stats['pending']=(int)$this->pdo()->query("SELECT COUNT(*) FROM telegram_questions WHERE status='new'")->fetchColumn(); $stats['failed']=(int)$this->pdo()->query("SELECT COUNT(*) FROM telegram_outbox WHERE status='failed'")->fetchColumn()+(int)$this->pdo()->query("SELECT COUNT(*) FROM telegram_updates WHERE status='failed'")->fetchColumn();
        $where=[]; $params=[];
        if(in_array($filters['status'] ?? '',['new','answered','closed'],true)) { $where[]='q.status=:status'; $params['status']=$filters['status']; }
        if(is_string($filters['q'] ?? null) && $filters['q']!=='') { $where[]='(q.question LIKE :q1 OR u.display_name LIKE :q2)'; $params['q1']=$params['q2']='%'.mb_substr($filters['q'],0,120).'%'; }
        $s=$this->pdo()->prepare('SELECT q.*,u.display_name,u.username FROM telegram_questions q JOIN telegram_users u ON u.chat_id=q.chat_id'.($where ? ' WHERE '.implode(' AND ',$where) : '').' ORDER BY q.created_at DESC LIMIT 100'); $s->execute($params);
        $requests=$this->pdo()->query('SELECT r.*,u.username FROM telegram_event_requests r JOIN telegram_users u ON u.chat_id=r.chat_id ORDER BY r.created_at DESC LIMIT 100')->fetchAll();
        return ['ready'=>true,'stats'=>$stats,'questions'=>$s->fetchAll(),'requests'=>$requests];
    }
    public function answer(string $id,string $answer): bool
    {
        return Database::transaction($this->pdo(),function() use ($id,$answer): bool {
            $suffix=Database::driver($this->pdo())==='mysql' ? ' FOR UPDATE' : '';
            $s=$this->pdo()->prepare('SELECT * FROM telegram_questions WHERE id=:id'.$suffix); $s->execute(['id'=>$id]); $question=$s->fetch();
            if(!$question || $question['status']!=='new') return false;
            $this->pdo()->prepare("UPDATE telegram_questions SET answer=:answer,status='answered',updated_at=:now WHERE id=:id")->execute(['answer'=>$answer,'now'=>Database::now(),'id'=>$id]);
            $this->message((string)$question['chat_id'],"پاسخ تیم منتوریس به پرسش شما:\n\n".$answer); return true;
        });
    }
}

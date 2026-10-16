<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,Session,Security};
use App\Repositories\MemberProfileRepository;
final class SurveyResponseService
{
    public function available(): bool
    {
        try{Database::connection()->query('SELECT id FROM survey_responses LIMIT 0');return true;}
        catch(\PDOException $e){if(MemberProfileRepository::missingTable($e))return false;throw $e;}
    }
    private function key(string $event): string
    {
        $session=new Session();$key=$session->get('survey.key.'.$event);
        if(!is_string($key) || !preg_match('/^[a-f0-9]{32}$/',$key)){$key=Security::randomToken(16);$session->put('survey.key.'.$event,$key);}
        return hash('sha256',$event.'|'.$key);
    }
    public function current(string $event): ?array
    {
        if(!$this->available())return null;
        $s=Database::connection()->prepare('SELECT id,identity_mode,content_rating,hosting_rating FROM survey_responses WHERE event_slug=:event AND response_key=:key');$s->execute(['event'=>$event,'key'=>$this->key($event)]);return $s->fetch()?:null;
    }
    public function save(string $event,array $data,?array $user): void
    {
        if(!$this->available())throw new \RuntimeException('برای فعال‌شدن بازخورد جدید، SQL شماره ۰۱۵ را اجرا کنید.');
        if(!in_array($event,SurveyWindowService::EVENTS,true))throw new \RuntimeException('برنامه معتبر نیست.');
        $mode=$data['identity_mode']??'';
        if(!in_array($mode,['anonymous','named'],true))throw new \RuntimeException('شیوه ثبت بازخورد را انتخاب کنید.');
        $rawName=$user['name']??$data['display_name']??'';
        if($mode==='named' && !is_string($rawName))throw new \RuntimeException('نام معتبر وارد کنید.');
        $name=$mode==='named'?trim($rawName):'';
        if($mode==='named' && (mb_strlen($name)<2 || mb_strlen($name)>120))throw new \RuntimeException('نام خود را بین ۲ تا ۱۲۰ حرف وارد کنید.');
        foreach(['content_rating','hosting_rating'] as $field)if(!is_scalar($data[$field]??null) || !in_array((string)$data[$field],['1','2','3','4','5'],true))throw new \RuntimeException('هر دو امتیاز را از ۱ تا ۵ انتخاب کنید.');
        if(!in_array($data['challenge']??'none',['none','burnout','technique','countertransference','supervision'],true) || !is_string($data['comment']??'') || mb_strlen($data['comment']??'')>1000)throw new \RuntimeException('گزینه چالش یا متن نظر معتبر نیست.');
        $insert=Database::driver(Database::connection())==='mysql'?'INSERT IGNORE':'INSERT OR IGNORE';
        $s=Database::connection()->prepare("$insert INTO survey_responses (id,event_slug,response_key,identity_mode,display_name,user_id,content_rating,hosting_rating,challenge,comment,created_at) VALUES (:id,:event,:key,:mode,:name,:user,:content,:hosting,:challenge,:comment,:at)");
        $s->execute(['id'=>Security::randomToken(16),'event'=>$event,'key'=>$this->key($event),'mode'=>$mode,'name'=>$name,'user'=>$mode==='named'?($user['id']??null):null,'content'=>$data['content_rating'],'hosting'=>$data['hosting_rating'],'challenge'=>$data['challenge']??'none','comment'=>$data['comment']??'','at'=>Database::now()]);
    }
}

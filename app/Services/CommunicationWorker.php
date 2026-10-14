<?php
declare(strict_types=1);
namespace App\Services;
use App\Repositories\CommunicationRepository;
use App\Core\{Database,Authorization};
final class CommunicationWorker
{
    public function run(int $limit=30,int $budgetSeconds=45): array
    {
        $hub=new CommunicationRepository();if(!$hub->available())throw new \RuntimeException('Install migration 010 before starting communications.');
        $lock=fopen(BASE_PATH.'/storage/data/communications-worker.lock','c');
        if(!$lock || !flock($lock,LOCK_EX|LOCK_NB))return ['updates'=>0,'sent'=>0,'failed'=>0,'skipped'=>0];
        $start=microtime(true);$result=['updates'=>0,'sent'=>0,'failed'=>0,'skipped'=>0];$limit=max(1,min(100,$limit));
        try {
            foreach(['comm_updates','comm_outbox'] as $table) {
                $hub->execute("UPDATE $table SET status='queued' WHERE status='processing' AND updated_at<:expiry",['expiry'=>gmdate('Y-m-d H:i:s',time()-600)]);
                $jobs=$hub->rows("SELECT * FROM $table WHERE status='queued' AND next_attempt_at<=:now ORDER BY created_at LIMIT $limit",['now'=>Database::now()]);
                foreach($jobs as $job) {
                    if(microtime(true)-$start>max(5,$budgetSeconds)) break;
                    $where=$table==='comm_updates'?'channel=:channel AND update_id=:id':'id=:id';$params=['id'=>$table==='comm_updates'?$job['update_id']:$job['id']];if($table==='comm_updates')$params['channel']=$job['channel'];
                    if($table==='comm_outbox' && !(new MessengerApi($job['channel']))->configured()) continue;
                    if($hub->execute("UPDATE $table SET status='processing',updated_at=:now WHERE $where AND status='queued'",['now'=>Database::now(),...$params])!==1)continue;
                    try {
                        $payload=CommunicationRepository::open($job['payload']);
                        if($table==='comm_updates') Database::transaction($hub->pdo(),function() use($hub,$job,$payload,$table,$where,$params){
                            (new CommunicationBotService($hub,$job['channel']))->handle($payload);
                            $hub->execute("UPDATE $table SET status='done',payload=:empty,updated_at=:now WHERE $where",['empty'=>CommunicationRepository::seal([]),'now'=>Database::now(),...$params]);
                        });
                        else {
                            if(!$this->deliveryAllowed($hub,$job)) {$hub->execute("UPDATE comm_outbox SET status='canceled',payload=:empty,updated_at=:now WHERE id=:id",['empty'=>CommunicationRepository::seal([]),'now'=>Database::now(),'id'=>$job['id']]);$result['skipped']++;continue;}
                            $api=new MessengerApi($job['channel']);
                            if($job['method']==='_manual_receipt') {
                                $payments=new ManualPaymentService($hub);$payment=$payments->find($payload['payment_id']);
                                if(!$payment)throw new \RuntimeException('Receipt not found.');
                                if((int)$payment['version']!==(int)$payload['version']){$hub->execute("UPDATE comm_outbox SET status='canceled',payload=:empty,updated_at=:now WHERE id=:id",['empty'=>CommunicationRepository::seal([]),'now'=>Database::now(),'id'=>$job['id']]);$result['skipped']++;continue;}
                                $api->sendAttachment($payments->receipt($payment),$job['chat_id']);
                            }
                            elseif($job['method']==='_attachment')$api->sendAttachment((new \App\Repositories\SupportRepository($hub))->attachment($payload['attachment_id']),$job['chat_id']);
                            else $api->call($job['method'],$payload);
                            $hub->execute("UPDATE comm_outbox SET status='sent',payload=:empty,updated_at=:now WHERE id=:id",['empty'=>CommunicationRepository::seal([]),'now'=>Database::now(),'id'=>$job['id']]);
                        }
                        $result[$table==='comm_updates'?'updates':'sent']++;
                    }catch(\Throwable $e) {
                        $attempts=(int)$job['attempts']+1;$permanent=$e instanceof MessengerException && in_array($e->getCode(),[400,401,403,404],true);
                        $delay=$e instanceof MessengerException && $e->getCode()===429?$e->retryAfter:min(3600,30*(2**min(8,$attempts)));
                        $hub->execute("UPDATE $table SET status=:status,attempts=:attempts,next_attempt_at=:next,updated_at=:now WHERE $where",['status'=>$permanent || $attempts>=5?'failed':'queued','attempts'=>$attempts,'next'=>gmdate('Y-m-d H:i:s',time()+$delay),'now'=>Database::now(),...$params]);$result['failed']++;
                    }
                    if($table==='comm_outbox') usleep(100000);
                }
            }
        }finally {flock($lock,LOCK_UN);fclose($lock);}
        file_put_contents(BASE_PATH.'/storage/data/communications-worker-status.json',json_encode(['at'=>Database::now(),'result'=>$result],JSON_THROW_ON_ERROR),LOCK_EX);return $result;
    }
    public function deliveryAllowed(CommunicationRepository $hub,array $job): bool
    {
        if(empty($job['user_id']))return true;
        $account=$hub->one('SELECT * FROM comm_accounts WHERE channel=:channel AND chat_id=:chat',['channel'=>$job['channel'],'chat'=>$job['chat_id']]);if(!$account || $account['user_id']!==$job['user_id'])return false;
        $user=$hub->linkedUser($account);if(!$user || (int)$user['auth_version']!==(int)$job['auth_version'])return false;
        if(($job['dedupe_key'] ?? '')!=='' && !$account['notifications_enabled'])return false;
        if(str_starts_with((string)($job['dedupe_key']??''),'manual:') && empty($account['admin_alerts']))return false;
        return empty($job['required_permission']) || ($account['link_method']==='site' && Authorization::can($user,$job['required_permission']));
    }
}

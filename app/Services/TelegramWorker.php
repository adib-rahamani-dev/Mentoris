<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Repositories\TelegramRepository;

final class TelegramWorker
{
    public function run(int $limit=30): array
    {
        $repo=new TelegramRepository(); if(!$repo->available()) throw new \RuntimeException('Run migration 005 before starting the Telegram worker.');
        $lock=fopen(BASE_PATH.'/storage/data/telegram-worker.lock','c');
        if(!$lock || !flock($lock,LOCK_EX|LOCK_NB)) return ['updates'=>0,'sent'=>0,'failed'=>0];
        $stats=['updates'=>0,'sent'=>0,'failed'=>0]; $pdo=$repo->pdo(); $limit=max(1,min($limit,100));
        try {
            foreach(['telegram_updates','telegram_outbox'] as $table) {
                $idColumn=$table==='telegram_updates' ? 'update_id' : 'id';
                $s=$pdo->prepare("SELECT * FROM $table WHERE status='queued' AND next_attempt_at<=:now ORDER BY created_at,$idColumn LIMIT $limit"); $s->execute(['now'=>Database::now()]);
                foreach($s->fetchAll() as $job) {
                    try {
                        $payload=json_decode($job['payload'],true,512,JSON_THROW_ON_ERROR);
                        if($table==='telegram_updates') Database::transaction($pdo,function() use ($pdo,$job,$payload): void {
                            (new TelegramBotService(new TelegramRepository($pdo)))->handle($payload);
                            $pdo->prepare("UPDATE telegram_updates SET status='done',updated_at=:now WHERE update_id=:id")->execute(['now'=>Database::now(),'id'=>$job['update_id']]);
                        });
                        else {
                            (new TelegramApi())->call($job['method'],$payload);
                            $pdo->prepare("UPDATE telegram_outbox SET status='sent',updated_at=:now WHERE id=:id")->execute(['now'=>Database::now(),'id'=>$job['id']]);
                        }
                        $stats[$table==='telegram_updates' ? 'updates' : 'sent']++;
                    } catch(\Throwable $e) {
                        $attempts=(int)$job['attempts']+1;
                        $pdo->prepare("UPDATE $table SET status=:status,attempts=:attempts,next_attempt_at=:next,updated_at=:now WHERE $idColumn=:id")->execute(['status'=>$attempts>=5 ? 'failed' : 'queued','attempts'=>$attempts,'next'=>gmdate('Y-m-d H:i:s',time()+min(3600,30*(2**$attempts))),'now'=>Database::now(),'id'=>$job[$idColumn]]); $stats['failed']++;
                    }
                }
            }
        } finally { flock($lock,LOCK_UN); fclose($lock); }
        file_put_contents(BASE_PATH.'/storage/data/telegram-worker-status.json',json_encode(['at'=>gmdate('Y-m-d H:i:s'),'result'=>$stats],JSON_THROW_ON_ERROR),LOCK_EX);
        return $stats;
    }
}

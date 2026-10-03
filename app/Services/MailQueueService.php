<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\PrivateRecords;
use App\Core\Security;

final class MailQueueService
{
    public function enqueue(string $to,string $subject,string $message): void
    {
        PrivateRecords::write('mail-outbox',Security::randomToken(16),['to'=>$to,'subject'=>$subject,'message'=>$message,'attempts'=>0,'created'=>time(),'next'=>time()]);
    }
    public function run(int $limit=20): array
    {
        $sent=0; $failed=0;
        $lock=fopen(BASE_PATH.'/storage/data/mail-worker.lock','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) return ['sent'=>0,'failed'=>0];
        try {
            $processed=0;
            foreach(PrivateRecords::files('mail-outbox') as $path) {
                $id=basename($path,'.sealed');
                try { $job=PrivateRecords::read('mail-outbox',$id); }
                catch(\Throwable) { $failed++; error_log('A queued email could not be decrypted. Keep APP_KEY unchanged and inspect the private queue.'); continue; }
                if (!$job || $job['next']>time() || $job['attempts']>=5) continue;
                if($processed++>=max(1,min($limit,100))) break;
                if ((new EmailService())->send($job['to'],$job['subject'],$job['message'])) { PrivateRecords::delete('mail-outbox',$id); $sent++; }
                else { $job['attempts']++; $job['next']=time()+min(3600,60*(2**$job['attempts'])); PrivateRecords::write('mail-outbox',$id,$job); $failed++; }
            }
        } finally { flock($lock,LOCK_UN); fclose($lock); }
        $result=compact('sent','failed');
        file_put_contents(BASE_PATH.'/storage/data/mail-worker-status.json',json_encode(['at'=>gmdate('Y-m-d H:i:s'),'result'=>$result],JSON_THROW_ON_ERROR),LOCK_EX);
        return $result;
    }
}

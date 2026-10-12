<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/bootstrap/constants.php';
require BASE_PATH.'/bootstrap/autoload.php';
$envFile=BASE_PATH.'/.env';
foreach (is_file($envFile)?file($envFile,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES):[] as $line) {
    $line=trim($line);if($line==='' || str_starts_with($line,'#') || !str_contains($line,'='))continue;
    [$key,$value]=array_map('trim',explode('=',$line,2));if(getenv($key)===false)putenv($key.'='.trim($value,"\"'"));
}
$state=['at'=>gmdate('c'),'ok'=>false];
try {
    $hub=new \App\Repositories\CommunicationRepository();
    if(!$hub->available())throw new RuntimeException('Install communications SQL first.');
    $api=new \App\Services\MessengerApi('telegram');
    $marker=BASE_PATH.'/storage/data/telegram-webhook-configured';
    if(!is_file($marker)) {
        $base=rtrim((string)env('APP_URL',''),'/');
        if(!preg_match('~^https://[^/]+~',$base))throw new RuntimeException('Public HTTPS APP_URL required.');
        $secret=trim((string)env('TELEGRAM_WEBHOOK_SECRET',''));
        if($secret==='') {
            $secret=bin2hex(random_bytes(32));$contents=(string)file_get_contents($envFile);
            $contents=preg_match('/^TELEGRAM_WEBHOOK_SECRET=.*$/m',$contents)?preg_replace('/^TELEGRAM_WEBHOOK_SECRET=.*$/m','TELEGRAM_WEBHOOK_SECRET='.$secret,$contents):rtrim($contents).PHP_EOL.'TELEGRAM_WEBHOOK_SECRET='.$secret.PHP_EOL;
            if(file_put_contents($envFile.'.tmp',$contents,LOCK_EX)===false || !rename($envFile.'.tmp',$envFile))throw new RuntimeException('Private configuration not writable.');
            chmod($envFile,0600);putenv('TELEGRAM_WEBHOOK_SECRET='.$secret);
        }
        if(!preg_match('/^[A-Za-z0-9_-]{16,256}$/',$secret))throw new RuntimeException('Invalid webhook secret.');
        $bot=$api->call('getMe');
        $api->call('setWebhook',['url'=>$base.'/api/telegram/webhook','secret_token'=>$secret,'allowed_updates'=>['message','callback_query'],'max_connections'=>5]);
        file_put_contents($marker,(string)($bot['username']??''),LOCK_EX);
    }
    $hook=$api->call('getWebhookInfo');
    $state['webhook']=['host'=>parse_url((string)($hook['url']??''),PHP_URL_HOST),'path'=>parse_url((string)($hook['url']??''),PHP_URL_PATH),'pending'=>(int)($hook['pending_update_count']??0),'last_error'=>mb_substr((string)($hook['last_error_message']??''),0,300),'last_error_at'=>$hook['last_error_date']??null];
    $state['reminders']=(new \App\Services\CommunicationNoticeService($hub))->reminders();
    $state['worker']=(new \App\Services\CommunicationWorker())->run();
    $state['ok']=true;
} catch (Throwable $error) {
    // Do not persist exception text: transport errors may include credential URLs.
    $state['error']='Check database, private bot token, APP_URL and outbound HTTPS access.';
    $state['error_type']=get_class($error);
}
file_put_contents(BASE_PATH.'/storage/data/communications-cron-status.json',json_encode($state,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX);
exit($state['ok']?0:1);

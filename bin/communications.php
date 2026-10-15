<?php
// Loaded by bin/console after private environment loading. Never prints credentials.
$channel=$argv[2] ?? 'telegram';if(!in_array($channel,['telegram','bale'],true))throw new RuntimeException('Use telegram or bale.');
$hub=new \App\Repositories\CommunicationRepository();
if($command==='communications:secret') {
 $key=strtoupper($channel).'_WEBHOOK_SECRET';$contents=is_file($envFile)?(string)file_get_contents($envFile):'';
 if(trim((string)env($key,''))!==''){echo "Private webhook secret already configured.\n";exit(0);}
 $newValue=bin2hex(random_bytes(32));$contents=preg_match('/^'.$key.'=.*$/m',$contents)?preg_replace('/^'.$key.'=.*$/m',$key.'='.$newValue,$contents):rtrim($contents).PHP_EOL.$key.'='.$newValue.PHP_EOL;
 $temporary=$envFile.'.tmp';if(file_put_contents($temporary,$contents,LOCK_EX)===false || !rename($temporary,$envFile))throw new RuntimeException('Could not save private configuration.');@chmod($envFile,0600);echo "Private webhook secret configured.\n";exit(0);
}
if($command==='communications:work') {
 if(!$hub->available())throw new RuntimeException('Install SQL migration 010 first.');
 $legacy=new \App\Repositories\TelegramRepository();if($legacy->available())(new \App\Services\TelegramWorker())->run(10);
 $reminders=(new \App\Services\CommunicationNoticeService($hub))->reminders();$result=(new \App\Services\CommunicationWorker())->run();echo json_encode(['reminders'=>$reminders,...$result],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}
$api=new \App\Services\MessengerApi($channel);
if($command==='communications:check') {
 $bot=$api->call('getMe');$hook=$api->call('getWebhookInfo');$url=(string)($hook['url'] ?? '');
 echo 'Bot: @'.($bot['username'] ?? 'unknown').PHP_EOL;
 echo 'Webhook configured: '.($url!==''?'yes':'no').PHP_EOL;
 echo 'Webhook host: '.(parse_url($url,PHP_URL_HOST) ?: 'none').PHP_EOL;
 echo 'Database hub: '.($hub->available()?'ready':'migration required').PHP_EOL;
 echo 'Pending updates: '.(int)($hook['pending_update_count'] ?? 0).PHP_EOL;exit(0);
}
if($command==='communications:setup') {
 if(!$hub->available())throw new RuntimeException('Install SQL migration 010 first.');
 $base=rtrim((string)env('APP_URL',''),'/');if(!preg_match('~^https://[^/]+~',$base))throw new RuntimeException('A public HTTPS APP_URL is required.');
 $secret=trim((string)env(strtoupper($channel).'_WEBHOOK_SECRET',''));$pattern=$channel==='telegram'?'/^[A-Za-z0-9_-]{16,256}$/':'/^[a-f0-9]{64}$/';if(!preg_match($pattern,$secret))throw new RuntimeException('Run communications:secret for this channel first.');
 $api->call('getMe');
 if($channel==='telegram') {
  $api->call('setMyCommands',['commands'=>[['command'=>'start','description'=>'منوی اصلی منتوریس'],['command'=>'menu','description'=>'رویدادها، دوره‌ها و پشتیبانی'],['command'=>'account','description'=>'حساب متصل من'],['command'=>'orders','description'=>'ثبت‌نام‌ها، پرداخت‌ها و بازگشت وجه'],['command'=>'notifications','description'=>'تنظیم اعلان‌های من'],['command'=>'admin','description'=>'مدیریت سایت برای مدیر متصل'],['command'=>'stats','description'=>'آمار برای مدیر متصل'],['command'=>'cancel','description'=>'لغو و برگشت'],['command'=>'help','description'=>'راهنمای استفاده']]]);
  $api->call('setMyDescription',['description'=>'همراه منتوریس برای درمانگران و دانشجویان؛ رویدادها، دوره‌ها، پشتیبانی انسانی و اعلان‌های اختصاصی. مدیریت سایت برای مدیران متصل.']);
  $api->call('setWebhook',['url'=>$base.'/api/telegram/webhook','secret_token'=>$secret,'allowed_updates'=>['message','callback_query'],'max_connections'=>5]);
 }else $api->call('setWebhook',['url'=>$base.'/api/bale/webhook/'.$secret]);
 echo "Webhook configured. Schedule communications:work every minute; check it from the host.\n";exit(0);
}
throw new RuntimeException('Unknown communications command.');

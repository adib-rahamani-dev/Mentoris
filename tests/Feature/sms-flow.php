<?php
declare(strict_types=1);
// Isolated SQLite/fake-provider tests: never calls the real SMS provider.
require dirname(__DIR__,2).'/bootstrap/constants.php';
require BASE_PATH.'/bootstrap/autoload.php';
use App\Core\Crypto;
use App\Core\Database;
use App\Core\FileRateLimiter;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Services\PhoneVerificationService;
use App\Services\SmsIrClient;
$_ENV['APP_KEY']='sms-test-key-'.str_repeat('x',32); $_ENV['APP_ENV']='local';
$_ENV['SMS_ENABLED']='true'; $_ENV['SMS_IR_API_KEY']='fake-provider-key'; $_ENV['SMS_IR_OTP_TEMPLATE_ID']='123456';
$_ENV['SMS_DAILY_LIMIT']='40'; $_ENV['SMS_HOURLY_LIMIT']='10';
$schema="CREATE TABLE users (id TEXT PRIMARY KEY,phone TEXT,status TEXT,name TEXT);
CREATE TABLE rate_limits (key_hash TEXT PRIMARY KEY,hits INTEGER,reset_at TEXT,updated_at TEXT);
CREATE TABLE phone_verifications (user_id TEXT PRIMARY KEY REFERENCES users(id),phone TEXT UNIQUE,verified_at TEXT);
CREATE TABLE sms_challenges (user_id TEXT PRIMARY KEY REFERENCES users(id),challenge_id TEXT,phone_hash TEXT,code_hash TEXT,expires_at TEXT,attempts INTEGER,status TEXT,sent_at TEXT,updated_at TEXT);
CREATE TABLE sms_deliveries (id TEXT PRIMARY KEY,user_id TEXT REFERENCES users(id),phone_hash TEXT,kind TEXT,status TEXT,provider_message_id INTEGER,provider_code INTEGER,cost NUMERIC,created_at TEXT,updated_at TEXT);";
// Workers are spawned only by this test, using an isolated temporary directory.
if(($argv[1] ?? '')==='--worker') {
    if($argv[2]==='file') $result=(new FileRateLimiter($argv[3]))->hit('concurrent',5,60);
    else { $pdo=Database::connect(['driver'=>'sqlite','database'=>$argv[3]]); $result=(new RateLimiter($pdo))->reserveMany([['key'=>'concurrent','max'=>5,'seconds'=>60]],static function(PDO $pdo): void {}); }
    echo json_encode($result); exit;
}
$checks=0; $check=static function(bool $condition,string $message) use (&$checks): void { if(!$condition) throw new RuntimeException($message); $checks++; };
$pdo=Database::connect(['driver'=>'sqlite','database'=>':memory:']); $pdo->exec($schema); Database::use($pdo);
$add=static function(string $id,string $phone) use($pdo): void { $pdo->prepare('INSERT INTO users VALUES (:id,:phone,\'active\',\'test member\')')->execute(['id'=>$id,'phone'=>$phone]); };
$add('u1','09123456789'); $add('u2','09123456789'); $add('u3','09123456780');
$calls=0; $codes=[]; $mode='sent';
$client=new SmsIrClient(static function(array $payload) use (&$calls,&$codes,&$mode): array {
    $calls++; $codes[$payload['mobile']]=$payload['parameters'][0]['value'];
    if($mode==='throw') throw new RuntimeException('Simulated timeout');
    return match($mode) {
        'sent'=>['http'=>200,'body'=>json_encode(['status'=>1,'data'=>['messageId'=>100+$calls,'cost'=>1]])],
        'failed'=>['http'=>200,'body'=>'{"status":5,"message":"rejected"}'],
        'http-failed'=>['http'=>400,'body'=>'{"status":1,"data":{"messageId":55}}'],
        default=>['http'=>200,'body'=>'{"status":1,"data":{}}'],
    };
});
$service=new PhoneVerificationService($pdo,$client);
$expireLimits=static function() use($pdo): void { $pdo->exec("UPDATE rate_limits SET reset_at='2000-01-01 00:00:00'"); };
$check($service->schemaReady(),'SMS schema available.');
$_ENV['SMS_ENABLED']='false'; $check($service->send('u1','1.2.3.4')['status']===503 && $calls===0,'Disabled service sends nothing.'); $_ENV['SMS_ENABLED']='true';
$result=$service->send('u1','1.2.3.4');
$check($result['status']===200 && $calls===1 && $service->state('u1')['pending'],'Accepted send enables code entry.');
$row=$pdo->query("SELECT * FROM sms_challenges WHERE user_id='u1'")->fetch();
$check($row['code_hash']!==$codes['09123456789'] && strlen($row['code_hash'])===64,'Only keyed OTP hash is stored.');
$check(!array_key_exists('code',$service->state('u1')) && !array_key_exists('code_hash',$service->state('u1')),'State does not expose OTP.');
$check($service->send('u1','8.8.8.8')['status']===429 && $calls===1,'Changing IP cannot bypass account cooldown.');
$check($service->send('u2','9.9.9.9')['status']===429 && $calls===1,'Another account cannot bypass phone cooldown.');
$check($service->verify('u2',$codes['09123456789'])['status']===422,'Code cannot verify another account.');
$wrong=$codes['09123456789']==='111111' ? '222222' : '111111';
for($i=0;$i<5;$i++) $check($service->verify('u1',$wrong)['status']===422,'Incorrect code rejected.');
$check($service->verify('u1',$codes['09123456789'])['status']===422 && !$service->state('u1')['pending'],'Five wrong guesses exhaust code even across new requests.');
$expireLimits(); $service->send('u1','1.2.3.4'); $code=$codes['09123456789'];
$pdo->exec("UPDATE sms_challenges SET expires_at='2000-01-01 00:00:00' WHERE user_id='u1'");
$check($service->verify('u1',$code)['status']===422,'Expired OTP rejected.');
$expireLimits(); $service->send('u1','1.2.3.4'); $code=$codes['09123456789'];
$pdo->exec("UPDATE users SET phone='09123456781' WHERE id='u1'");
$check(!$service->state('u1')['pending'] && $service->verify('u1',$code)['status']===422,'Changing phone invalidates the old challenge.');
$expireLimits(); $service->send('u1','1.2.3.4'); $code=$codes['09123456781'];
$persian=strtr($code,array_combine(str_split('0123456789'),preg_split('//u','۰۱۲۳۴۵۶۷۸۹',-1,PREG_SPLIT_NO_EMPTY)));
$check($service->verify('u1',$persian)['status']===200 && $service->state('u1')['verified'],'Correct Persian-digit code verifies current phone.');
$check($service->verify('u1',$code)['status']===422,'Consumed OTP cannot be replayed.');
$before=$calls; $check($service->send('u1','1.2.3.4')['status']===200 && $calls===$before,'Already verified account sends no SMS.');
$pdo->exec("UPDATE users SET phone='09123456782' WHERE id='u1'");
$check(!$service->state('u1')['verified'],'Verified status follows actual current phone.');
$pdo->exec("UPDATE users SET phone='09123456781' WHERE id='u2'"); $expireLimits(); $before=$calls;
$check($service->send('u2','9.9.9.9')['status']===503 && $calls===$before,'Verified phone cannot be claimed by another account.');
$mode='failed'; $expireLimits(); $result=$service->send('u3','1.2.3.4');
$check($result['status']===503 && !$service->state('u3')['pending'],'Provider rejection cannot enable verification.');
$check($service->verify('u3',$codes['09123456780'])['status']===422,'Failed provider code cannot verify.');
$before=$calls; $check($service->send('u3','2.3.4.5')['status']===429 && $calls===$before,'Failed send still reserves quota.');
$mode='throw'; $expireLimits(); $before=$calls; $service->send('u3','1.2.3.4');
$check($calls===$before+1 && $pdo->query("SELECT status FROM sms_challenges WHERE user_id='u3'")->fetchColumn()==='unknown','Timeout is recorded once and never retried.');
$mode='http-failed'; $check($client->sendCode('09123456780','123456')['status']==='failed','HTTP error overrides apparent success body.');
$mode='malformed'; $check($client->sendCode('09123456780','123456')['status']==='unknown','Malformed success never counts as accepted.');
$mode='sent'; $pdo->exec('DELETE FROM rate_limits'); $pdo->exec('DELETE FROM sms_challenges');
$_ENV['SMS_DAILY_LIMIT']='2'; $_ENV['SMS_HOURLY_LIMIT']='2';
$add('g1','09120000001'); $add('g2','09120000002'); $add('g3','09120000003');
$check($service->send('g1','1.1.1.1')['status']===200 && $service->send('g2','2.2.2.2')['status']===200,'Two independent senders consume global quota.');
$before=$calls; $check($service->send('g3','3.3.3.3')['status']===429 && $calls===$before,'Global cap works across different phones, accounts and IPs.');
$check((int)$pdo->query("SELECT hits FROM rate_limits WHERE key_hash='".Crypto::keyedHash('sms:user:g3')."'")->fetchColumn()===0,'Blocked reservation consumes none of the other budgets.');
$check($service->report()['budget']['site-day']['hits']===2,'Admin report shows persisted budget usage.');
$_ENV['TRUSTED_PROXIES']=''; $_ENV['VERCEL']='';
$check((new Request(server:['REMOTE_ADDR'=>'1.2.3.4','HTTP_X_FORWARDED_FOR'=>'9.9.9.9']))->ip()==='1.2.3.4','Untrusted forwarded IP cannot spoof limits.');
$_ENV['RATE_LIMIT_DRIVER']='database';
$middleware=new \App\Middleware\RateLimitMiddleware(1,60);
$r1=new Request(cookies:['session'=>'one'],server:['REMOTE_ADDR'=>'192.0.2.10','REQUEST_URI'=>'/test/first']); $r1->setRoutePattern('/test/{slug}');
$r2=new Request(cookies:['session'=>'two'],server:['REMOTE_ADDR'=>'192.0.2.10','REQUEST_URI'=>'/test/second']); $r2->setRoutePattern('/test/{slug}');
$check($middleware->handle($r1,static fn()=>Response::json(['ok'=>true]))->status()===200 && $middleware->handle($r2,static fn()=>Response::json(['ok'=>true]))->status()===429,'Changing cookie and route parameter cannot bypass a shared middleware bucket.');

$directory=STORAGE_PATH.'/temp/sms-test-'.bin2hex(random_bytes(8)); mkdir($directory,0700,true);
$cleanup=static function() use($directory): void { foreach(glob($directory.'/*') ?: [] as $path) { if(is_file($path)) unlink($path); } if(is_dir($directory)) rmdir($directory); };
register_shutdown_function($cleanup);
foreach(['file','database'] as $kind) {
    $target=$kind==='file' ? $directory : $directory.'/parallel.sqlite';
    if($kind==='database') { $db=Database::connect(['driver'=>'sqlite','database'=>$target]); $db->exec($schema); unset($db); }
    $workers=[];
    for($i=0;$i<12;$i++) {
        $process=proc_open([PHP_BINARY,__FILE__,'--worker',$kind,$target],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)) throw new RuntimeException('Cannot launch concurrency test worker.');
        $workers[]=[$process,$pipes];
    }
    $accepted=0;
    foreach($workers as [$process,$pipes]) { $output=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $exit=proc_close($process); $check($exit===0 && $error==='', 'Concurrency worker failed: '.$error); $accepted+=(int)(json_decode($output,true)['allowed'] ?? false); }
    $check($accepted===5,$kind.' limiter must accept exactly five simultaneous reservations.');
}
$cleanup();
echo "PASS: {$checks} SMS/security checks, including 24 concurrent workers; no real SMS sent.\n";

<?php
declare(strict_types=1);
ob_start();require __DIR__.'/payment-first-notifications.php';
use App\Repositories\CommunicationRepository;
use App\Services\CommunicationBotService;
$botChecks=0;$verifyBot=static function(bool $ok,string $message)use(&$botChecks):void{if(!$ok)throw new RuntimeException($message);$botChecks++;};
$tap=static function(int $chat,string $action)use($hub,$callback):array{
    static $id=6000;$before=(int)$hub->pdo()->query('SELECT COALESCE(MAX(rowid),0) FROM comm_outbox')->fetchColumn();
    (new CommunicationBotService($hub,'telegram'))->handle($callback($chat,$action,++$id));
    return array_map(static fn($r)=>CommunicationRepository::open($r['payload']),$hub->rows("SELECT payload FROM comm_outbox WHERE rowid>:before AND method='sendMessage' ORDER BY rowid",['before'=>$before]));
};
$texts=static fn(array $payloads):string=>implode("\n",array_column($payloads,'text'));
$managerAccount=$hub->account('telegram','501');
// A long outgoing list is not a flood of incoming commands.
for($i=0;$i<50;$i++)$hub->message($managerAccount,'Existing detailed receipt '.$i);
$menu=$tap(501,'admin');$verifyBot(str_contains($texts($menu),'مدیریت منتوریس'),'A manager with more than forty outgoing messages can still open management.');
$verifyBot(str_contains(json_encode($menu,JSON_UNESCAPED_UNICODE),'admin:search'),'Management includes a discoverable search button.');
foreach(['admin:receipts:pending:0','admin:receipts:approved:0','admin:receipts:rejected:0','admin:refunds','admin:content','pending:circle','pending:courses','admin:tickets','admin:tickets:answered:0','admin:tickets:closed:0','admin:alerts','admin:search'] as $action)$tap(501,$action);
$verifyBot(count($tap(501,'admin:receipts:pending:0'))===1,'Browsing receipts sends a compact single message instead of a burst of full reports.');
$pending=$hub->one("SELECT id FROM manual_payments WHERE status='pending' LIMIT 1");$full=$tap(501,'mpd:'.$pending['id']);
$verifyBot(str_contains($texts($full),'شناسه رسید: '.$pending['id']) && str_contains(json_encode($full),'mpx:'.$pending['id']),'Selecting a receipt shows full financial details and the combined decision button.');
$delivery=$tap(501,'admin:delivery');$verifyBot(str_contains($texts($delivery),'در صف:') && str_contains($texts($delivery),'هنوز ثبت نشده'),'Delivery health distinguishes queued messages from actual successful sends.');
$tap(501,'admin:search');
$verifyBot($hub->account('telegram','501')['state']==='admin_search','Search waits for a phone number.');
(new CommunicationBotService($hub,'telegram'))->handle($message(501,'+۹۸۹۱۲۹۹۹۰۰۰۸',6501));
$verifyBot($hub->account('telegram','501')['state']==='','A normalized Persian international phone completes the search.');
$last=CommunicationRepository::open($hub->one("SELECT payload FROM comm_outbox WHERE chat_id='501' AND method='sendMessage' ORDER BY rowid DESC LIMIT 1")['payload']);
$verifyBot(str_contains($last['text'],'09129990008') && str_contains($last['text'],$applicant['name']),'The actual matching member is shown with a private profile link.');
$hub->execute("UPDATE comm_accounts SET admin_alerts=0,notifications_enabled=0 WHERE channel='telegram' AND chat_id='501'");
$tap(501,'admin:alerts');$verifyBot((int)$hub->account('telegram','501')['admin_alerts']===0,'Opening notification settings does not silently toggle them.');
$tap(501,'admin:alerts:on');$a=$hub->account('telegram','501');$verifyBot((int)$a['admin_alerts']===1 && (int)$a['notifications_enabled']===1,'Explicit enable switches on both flags required to deliver managerial alerts.');
$link=$hub->issueLink($applicant,'telegram');$hub->claimLink($hub->account('telegram','888'),$link);
$own=$tap(888,'my:requests:0');$verifyBot(str_contains(json_encode($own),'my:courses:'.$application['id']),'A linked member sees their persisted registration.');
$detail=$tap(888,'my:courses:'.$application['id']);$verifyBot(str_contains($texts($detail),'تأیید شده') && str_contains($texts($detail),'15,000,000')===false && str_contains($texts($detail),'7,500,000'),'Personal payment view shows the actual installment and admission.');
$foreign=$tap(888,'my:courses:'.$telegramRequest['id']);$verifyBot(str_contains($texts($foreign),'در دسترس نیست') && !str_contains($texts($foreign),$telegramMember['phone']),'Guessing another registration id cannot expose its contact or payment data.');
$guest=$tap(889,'my:courses:'.$application['id']);$verifyBot(str_contains($texts($guest),'متصل کنید'),'An unlinked chat cannot read member information.');
$verifyBot(str_contains($texts($tap(888,'my:account')),$applicant['phone']),'Account status is available to its connected owner.');
$verifyBot(str_contains($texts($tap(888,'help')),'/orders'),'Help explains the available commands and actual payment flow.');
(new CommunicationBotService($hub,'telegram'))->handle($message(888,'/orders',6510));$last=CommunicationRepository::open($hub->one("SELECT payload FROM comm_outbox WHERE chat_id='888' AND method='sendMessage' ORDER BY rowid DESC LIMIT 1")['payload']);
$verifyBot(str_contains(json_encode($last),'my:courses:'.$application['id']),'The real /orders command reaches the personal registration list.');
$denied=$tap(888,'admin:search');$verifyBot(!str_contains($texts($denied),$telegramMember['phone']) && $hub->account('telegram','888')['state']==='','A member cannot start privileged user search.');
$beforeTickets=(int)$pdo->query('SELECT COUNT(*) FROM support_tickets')->fetchColumn();$tap(888,'support:registration');$tap(888,'faq');
(new CommunicationBotService($hub,'telegram'))->handle($message(888,'این پیام نباید تیکت تازه بسازد',6502));
$verifyBot((int)$pdo->query('SELECT COUNT(*) FROM support_tickets')->fetchColumn()===$beforeTickets,'Leaving a text-entry flow cannot accidentally turn a later message into a ticket.');
$ticketId=$hub->one('SELECT id FROM support_tickets LIMIT 1')['id'];$hub->execute("UPDATE support_tickets SET status='waiting' WHERE id=:id",['id'=>$ticketId]);
$waiting=$tap(501,'admin:tickets');$verifyBot(str_contains(json_encode($waiting),'at:'.$ticketId),'Waiting tickets are included in the actionable support inbox.');
$tap(501,'at:'.$ticketId);$hub->execute("UPDATE support_tickets SET status='closed' WHERE id=:id",['id'=>$ticketId]);$closed=$tap(501,'at:'.$ticketId);
$verifyBot(!str_contains(json_encode($closed),'reply:'.$ticketId),'Closed threads do not offer a misleading reply action.');
$program=\App\Services\PublicContentService::courses()[0];$preview=$tap(888,'c:'.\App\Services\TelegramBotService::eventKey($program['slug']));
$verifyBot(str_contains($texts($preview),'هزینه:') && str_contains($texts($preview),'نصف مبلغ'),'Program details show price and installment terms.');
foreach(['menu','events','courses','about','notify','tools','my:account','old:unknown'] as $action)$tap(888,$action);
// Validate the provider contract, including every existing financial notification.
$checked=0;foreach($hub->rows('SELECT payload FROM comm_outbox') as $job){$payload=CommunicationRepository::open($job['payload']);foreach($payload['reply_markup']['inline_keyboard']??[] as $row){if(!$row)throw new RuntimeException('Empty keyboard row.');foreach($row as $button){if(!isset($button['text']) || !is_string($button['text']) || ((isset($button['url'])?1:0)+(isset($button['callback_data'])?1:0)!==1))throw new RuntimeException('Malformed Telegram keyboard button.');if(isset($button['callback_data']) && strlen($button['callback_data'])>64)throw new RuntimeException('Oversized callback data.');$checked++;}}}
$verifyBot($checked>100,'All exercised menus and financial messages obey the real Telegram keyboard structure.');
$limitChat=9901;for($i=0;$i<46;$i++)$tap($limitChat,'notify');$limited=$hub->one("SELECT payload FROM comm_outbox WHERE chat_id='9901' AND method='answerCallbackQuery' ORDER BY rowid DESC LIMIT 1");
$verifyBot(str_contains(CommunicationRepository::open($limited['payload'])['text']??'','صبر'),'An actual incoming flood is limited with an explicit callback response.');
echo "PASS: {$botChecks} bot navigation, member privacy, keyboard, manager search, alerts and inbound flood checks ({$checked} valid buttons).\n";ob_end_flush();

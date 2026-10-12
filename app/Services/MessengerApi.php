<?php
declare(strict_types=1);
namespace App\Services;

final class MessengerApi
{
    public function __construct(public readonly string $channel='telegram')
    { if(!in_array($channel,['telegram','bale'],true)) throw new \InvalidArgumentException('Unknown messenger.'); }
    public function configured(): bool { return trim((string)env(strtoupper($this->channel).'_BOT_TOKEN',''))!==''; }
    private function origin(): string { return $this->channel==='telegram'?'https://api.telegram.org':'https://tapi.bale.ai'; }
    private function token(): string { return (string)env(strtoupper($this->channel).'_BOT_TOKEN',''); }
    public function call(string $method,array $payload=[]): array
    {
        if(!$this->configured() || !function_exists('curl_init')) throw new \RuntimeException('Messenger configuration or cURL unavailable.');
        if(!in_array($method,['getMe','setWebhook','getWebhookInfo','setMyCommands','setMyDescription','setMyShortDescription','sendMessage','sendVoice','sendAudio','sendDocument','sendPhoto','answerCallbackQuery','getFile'],true)) throw new \InvalidArgumentException('Unsupported messenger method.');
        $curl=curl_init($this->origin().'/bot'.$this->token().'/'.$method);
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>10,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_FOLLOWLOCATION=>false]);
        $raw=curl_exec($curl);$code=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
        $result=is_string($raw)?json_decode($raw,true):null;
        if($code===429) throw new MessengerException('Rate limited.',429,max(1,min(3600,(int)($result['parameters']['retry_after'] ?? 60))));
        if($code!==200 || !is_array($result) || empty($result['ok'])) throw new MessengerException('Messenger request failed.',in_array($code,[400,401,403,404],true)?$code:503);
        return is_array($result['result'] ?? null)?$result['result']:['value'=>$result['result'] ?? true];
    }
    /** Download only a provider-generated relative path, bounded in memory; never expose token URLs. */
    public function download(string $fileId,int $maximum=8388608): string
    {
        $info=$this->call('getFile',['file_id'=>$fileId]);$path=(string)($info['file_path'] ?? '');
        if($path==='' || !preg_match('~^[A-Za-z0-9_./-]+$~',$path) || str_contains($path,'..') || str_starts_with($path,'/') || (int)($info['file_size'] ?? 0)>$maximum) throw new \RuntimeException('Attachment is unavailable or oversized.');
        $bytes=''; $curl=curl_init($this->origin().'/file/bot'.$this->token().'/'.$path);
        curl_setopt_array($curl,[CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>15,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk) use(&$bytes,$maximum): int {if(strlen($bytes)+strlen($chunk)>$maximum) return 0;$bytes.=$chunk;return strlen($chunk);}]);
        $ok=curl_exec($curl);$code=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
        if($ok===false || $code!==200 || $bytes==='') throw new \RuntimeException('Could not retrieve attachment.');
        return $bytes;
    }
    public function sendAttachment(?array $file,string $chat): void
    {
        if(!$file) throw new \RuntimeException('Attachment not found.');
        $method=$file['media_kind']==='voice'?'sendVoice':'sendDocument';$field=$method==='sendVoice'?'voice':'document';
        if($file['channel']===$this->channel && !empty($file['file_id'])) {$this->call($method,['chat_id'=>$chat,$field=>$file['file_id']]);return;}
        $bytes=(new SupportFileService())->bytes($file);$temporary=tempnam(BASE_PATH.'/storage/temp','support-send-');if(!$temporary)throw new \RuntimeException('Private temporary storage unavailable.');
        try {
            file_put_contents($temporary,$bytes,LOCK_EX);$curl=curl_init($this->origin().'/bot'.$this->token().'/'.$method);
            curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>['chat_id'=>$chat,$field=>new \CURLFile($temporary,$file['mime_type'],$file['filename'])],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>15,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_FOLLOWLOCATION=>false]);
            $raw=curl_exec($curl);$code=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);$result=is_string($raw)?json_decode($raw,true):null;
            if($code!==200 || empty($result['ok']))throw new MessengerException('Attachment delivery failed.',in_array($code,[400,401,403,404,429],true)?$code:503,max(1,min(3600,(int)($result['parameters']['retry_after'] ?? 60))));
        }finally {if(is_file($temporary))unlink($temporary);}
    }
}

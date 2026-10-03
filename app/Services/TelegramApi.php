<?php
declare(strict_types=1);
namespace App\Services;

final class TelegramApi
{
    public function configured(): bool { return trim((string)env('TELEGRAM_BOT_TOKEN',''))!==''; }
    public function call(string $method,array $payload=[]): array
    {
        if(!$this->configured() || !function_exists('curl_init')) throw new \RuntimeException('Telegram configuration or PHP cURL is unavailable.');
        if(!in_array($method,['getMe','setWebhook','getWebhookInfo','setMyCommands','setMyDescription','setMyShortDescription','sendMessage','answerCallbackQuery'],true)) throw new \InvalidArgumentException('Unsupported Telegram method.');
        $curl=curl_init('https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN').'/'.$method);
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
        $raw=curl_exec($curl); $code=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
        $result=is_string($raw) ? json_decode($raw,true) : null;
        if(!is_array($result) || empty($result['ok']) || $code!==200) throw new \RuntimeException('Telegram API request failed. Check connectivity and configuration.');
        return (array)($result['result'] ?? []);
    }
}

<?php
declare(strict_types=1);
namespace App\Services;

use Closure;
use RuntimeException;

final class SmsIrClient
{
    public function __construct(private readonly ?Closure $transport = null) {}

    public function configured(): bool
    {
        return (bool)env('SMS_ENABLED',false) && trim((string)env('SMS_IR_API_KEY',''))!==''
            && (int)env('SMS_IR_OTP_TEMPLATE_ID',0)>0 && ($this->transport !== null || function_exists('curl_init'));
    }

    /** Exactly one provider request. Never retry a possibly accepted send. */
    public function sendCode(string $phone, string $code): array
    {
        $parameter=(string)env('SMS_IR_OTP_PARAMETER','CODE');
        if (!$this->configured() || !preg_match('/^09[0-9]{9}$/',$phone) || !preg_match('/^[0-9]{6}$/',$code) || !preg_match('/^[A-Za-z0-9_]{1,32}$/',$parameter)) throw new RuntimeException('SMS configuration unavailable.');
        $payload=['mobile'=>$phone,'templateId'=>(int)env('SMS_IR_OTP_TEMPLATE_ID',0),'parameters'=>[['name'=>$parameter,'value'=>$code]]];
        if ($this->transport !== null) $response=($this->transport)($payload);
        else {
            $curl=curl_init('https://api.sms.ir/v1/send/verify'); $body='';
            curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload,JSON_THROW_ON_ERROR),
                CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','X-API-KEY: '.(string)env('SMS_IR_API_KEY','')],
                CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
                CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk) use (&$body): int { if(strlen($body)+strlen($chunk)>16384) return 0; $body.=$chunk; return strlen($chunk); }]);
            $ok=curl_exec($curl); $http=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
            if ($ok===false) return ['status'=>'unknown','provider_code'=>null];
            $response=['http'=>$http,'body'=>$body];
        }
        $data=json_decode((string)($response['body'] ?? ''),true);
        $http=(int)($response['http'] ?? 0); $provider=is_array($data) && is_int($data['status'] ?? null) ? $data['status'] : null;
        $id=$data['data']['messageId'] ?? null;
        if ($http===200 && $provider===1 && (is_int($id) || (is_string($id) && ctype_digit($id))) && (int)$id>0) {
            $cost=$data['data']['cost'] ?? null;
            return ['status'=>'sent','provider_code'=>1,'message_id'=>(int)$id,'cost'=>is_numeric($cost) && (float)$cost>=0 && (float)$cost<1000000000 ? (float)$cost : null];
        }
        // Malformed success and server errors are ambiguous, so retain their quota.
        return ['status'=>($http>=400 && $http<500) || ($http===200 && $provider!==null && $provider!==1) ? 'failed' : 'unknown','provider_code'=>$provider];
    }
}

<?php
declare(strict_types=1);
// Read-only credential check. No SMS is sent and no response body/key is printed.
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/bootstrap/app.php';
if(!function_exists('curl_init') || !env('SMS_IR_API_KEY','')) { fwrite(STDERR,"SMS credentials or cURL unavailable.\n"); exit(1); }
$curl=curl_init('https://api.sms.ir/v1/credit'); $body='';
curl_setopt_array($curl,[CURLOPT_HTTPHEADER=>['Accept: application/json','X-API-KEY: '.(string)env('SMS_IR_API_KEY','')],CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk) use (&$body): int { if(strlen($body)+strlen($chunk)>16384) return 0; $body.=$chunk; return strlen($chunk); }]);
$ok=curl_exec($curl); $http=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl); $data=json_decode($body,true);
$valid=$ok!==false && $http===200 && is_array($data) && ($data['status'] ?? null)===1 && is_numeric($data['data'] ?? null);
echo json_encode(['authenticated'=>$valid,'http_status'=>$http,'provider_status'=>is_int($data['status'] ?? null) ? $data['status'] : null,'sms_sent'=>false],JSON_THROW_ON_ERROR).PHP_EOL;
exit($valid ? 0 : 1);

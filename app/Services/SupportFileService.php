<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Security;
final class SupportFileService
{
    public const MAX_BYTES=8388608;
    public static function filename(string $name): string {return mb_substr(preg_replace('/[^\p{L}\p{N} ._-]/u','_',basename(str_replace('\\','/',$name))) ?: 'attachment',0,160);}
    public function metadata(array $message,string $channel): ?array
    {
        foreach(['voice','audio','document','photo'] as $kind) {
            $media=$message[$kind] ?? null;if(!$media) continue;
            if($kind==='photo' && is_array($media)) $media=end($media);
            if(!is_array($media) || !is_string($media['file_id'] ?? null) || strlen($media['file_id'])>512) throw new \RuntimeException('فایل معتبر نیست.');
            $size=(int)($media['file_size'] ?? 0); if($size<0 || $size>self::MAX_BYTES) throw new \RuntimeException('حجم فایل باید حداکثر ۸ مگابایت باشد.');
            return ['channel'=>$channel,'file_id'=>$media['file_id'],'local_name'=>null,'filename'=>self::filename((string)($media['file_name'] ?? ($kind==='photo'?'image.jpg':($kind==='voice'?'voice.ogg':'attachment')))),'mime_type'=>mb_substr((string)($media['mime_type'] ?? ($kind==='voice'?'audio/ogg':($kind==='photo'?'image/jpeg':'application/octet-stream'))),0,100),'byte_size'=>$size,'media_kind'=>$kind];
        }
        return null;
    }
    public function upload(?array $file): ?array
    {
        if(!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
        if(($file['error'] ?? null)!==UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) throw new \RuntimeException('بارگذاری فایل کامل نشد.');
        if((int)($file['size'] ?? 0)>self::MAX_BYTES) throw new \RuntimeException('حجم فایل باید حداکثر ۸ مگابایت باشد.');
        return $this->store((string)file_get_contents($file['tmp_name']),self::filename((string)($file['name'] ?? 'attachment')));
    }
    public function validate(string $bytes,string $filename): string
    {
        if($bytes==='' || strlen($bytes)>self::MAX_BYTES) throw new \RuntimeException('فایل خالی یا بزرگ‌تر از ۸ مگابایت است.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $allowed=['application/pdf','image/jpeg','image/png','audio/ogg','application/ogg','audio/mpeg','audio/wav','audio/x-wav','audio/mp4','video/webm','audio/webm','text/plain'];
        if(in_array($mime,$allowed,true)) return $mime;
        if(in_array(strtolower(pathinfo($filename,PATHINFO_EXTENSION)),['docx','xlsx','pptx'],true) && in_array($mime,['application/zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.openxmlformats-officedocument.presentationml.presentation'],true)) return 'application/octet-stream';
        throw new \RuntimeException('فایل PDF، تصویر JPG/PNG، فایل صوتی، متن یا فایل Office مجاز است.');
    }
    public function store(string $bytes,string $filename): array
    {
        $mime=$this->validate($bytes,$filename);$name=Security::randomToken(16).'.bin';$directory=BASE_PATH.'/storage/uploads/support';
        if(!is_dir($directory) && !mkdir($directory,0700,true) && !is_dir($directory)) throw new \RuntimeException('ذخیرهٔ فایل در دسترس نیست.');
        if(file_put_contents($directory.'/'.$name,$bytes,LOCK_EX)!==strlen($bytes)) throw new \RuntimeException('ذخیرهٔ فایل انجام نشد.');
        @chmod($directory.'/'.$name,0600);
        return ['channel'=>'site','file_id'=>null,'local_name'=>$name,'filename'=>$filename,'mime_type'=>$mime,'byte_size'=>strlen($bytes),'media_kind'=>str_starts_with($mime,'audio/') || in_array($mime,['application/ogg','video/webm'],true)?'audio':'document'];
    }
    public function bytes(array $file): string
    {
        if(!empty($file['local_name'])) {
            if(!preg_match('/^[a-f0-9]{32}\.bin$/',$file['local_name'])) throw new \RuntimeException('Invalid attachment path.');
            $path=BASE_PATH.'/storage/uploads/support/'.$file['local_name'];
            if(!is_file($path) || filesize($path)>self::MAX_BYTES) throw new \RuntimeException('فایل در دسترس نیست.');
            $bytes=(string)file_get_contents($path);
        } else $bytes=(new MessengerApi($file['channel']))->download((string)$file['file_id'],self::MAX_BYTES);
        $this->validate($bytes,$file['filename']);return $bytes;
    }
}

<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Security;
use RuntimeException;

final class AvatarService
{
    public function store(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? 1)!==UPLOAD_ERR_OK || ($file['size'] ?? 0)>2*1024*1024 || !is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('عکس JPG، PNG یا WebP تا حجم ۲ مگابایت انتخاب کنید.');
        $path=$file['tmp_name']; $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; $size=@getimagesize($path);
        if (!isset($extensions[$mime]) || !$size || $size[0]<32 || $size[1]<32 || $size[0]>4000 || $size[1]>4000) throw new RuntimeException('تصویر معتبر با ابعاد ۳۲ تا ۴۰۰۰ پیکسل انتخاب کنید.');
        $relative='images/uploads/avatar-'.Security::randomToken(16).'.'.$extensions[$mime];
        if (!move_uploaded_file($path,BASE_PATH.'/public/assets/'.$relative)) throw new RuntimeException('ذخیره تصویر انجام نشد. دوباره تلاش کنید.');
        return $relative;
    }
}

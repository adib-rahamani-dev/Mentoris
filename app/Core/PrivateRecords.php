<?php
declare(strict_types=1);
namespace App\Core;

/** Encrypted, atomic storage for small records while an optional migration is pending. */
final class PrivateRecords
{
    private static function path(string $group,string $id): string
    {
        if (!preg_match('/^[a-z-]+$/',$group) || !preg_match('/^[a-f0-9]{16,64}$/',$id)) throw new \InvalidArgumentException('Invalid record key.');
        $directory=BASE_PATH.'/storage/data/'.$group;
        if (!is_dir($directory) && !mkdir($directory,0700,true) && !is_dir($directory)) throw new \RuntimeException('فضای ذخیره‌سازی قابل نوشتن نیست؛ با پشتیبانی تماس بگیرید.');
        return $directory.'/'.$id.'.sealed';
    }
    public static function read(string $group,string $id): ?array
    {
        $path=self::path($group,$id);
        if (!is_file($path)) return null;
        $raw=file_get_contents($path);
        $plaintext=is_string($raw) ? Crypto::decrypt($raw,'mentoris-'.$group.'-v1') : null;
        if ($plaintext===null) throw new \RuntimeException('اطلاعات ذخیره‌شده قابل خواندن نیست؛ کلید APP_KEY را تغییر ندهید.');
        return json_decode($plaintext,true,512,JSON_THROW_ON_ERROR);
    }
    public static function write(string $group,string $id,array $data): void
    {
        $path=self::path($group,$id); $temporary=$path.'.'.Security::randomToken(8).'.tmp';
        try {
            $sealed=Crypto::encrypt(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'mentoris-'.$group.'-v1');
            if (file_put_contents($temporary,$sealed,LOCK_EX)===false || !rename($temporary,$path)) throw new \RuntimeException('ذخیره انجام نشد؛ دوباره تلاش کنید.');
            @chmod($path,0600);
        } finally { if (is_file($temporary)) @unlink($temporary); }
    }
    public static function delete(string $group,string $id): void { $path=self::path($group,$id); if (is_file($path)) unlink($path); }
    public static function files(string $group): array { return glob(dirname(self::path($group,str_repeat('0',24))).'/*.sealed') ?: []; }
}

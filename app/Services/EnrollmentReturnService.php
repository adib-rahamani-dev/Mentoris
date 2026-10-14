<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Session;

final class EnrollmentReturnService
{
    public static function valid(mixed $path): ?string
    {
        if(!is_string($path) || strlen($path)>500)return null;
        return preg_match('~^/(courses|events)/[a-zA-Z0-9-]+(?:\?lang=(?:fa|en|ar|ku))?(?:\#(?:request|register))?$~D',$path)?$path:null;
    }
    public static function remember(mixed $path): void
    {
        if($target=self::valid($path))(new Session())->put('enrollment.return',['path'=>$target,'at'=>time()]);
    }
    public static function target(): ?string
    {
        $session=new Session();$saved=$session->get('enrollment.return');
        if(!is_array($saved) || (int)($saved['at']??0)<time()-86400 || !($target=self::valid($saved['path']??null))){$session->forget('enrollment.return');return null;}
        return $target;
    }
    public static function consume(): ?string
    {
        $target=self::target();(new Session())->forget('enrollment.return');return $target;
    }
    public static function afterLogin(array $user): ?string
    {
        if(!($target=self::target()))return null;
        return str_starts_with($target,'/courses/') || ManualPaymentService::profileReady($user)?self::consume():'/profile?next='.rawurlencode($target);
    }
}

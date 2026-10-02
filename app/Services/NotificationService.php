<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Security;
use App\Repositories\MemberProfileRepository;

final class NotificationService
{
    public function notify(array $user, string $title, string $message, bool $email = false): string
    {
        $s=Database::connection()->prepare('INSERT INTO notifications (id,user_id,title,message,created_at) VALUES (:id,:user_id,:title,:message,:created_at)');
        $s->execute(['id'=>Security::randomToken(8),'user_id'=>$user['id'],'title'=>$title,'message'=>$message,'created_at'=>Database::now()]);
        if (!$email) return 'panel';
        if (empty((new MemberProfileRepository())->find($user['id'])['marketing_consent'])) return 'no-consent';
        return (new EmailService())->send($user['email'],$title,$message) ? 'sent' : 'failed';
    }
}

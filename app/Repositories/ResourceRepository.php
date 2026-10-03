<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\Database;

final class ResourceRepository
{
    public function downloaded(string $userId,string $slug): void
    {
        $pdo=Database::connection(); $mysql=Database::driver($pdo)==='mysql';
        $sql='INSERT INTO resource_downloads (user_id,resource_slug,downloads,last_download_at) VALUES (:user,:slug,1,:now) '.($mysql ? 'ON DUPLICATE KEY UPDATE downloads=downloads+1,last_download_at=VALUES(last_download_at)' : 'ON CONFLICT(user_id,resource_slug) DO UPDATE SET downloads=downloads+1,last_download_at=excluded.last_download_at');
        try { $pdo->prepare($sql)->execute(['user'=>$userId,'slug'=>$slug,'now'=>Database::now()]); }
        catch(\PDOException $e) { if(!MemberProfileRepository::missingTable($e)) throw $e; }
    }
    public function stats(): array
    {
        try { return ['ready'=>true,'rows'=>Database::connection()->query('SELECT resource_slug,COUNT(*) AS members,SUM(downloads) AS downloads,MAX(last_download_at) AS latest FROM resource_downloads GROUP BY resource_slug ORDER BY downloads DESC')->fetchAll()]; }
        catch(\PDOException $e) { if(!MemberProfileRepository::missingTable($e)) throw $e; return ['ready'=>false,'rows'=>[]]; }
    }
}

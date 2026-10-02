<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\Database;
use PDO;

final class FeedbackReportRepository
{
    public function __construct(private readonly ?PDO $database=null) {}
    public function report(array $filters): array
    {
        $pdo=$this->database ?? Database::connection();
        $empty=['ready'=>false,'rows'=>[],'stats'=>['responses'=>0,'content'=>0,'hosting'=>0,'participants'=>0,'attended'=>0],'challenges'=>[],'page'=>1,'pages'=>1,'total'=>0];
        if (!(new CircleRepository($pdo))->feedbackAvailable()) return $empty;
        $event=is_string($filters['event'] ?? null) ? $filters['event'] : '';
        $where=[]; $params=[];
        if ($event!=='') { $where[]='s.event_slug=:event'; $params['event']=mb_substr($event,0,190); }
        $baseWhere=$where ? ' WHERE '.implode(' AND ',$where) : '';
        $s=$pdo->prepare('SELECT COUNT(*) AS responses,AVG(f.content_rating) AS content,AVG(f.hosting_rating) AS hosting FROM event_feedback f JOIN event_signups s ON s.id=f.signup_id'.$baseWhere); $s->execute($params); $stats=$s->fetch();
        $s=$pdo->prepare("SELECT COUNT(*) AS participants,SUM(CASE WHEN s.status='attended' THEN 1 ELSE 0 END) AS attended FROM event_signups s".$baseWhere); $s->execute($params); $stats=array_merge($stats,$s->fetch());
        $s=$pdo->prepare('SELECT f.challenge,COUNT(*) AS n FROM event_feedback f JOIN event_signups s ON s.id=f.signup_id'.$baseWhere.' GROUP BY f.challenge ORDER BY n DESC'); $s->execute($params); $challenges=$s->fetchAll();
        if (is_string($filters['q'] ?? null) && trim($filters['q'])!=='') { $where[]='(s.name LIKE :q1 OR s.phone LIKE :q2 OR f.comment LIKE :q3)'; foreach(['q1','q2','q3'] as $key) $params[$key]='%'.mb_substr(trim($filters['q']),0,120).'%'; }
        if (in_array($filters['rating'] ?? '',['1','2','3','4','5'],true)) { $where[]='f.content_rating=:rating'; $params['rating']=(int)$filters['rating']; }
        $from=' FROM event_feedback f JOIN event_signups s ON s.id=f.signup_id LEFT JOIN users u ON u.id=s.user_id';
        $clause=$where ? ' WHERE '.implode(' AND ',$where) : '';
        $s=$pdo->prepare('SELECT COUNT(*)'.$from.$clause); $s->execute($params); $total=(int)$s->fetchColumn();
        $pages=max(1,(int)ceil($total/50)); $page=max(1,min((int)($filters['page'] ?? 1),$pages));
        $s=$pdo->prepare('SELECT f.*,s.name,s.phone,s.city,s.event_slug,s.status,s.user_id,u.email'.$from.$clause.' ORDER BY f.created_at DESC,f.id DESC LIMIT 50 OFFSET '.(($page-1)*50)); $s->execute($params);
        return ['ready'=>true,'rows'=>$s->fetchAll(),'stats'=>$stats,'challenges'=>$challenges,'total'=>$total,'page'=>$page,'pages'=>$pages];
    }
}

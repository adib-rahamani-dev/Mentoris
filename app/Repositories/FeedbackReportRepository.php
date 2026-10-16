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
        $empty=['ready'=>false,'rows'=>[],'stats'=>['responses'=>0,'anonymous'=>0,'named'=>0,'content'=>0,'hosting'=>0,'participants'=>0,'attended'=>0],'challenges'=>[],'page'=>1,'pages'=>1,'total'=>0];
        if (!(new CircleRepository($pdo))->feedbackAvailable()) return $empty;
        $source="SELECT f.id,f.content_rating,f.hosting_rating,f.challenge,f.comment,f.created_at,s.name,s.phone,s.city,s.event_slug,s.status,s.user_id,u.email,'named' AS identity_mode,'legacy' AS source FROM event_feedback f JOIN event_signups s ON s.id=f.signup_id LEFT JOIN users u ON u.id=s.user_id";
        try {
            $pdo->query('SELECT id FROM survey_responses LIMIT 0');
            // Only identified responses may join users. Anonymous rows have no identity columns.
            $source.=" UNION ALL SELECT f.id,f.content_rating,f.hosting_rating,f.challenge,f.comment,f.created_at,CASE WHEN f.identity_mode='anonymous' THEN 'ناشناس' ELSE f.display_name END AS name,'' AS phone,'' AS city,f.event_slug,'survey' AS status,CASE WHEN f.identity_mode='named' THEN f.user_id ELSE NULL END AS user_id,CASE WHEN f.identity_mode='named' THEN u.email ELSE NULL END AS email,f.identity_mode,'survey' AS source FROM survey_responses f LEFT JOIN users u ON f.identity_mode='named' AND u.id=f.user_id";
        } catch(\PDOException $e) { if(!MemberProfileRepository::missingTable($e))throw $e; }
        $from=' FROM ('.$source.') f';
        $event=is_string($filters['event'] ?? null) ? mb_substr($filters['event'],0,190) : '';
        $where=[];$params=[];
        if($event!==''){$where[]='f.event_slug=:event';$params['event']=$event;}
        $baseWhere=$where?' WHERE '.implode(' AND ',$where):'';
        $s=$pdo->prepare("SELECT COUNT(*) AS responses,AVG(f.content_rating) AS content,AVG(f.hosting_rating) AS hosting,SUM(CASE WHEN identity_mode='anonymous' THEN 1 ELSE 0 END) AS anonymous,SUM(CASE WHEN identity_mode='named' THEN 1 ELSE 0 END) AS named".$from.$baseWhere);$s->execute($params);$stats=$s->fetch();
        $s=$pdo->prepare("SELECT COUNT(*) AS participants,SUM(CASE WHEN status='attended' THEN 1 ELSE 0 END) AS attended FROM event_signups".($event!==''?' WHERE event_slug=:event':''));$s->execute($params);$stats=array_merge($stats,$s->fetch());
        $s=$pdo->prepare('SELECT f.challenge,COUNT(*) AS n'.$from.$baseWhere.' GROUP BY f.challenge ORDER BY n DESC');$s->execute($params);$challenges=$s->fetchAll();
        if(is_string($filters['q']??null) && trim($filters['q'])!==''){$where[]='(f.name LIKE :q1 OR f.phone LIKE :q2 OR f.comment LIKE :q3)';foreach(['q1','q2','q3'] as $key)$params[$key]='%'.mb_substr(trim($filters['q']),0,120).'%';}
        if(in_array($filters['rating']??'',['1','2','3','4','5'],true)){$where[]='f.content_rating=:rating';$params['rating']=(int)$filters['rating'];}
        if(in_array($filters['identity']??'',['anonymous','named'],true)){$where[]='f.identity_mode=:identity';$params['identity']=$filters['identity'];}
        $clause=$where?' WHERE '.implode(' AND ',$where):'';
        $s=$pdo->prepare('SELECT COUNT(*)'.$from.$clause);$s->execute($params);$total=(int)$s->fetchColumn();
        $pages=max(1,(int)ceil($total/50));$page=max(1,min((int)($filters['page']??1),$pages));
        $s=$pdo->prepare('SELECT f.*'.$from.$clause.' ORDER BY f.created_at DESC,f.id DESC LIMIT 50 OFFSET '.(($page-1)*50));$s->execute($params);
        return ['ready'=>true,'rows'=>$s->fetchAll(),'stats'=>$stats,'challenges'=>$challenges,'total'=>$total,'page'=>$page,'pages'=>$pages];
    }
}

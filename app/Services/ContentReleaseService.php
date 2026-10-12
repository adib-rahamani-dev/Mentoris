<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Content\Autumn2026;
use PDO;

final class ContentReleaseService
{
    public function sync(PDO $pdo): array
    {
        return Database::transaction($pdo,static function(PDO $pdo): array {
            $counts=['created'=>0,'preserved'=>0];
            foreach(Autumn2026::entries() as $entry) {
                $lookup=$pdo->prepare('SELECT id FROM content_entities WHERE entity_type=:type AND slug=:slug'); $lookup->execute(['type'=>$entry['entity_type'],'slug'=>$entry['slug']]); $id=$lookup->fetchColumn();
                $now=Database::now();
                if(!$id) {
                    $id=substr(hash('sha256',$entry['entity_type'].'|'.$entry['slug']),0,32);
                    $pdo->prepare("INSERT INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES (:id,:type,:slug,'published',:sort,NULL,:published,:created,:updated)")->execute(['id'=>$id,'type'=>$entry['entity_type'],'slug'=>$entry['slug'],'sort'=>$entry['sort_order'],'published'=>$now,'created'=>$now,'updated'=>$now]);
                    $counts['created']++;
                } else $counts['preserved']++;
                $translation=$pdo->prepare("SELECT id,metadata FROM content_translations WHERE entity_id=:id AND locale='fa'"); $translation->execute(['id'=>$id]); $current=$translation->fetch();
                if(!$current) $pdo->prepare("INSERT INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) VALUES (:id,:entity,'fa',:title,:subtitle,:excerpt,:body,:metadata,:created,:updated)")->execute(['id'=>substr(hash('sha256',$id.'|fa'),0,32),'entity'=>$id,'title'=>$entry['title'],'subtitle'=>$entry['subtitle'],'excerpt'=>$entry['excerpt'],'body'=>$entry['body'],'metadata'=>json_encode($entry['metadata'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'created'=>$now,'updated'=>$now]);
                // Backfill only the requested missing second-event poster. Preserve subsequent admin edits.
                elseif($entry['slug']==='therapists-circle-second') {
                    $meta=json_decode($current['metadata'],true) ?: [];
                    if(empty($meta['image'])) { $meta['image']=$entry['metadata']['image']; $pdo->prepare('UPDATE content_translations SET metadata=:metadata,updated_at=:now WHERE id=:id')->execute(['metadata'=>json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'now'=>$now,'id'=>$current['id']]); }
                }
            }
            return $counts;
        });
    }

    public static function sql(): string
    {
        $sql="-- Mentoris content release 2026-10-12. Requires migration 002.\n-- Repeatable: existing content and administrator edits are preserved.\nSET NAMES utf8mb4;\nSTART TRANSACTION;\n";
        $quote=static fn(string $value): string => "CONVERT(0x".bin2hex($value)." USING utf8mb4)";
        foreach(Autumn2026::entries() as $e) {
            $id=substr(hash('sha256',$e['entity_type'].'|'.$e['slug']),0,32); $type=$quote($e['entity_type']); $slug=$quote($e['slug']);
            $sql.="\n-- ".$e['slug']."\nINSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('$id',$type,$slug,'published',".$e['sort_order'].",NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());\n";
            $values=implode(',',array_map($quote,[$e['title'],$e['subtitle'],$e['excerpt'],$e['body'],json_encode($e['metadata'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]));
            $sql.="INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',$values,UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=$type AND ce.slug=$slug;\n";
            if($e['slug']==='therapists-circle-second') $sql.="UPDATE content_translations ct JOIN content_entities ce ON ce.id=ct.entity_id SET ct.metadata=JSON_SET(COALESCE(ct.metadata,JSON_OBJECT()),'$.image',".$quote($e['metadata']['image'])."),ct.updated_at=UTC_TIMESTAMP() WHERE ce.entity_type=$type AND ce.slug=$slug AND ct.locale='fa' AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ct.metadata,'$.image')),'')='';\n";
        }
        return $sql."\nCOMMIT;\n";
    }
}

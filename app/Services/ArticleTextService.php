<?php
declare(strict_types=1);
namespace App\Services;
final class ArticleTextService
{
    /** Plain text and ## headings only; all output is escaped by the view. */
    public static function sections(string $body): array
    {
        $sections=[]; $heading=''; $paragraphs=[];
        foreach(preg_split('/\r?\n/',$body) ?: [] as $line) {
            if(preg_match('/^##\s+(.+)$/u',$line,$match)) {
                if($heading!=='' || trim(implode("\n",$paragraphs))!=='') $sections[]=['heading'=>$heading,'body'=>trim(implode("\n",$paragraphs))];
                $heading=trim($match[1]); $paragraphs=[];
            } else $paragraphs[]=$line;
        }
        if($heading!=='' || trim(implode("\n",$paragraphs))!=='') $sections[]=['heading'=>$heading,'body'=>trim(implode("\n",$paragraphs))];
        return $sections;
    }
}

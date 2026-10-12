<?php
declare(strict_types=1);
namespace App\Services;

/** Versioned definitions; personal answers never enter this service. */
final class ResourceService
{
    public const RETIRED = ['session-reflection','deliberate-practice','supervision-prep'];
    public static function all(): array
    {
        static $tools;
        return $tools ??= json_decode(file_get_contents(dirname(__DIR__).'/Content/AssessmentTools.json'),true,512,JSON_THROW_ON_ERROR);
    }
    public static function text(string $slug): ?string
    {
        $tool=self::all()[$slug] ?? null;
        if (!$tool) return null;
        $text='منتوریس | '.$tool['title']."\n\n".$tool['instruction']."\n\nگزینه‌ها: ".implode(' | ',$tool['labels'])."\n\n";
        foreach($tool['questions'] as $i=>$question) $text.=($i+1).'. '.$question."\nپاسخ: ....................\n\n";
        return $text."روش محاسبه\n".$tool['scoring']."\n\n".$tool['notice']."\n\nاین ابزار خودارزیابی آموزشی است و جایگزین ارزیابی بالینی نیست.\nhttps://mentorisacademy.com/resources/".$slug."\n";
    }
    public static function schemas(?string $slug=null): array
    {
        $base=rtrim((string)env('APP_URL','https://mentorisacademy.com'),'/');
        if(!preg_match('#^https?://#i',$base)) $base='https://'.$base;
        $crumbs=[['name'=>'منتوریس','item'=>$base.'/?lang=fa'],['name'=>'ابزارهای خودارزیابی','item'=>$base.'/resources?lang=fa']];
        $nodes=[];
        if($slug && ($tool=self::all()[$slug] ?? null)) {
            $url=$base.'/resources/'.$slug.'?lang=fa'; $crumbs[]=['name'=>$tool['short'],'item'=>$url];
            $nodes[]=['@type'=>'WebApplication','@id'=>$url.'#tool','name'=>$tool['title'],'description'=>$tool['intro'],'url'=>$url,'inLanguage'=>'fa-IR','applicationCategory'=>'HealthApplication','operatingSystem'=>'Any','browserRequirements'=>'Requires JavaScript','isAccessibleForFree'=>true,'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'IRR'],'publisher'=>['@id'=>$base.'/#organization'],'dateModified'=>$tool['updated'],'citation'=>array_column($tool['sources'],'url')];
        } else {
            $items=[]; foreach(self::all() as $key=>$tool) $items[]=['@type'=>'ListItem','position'=>count($items)+1,'name'=>$tool['title'],'url'=>$base.'/resources/'.$key.'?lang=fa'];
            $nodes[]=['@type'=>'CollectionPage','@id'=>$base.'/resources?lang=fa#collection','name'=>'پرسشنامه‌های خودارزیابی منتوریس','inLanguage'=>'fa-IR','mainEntity'=>['@type'=>'ItemList','itemListElement'=>$items]];
        }
        $nodes[]=['@type'=>'BreadcrumbList','itemListElement'=>array_map(static fn($crumb,$index)=>['@type'=>'ListItem','position'=>$index+1,...$crumb],$crumbs,array_keys($crumbs))];
        return $nodes;
    }
}

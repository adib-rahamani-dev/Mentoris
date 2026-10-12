<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/bootstrap/constants.php';
require BASE_PATH.'/bootstrap/autoload.php';
use App\Core\{Translator,Request,Database};
use App\Services\ResourceService;
use App\Controllers\{ResourcesController,SitemapController};
$_ENV['APP_ENV']='local'; $_ENV['APP_KEY']=\App\Core\Crypto::generateKey(); $_ENV['SESSION_DRIVER']='files'; $_ENV['APP_URL']='http://127.0.0.1:8103';
Translator::boot([],[],[]);
set_error_handler(static function($severity,$message,$file,$line) { if(!(error_reporting()&$severity)) return false; throw new ErrorException($message,0,$severity,$file,$line); });
$pdo=Database::connect(['driver'=>'sqlite','database'=>':memory:']); Database::use($pdo);
$pdo->exec('CREATE TABLE content_entities (id TEXT PRIMARY KEY,entity_type TEXT,slug TEXT,status TEXT,sort_order INTEGER,author_id TEXT,published_at TEXT,created_at TEXT,updated_at TEXT)');
$pdo->exec('CREATE TABLE content_translations (id TEXT,entity_id TEXT,locale TEXT,title TEXT,subtitle TEXT,excerpt TEXT,body TEXT,metadata TEXT,updated_at TEXT)');
$checks=0; $check=static function($condition,$message) use(&$checks) { if(!$condition) throw new RuntimeException($message); $checks++; };
$tools=ResourceService::all();
$check(count($tools)===3,'Exactly three replacement tools.');
$check(array_map(static fn($tool)=>count($tool['questions']),$tools)===['psychological-flexibility'=>30,'maslach-burnout'=>22,'ryff-wellbeing'=>18],'Question counts match the selected versions; duplicate MBI item removed.');
$controller=new ResourcesController(); $pages=[];
$_SERVER['REQUEST_URI']='/resources?lang=fa'; $pages['index']=$controller->index(new Request())->content();
foreach($tools as $slug=>$tool) {
 $coverage=[]; foreach($tool['dimensions'] as $dimension) $coverage=[...$coverage,...$dimension['items']]; sort($coverage);
 $check($coverage===range(1,count($tool['questions'])),'Every question is assigned once to a dimension.');
 $check(count(array_unique($tool['questions']))===count($tool['questions']),'No repeated question.');
 $request=new Request(); $request->setRouteParams(['slug'=>$slug]); $_SERVER['REQUEST_URI']='/resources/'.$slug.'?lang=fa';
 $response=$controller->show($request); $html=$response->content(); $pages[$slug]=$html;
 $check($response->status()===200,'Public tool works without authentication.');
 $check(substr_count($html,'data-question="')===count($tool['questions']),'All question text exists in server HTML, before JavaScript.');
 $check(str_contains($html,'name="robots" content="index, follow') && str_contains($html,'rel="canonical" href="http://127.0.0.1:8103/resources/'.$slug.'?lang=fa"'),'Indexable page has the expected unique canonical.');
 $check(str_contains($html,'WebApplication') && str_contains($html,'BreadcrumbList') && !str_contains($html,'FAQPage'),'Structured data describes the actual app and avoids deprecated FAQ rich-result markup.');
 $check(substr_count($html,'<h1>')===1 && str_contains($html,'روش نمره‌گذاری'),'Clear heading and scoring method.');
 $download=$controller->download($request);
 $check($download->status()===200 && $download->headers()['X-Robots-Tag']==='noindex, nofollow' && str_contains($download->content(),$tool['questions'][0]),'Blank worksheet download works for guests and is not indexed.');
}
foreach(ResourceService::RETIRED as $slug) {
 $request=new Request();$request->setRouteParams(['slug'=>$slug]);
 $check($controller->show($request)->status()===410 && $controller->download($request)->status()===410,'Retired page and download are permanently removed.');
}
$request=new Request(); $request->setRouteParams(['slug'=>'not-a-tool']);
$check($controller->show($request)->status()===404,'Unknown slug is not a soft 404.');
$sitemap=(new SitemapController())->index(new Request())->content();
foreach(array_keys($tools) as $slug) $check(str_contains($sitemap,'/resources/'.$slug.'?lang=fa'),'New tool is listed in sitemap.');
foreach(ResourceService::RETIRED as $slug) $check(!str_contains($sitemap,'/resources/'.$slug),'Retired tool is absent from sitemap.');
$check(substr_count($sitemap,'<lastmod>2026-10-12</lastmod>')>=3,'Tools have real release dates in sitemap.');
$check($tools['ryff-wellbeing']['reverse']===[3,4,5,9,10,13,16,17],'Corrected negative Ryff key is explicit.');
$check($tools['maslach-burnout']['overall']===false,'No invalid combined burnout score.');
$check(!str_contains(file_get_contents(BASE_PATH.'/public/assets/js/pages/assessments.js'),'fetch('),'Personal answers never call a network API.');
$pdo->exec("INSERT INTO content_entities (id,entity_type,slug,status,updated_at) VALUES ('hidden','article','act-psychological-flexibility-therapists','archived','2026-10-12'),('visible','article','persian-only-article','published','2026-10-12')");
$pdo->exec("INSERT INTO content_translations (entity_id,locale,title,body) VALUES ('visible','fa','مقاله فارسی','متن کامل')");
Translator::boot(['lang'=>'en'],[],[]);
$englishMap=(new SitemapController())->index(new Request())->content();
$check(!str_contains($englishMap,'/articles/act-psychological-flexibility-therapists'),'Archived seed articles are removed from sitemap.');
$check(str_contains($englishMap,'/articles/persian-only-article?lang=fa'),'Sitemap keeps Persian articles even for an English visitor.');
$check($controller->index(new Request())->status()===301 && $controller->show((function(){ $r=new Request();$r->setRouteParams(['slug'=>'ryff-wellbeing']);return $r; })())->headers()['Location']==='/resources/ryff-wellbeing?lang=fa','Unsupported tool languages redirect to their real Persian page.');
// Isolated static preview, no real authentication, MySQL, mail or SMS.
$directory=BASE_PATH.'/storage/temp/assessment-preview'; if(!is_dir($directory)) mkdir($directory,0777,true);
foreach($pages as $name=>$html) file_put_contents($directory.'/'.$name.'.html',$html);
file_put_contents($directory.'/sitemap.xml',$sitemap);
echo "PASS: {$checks} assessment integration checks; ".count($pages)." pages rendered.\n";

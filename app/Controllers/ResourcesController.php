<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Translator};
use App\Services\{ResourceService,AuthService};
final class ResourcesController extends Controller
{
    public function index(Request $request): Response
    {
        if(Translator::locale()!=='fa') return $this->redirect('/resources?lang=fa',301);
        return $this->view('pages.resources',['seoLanguages'=>['fa'],'title'=>'تست آنلاین انعطاف‌پذیری، فرسودگی شغلی و بهزیستی ریف | منتوریس','description'=>'سه پرسشنامه رایگان فارسی: انعطاف‌پذیری روان‌شناختی MPFI، فرسودگی شغلی ماسلاچ و بهزیستی ریف. پاسخ مرحله‌ای، نتیجه فوری و چاپ گزارش، بدون ثبت‌نام.','resources'=>ResourceService::all(),'tool'=>null,'structuredData'=>ResourceService::schemas()]);
    }
    public function show(Request $request): Response
    {
        $slug=(string)$request->route('slug'); $tool=ResourceService::all()[$slug] ?? null;
        if(!$tool) return $this->missing($slug);
        if(Translator::locale()!=='fa') return $this->redirect('/resources/'.$slug.'?lang=fa',301);
        return $this->view('pages.resources',['seoLanguages'=>['fa'],'title'=>$tool['title'].' | تست آنلاین منتوریس','description'=>$tool['intro'],'resources'=>ResourceService::all(),'tool'=>$tool,'slug'=>$slug,'structuredData'=>ResourceService::schemas($slug)]);
    }
    public function download(Request $request): Response
    {
        $slug=(string)$request->route('slug'); $text=ResourceService::text($slug);
        if($text===null) return $this->missing($slug);
        $user=(new AuthService())->user();
        if($user) (new \App\Repositories\ResourceRepository())->downloaded($user['id'],$slug);
        return new Response("\xEF\xBB\xBF".$text,200,['Content-Type'=>'text/plain; charset=UTF-8','Content-Disposition'=>'attachment; filename="mentoris-'.$slug.'.txt"','Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex, nofollow','X-Content-Type-Options'=>'nosniff']);
    }
    private function missing(string $slug): Response
    {
        $retired=in_array($slug,ResourceService::RETIRED,true);
        $response=$this->view('pages.resource-missing',['title'=>$retired?'این ابزار جایگزین شده است | منتوریس':'ابزار پیدا نشد | منتوریس','indexable'=>false,'retired'=>$retired,'resources'=>ResourceService::all()]);
        return new Response($response->content(),$retired?410:404,$response->headers());
    }
}

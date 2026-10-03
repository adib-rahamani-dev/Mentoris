<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\ResourceService;
use App\Services\AuthService;

final class ResourcesController extends Controller
{
    public function index(Request $request): Response { return $this->view('pages.resources',['seoLanguages'=>['fa'],'title'=>'ابزارهای رایگان درمانگران | منتوریس','description'=>'برگه‌های مرور جلسه، تمرین عامدانه و آمادگی برای سوپرویژن؛ رایگان، قابل چاپ و بدون فرم طولانی.','resources'=>ResourceService::all(),'tool'=>null,'member'=>(new AuthService())->user()]); }
    public function show(Request $request): Response
    {
        $slug=(string)$request->route('slug'); $tool=ResourceService::all()[$slug] ?? null;
        if(!$tool) return new Response('این ابزار پیدا نشد.',404);
        return $this->view('pages.resources',['seoLanguages'=>['fa'],'title'=>$tool['title'].' | منتوریس','description'=>$tool['intro'],'resources'=>ResourceService::all(),'tool'=>$tool,'slug'=>$slug,'member'=>(new AuthService())->user()]);
    }
    public function download(Request $request): Response
    {
        $slug=(string)$request->route('slug'); $text=ResourceService::text($slug);
        if($text===null) return new Response('این ابزار پیدا نشد.',404);
        $user=(new AuthService())->user(); if(!$user) return $this->redirect('/login');
        (new \App\Repositories\ResourceRepository())->downloaded($user['id'],$slug);
        return new Response("\xEF\xBB\xBF".$text,200,['Content-Type'=>'text/plain; charset=UTF-8','Content-Disposition'=>'attachment; filename="mentoris-'.$slug.'.txt"','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}

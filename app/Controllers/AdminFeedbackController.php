<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\PublicContentService;
use App\Repositories\FeedbackReportRepository;

final class AdminFeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $filters=(array)$request->query(); $report=(new FeedbackReportRepository())->report($filters);
        $windows=[]; foreach(\App\Services\SurveyWindowService::EVENTS as $slug) $windows[$slug]=(new \App\Services\SurveyWindowService())->status($slug);
        return $this->view('admin.feedback',['title'=>'گزارش بازخورد نشست‌ها','admin'=>(new AuthService())->user(),'filters'=>$filters,'report'=>$report,'events'=>PublicContentService::events(),'windows'=>$windows,'windowNotice'=>(new \App\Core\Session())->pull('feedback.window.notice')],'layouts.admin');
    }
    public function schedule(Request $request): Response
    {
        $slug=(string)$request->route('slug'); $service=new \App\Services\SurveyWindowService();
        if(!$service->status($slug)['ready']) { (new \App\Core\Session())->put('feedback.window.notice','ابتدا SQL شمارهٔ ۰۰۶ را وارد کنید.'); return $this->redirect('/admin/feedback'); }
        $before=$service->status($slug)['row']; $actor=(new AuthService())->user()['id'];
        $errors=$service->save($slug,(array)$request->input(),$actor);
        if(!$errors) \App\Core\Audit::record('feedback.schedule','feedback_window',$slug,$actor,$before ?? [],$service->status($slug)['row'] ?? [],$request->ip());
        (new \App\Core\Session())->put('feedback.window.notice',$errors ? implode(' ',array_merge(...array_values($errors))) : 'زمان نظرسنجی ذخیره شد.');
        return $this->redirect('/admin/feedback');
    }
}

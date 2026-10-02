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
        return $this->view('admin.feedback',['title'=>'گزارش بازخورد نشست‌ها','admin'=>(new AuthService())->user(),'filters'=>$filters,'report'=>$report,'events'=>PublicContentService::events()],'layouts.admin');
    }
}

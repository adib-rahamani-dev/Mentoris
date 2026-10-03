<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\PhoneVerificationService;
final class AdminSmsController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.sms',['title'=>'پیامک و تأیید موبایل','admin'=>(new AuthService())->user(),'report'=>(new PhoneVerificationService())->report(),'templateId'=>(int)env('SMS_IR_OTP_TEMPLATE_ID',0)],'layouts.admin')->withHeader('Cache-Control','no-store');
    }
}

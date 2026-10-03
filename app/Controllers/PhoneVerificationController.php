<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\PhoneVerificationService;
final class PhoneVerificationController extends Controller
{
    public function send(Request $request): Response
    {
        $id=(new AuthService())->user()['id']; $service=new PhoneVerificationService();
        return $this->respond($request,$service->send($id,$request->ip()),$service->state($id));
    }
    public function verify(Request $request): Response
    {
        $id=(new AuthService())->user()['id']; $service=new PhoneVerificationService(); $input=$request->input('code','');
        $result=$service->verify($id,is_string($input) && strlen($input)<=64 ? $input : '');
        return $this->respond($request,$result,$service->state($id));
    }
    private function respond(Request $request,array $result,array $state): Response
    {
        if(!$request->expectsJson()) { (new Session())->put('phone.notice',$result['message']); return $this->redirect('/profile#phone-verification'); }
        $response=Response::json(['message'=>$result['message'],'phone_verification'=>$state],$result['status'])->withHeader('Cache-Control','no-store');
        if($result['retry_after']>0) $response=$response->withHeader('Retry-After',(string)$result['retry_after']);
        return $response;
    }
}

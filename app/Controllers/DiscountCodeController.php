<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Session};
use App\Services\{AuthService,DiscountCodeService,ManualPaymentService,PublicContentService};
use App\Repositories\CommunicationRepository;
final class DiscountCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $hub=new CommunicationRepository();$service=new DiscountCodeService($hub);$page=max(1,(int)$request->query('page',1));$offset=($page-1)*30;
        $codes=$service->available()?$hub->rows("SELECT * FROM discount_codes ORDER BY created_at DESC,id DESC LIMIT 30 OFFSET $offset"):[];
        return $this->view('admin.discounts',['title'=>'کدهای تخفیف','admin'=>(new AuthService())->user(),'codes'=>$codes,'ready'=>$service->available(),'page'=>$page,'notice'=>(new Session())->pull('discount.notice'),'old'=>(new Session())->pull('discount.old')??[],'courses'=>PublicContentService::courses(),'events'=>PublicContentService::events()],'layouts.admin');
    }
    public function create(Request $request): Response
    {
        $input=$request->only(['code','discount_type','value','applies_to','item_slug','max_uses','starts_at','ends_at']);
        try{(new DiscountCodeService())->create($input,(new AuthService())->user());(new Session())->put('discount.notice','کد تخفیف ساخته شد.');}
        catch(\RuntimeException $e){(new Session())->put('discount.notice',$e instanceof \PDOException?'ثبت کد انجام نشد؛ دوباره تلاش کنید.':$e->getMessage());(new Session())->put('discount.old',array_filter($input,'is_string'));}
        return $this->redirect('/admin/discounts',303);
    }
    public function status(Request $request): Response
    {
        try{if(!in_array($request->input('active'),['0','1'],true))throw new \RuntimeException('وضعیت معتبر نیست.');(new DiscountCodeService())->setActive((string)$request->route('id'),$request->input('active')==='1',(new AuthService())->user());(new Session())->put('discount.notice','وضعیت کد ذخیره شد. تخفیف درخواست‌های قبلی حفظ می‌شود.');}
        catch(\RuntimeException $e){(new Session())->put('discount.notice',$e instanceof \PDOException?'ثبت وضعیت انجام نشد.':$e->getMessage());}
        return $this->redirect('/admin/discounts',303);
    }
    public function apply(Request $request): Response
    {
        try{
            $user=(new AuthService())->user();$context=(new ManualPaymentService())->context((string)$request->route('type'),(string)$request->route('id'),$user);$discounts=new DiscountCodeService();
            if($request->input('remove')==='1'){$discounts->remove($context,$user);$message='کد تخفیف حذف شد؛ مبلغ جدید را قبل از واریز بررسی کنید.';}
            else{if(!is_string($request->input('code')))throw new \RuntimeException('کد تخفیف معتبر وارد کنید.');$discount=$discounts->apply($context,$user,$request->input('code'));$message='کد اعمال شد؛ مبلغ نهایی '.number_format((int)$discount['final_amount']).' تومان است.';}
            (new Session())->put('manual.notice',$message);
        }catch(\RuntimeException $e){(new Session())->put('manual.notice',$e instanceof \PDOException?'اعمال کد انجام نشد؛ دوباره تلاش کنید.':$e->getMessage());}
        return $this->redirect('/payments',303);
    }
}

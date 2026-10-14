<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CircleRepository;
use App\Services\AuthService;
use App\Services\PublicContentService;
use App\Services\SeoService;
use RuntimeException;

final class EventsController extends Controller
{
    public function index(Request $request): Response
    {
        $events = array_map(fn (array $event): array => PublicContentService::event($event['slug']) ?? $event, PublicContentService::events());
        return $this->view('pages.events', [
            'title' => t('nav.events') . ' | Mentoris Academy',
            'description' => $events[0]['short_description'] ?? t('empty.text'),
            'events' => $events,
            'statuses' => PublicContentService::eventStatusLabels(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $event = PublicContentService::event($slug);
        if ($event === null) {
            return Response::html('<h1>404 - Event Not Found</h1>', 404);
        }
        return $this->renderEvent($event,success:$event['can_register'] && (int)(new \App\Core\Session())->get('event.receipt.'.$slug,0)>time());
    }

    public function register(Request $request, string $slug): Response
    {
        $event = PublicContentService::event($slug);
        if ($event === null) {
            return Response::html('<h1>404 - Event Not Found</h1>', 404);
        }
        if (!$event['can_register']) {
            return $this->renderEvent($event, [], $request->only(['name', 'phone', 'city']), false, 'درخواست حضور در این رویداد فعلاً فعال نیست.');
        }

        $user=(new AuthService())->user();
        if(!$user || !\App\Services\ManualPaymentService::profileReady($user)){
            $target='/events/'.$slug.'#register';\App\Services\EnrollmentReturnService::remember($target);
            return $this->redirect(($user?'/profile':'/login').'?next='.rawurlencode($target),303);
        }
        $data = $request->only(['name', 'phone', 'city','education','university','specialization','experience','approach','practice_areas','professional_number','motivation']);
        $data['name']=$user['name'];$data['phone']=$user['phone'];
        foreach($data as $key=>$value) $data[$key]=is_string($value) ? trim($value) : '';
        $data['phone'] = \App\Core\PhoneNumber::normalize($data['phone'] ?? '');
        $validator = new Validator();
        $valid = $validator->validate($data, [
            'name' => 'required|string|min:2|max:80',
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'city' => 'required|string|min:2|max:80',
            'education'=>'required|string|max:80',
            'university'=>'required|string|min:2|max:160',
            'specialization'=>'required|string|min:2|max:160',
            'experience'=>'required|string|max:80',
            'approach'=>'required|string|max:200',
            'practice_areas'=>'required|string|max:200',
            'professional_number'=>'string|max:80',
            'motivation'=>'string|max:1500',
        ]);
        if (!$valid) {
            return $this->renderEvent($event, $validator->errors(), $data);
        }

        $resume=null;$uploadedResume=null;
        try {
            $user = (new AuthService())->user();
            $circle = new CircleRepository();
            if (!$circle->applicationsAvailable()) return $this->renderEvent($event, [], $data, false, 'ثبت درخواست پس از آماده‌سازی پایگاه داده فعال می‌شود.');
            if($circle->findSignup($slug,$data['phone']))throw new RuntimeException('این شماره برای این رویداد قبلاً ثبت شده است.');
            $file=$request->file('resume');
            if($file && ($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                if(!in_array(strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION)),['pdf','docx'],true))throw new RuntimeException('رزومه را به صورت PDF یا DOCX بارگذاری کنید.');
                $resume=$uploadedResume=(new \App\Services\SupportFileService())->upload($file);
                if(!$resume || !in_array($resume['mime_type'],['application/pdf','application/octet-stream'],true))throw new RuntimeException('محتوای فایل رزومه معتبر نیست.');
            } elseif($request->input('reuse_resume')==='1') {
                $resume=$circle->savedApplication($user['id'])['resume']??null;
                if($resume){try{(new \App\Services\SupportFileService())->bytes($resume);}catch(RuntimeException){$resume=null;}}
            }
            $data['application']=array_intersect_key($data,array_flip(['education','university','specialization','experience','approach','practice_areas','professional_number','motivation']));
            $data['resume']=$resume;
            $signup=$circle->signup($slug, $data, $user['id'] ?? null);
        } catch (RuntimeException $exception) {
            if($uploadedResume && preg_match('/^[a-f0-9]{32}\.bin$/',$uploadedResume['local_name']))@unlink(BASE_PATH.'/storage/uploads/support/'.$uploadedResume['local_name']);
            return $this->renderEvent($event, [], $data, false, $exception->getMessage());
        }
        (new \App\Core\Session())->put('event.receipt.'.$slug,time()+1800);
        return $this->redirect('/payments/circle/'.$signup['id'],303);
    }

    private function renderEvent(array $event, array $errors = [], array $old = [], bool $success = false, ?string $notice = null): Response
    {
        $user=(new AuthService())->user();
        $profile=$user?(new \App\Repositories\MemberProfileRepository())->find($user['id']):[];
        $circles=new CircleRepository();$existing=$user && $circles->available()?$circles->findSignup($event['slug'],$user['phone']):null;
        if($existing && ($existing['user_id']??null)!==$user['id'])$existing=null;
        $success=$success && (!$existing || $existing['status']==='requested');
        $saved=$user?(new CircleRepository())->savedApplication($user['id']):null;
        $savedDetails=array_filter($saved['details']??[],static fn($value)=>is_string($value));
        $savedResume=$saved['resume']??null;
        if($savedResume && (!preg_match('/^[a-f0-9]{32}\.bin$/',(string)($savedResume['local_name']??'')) || !is_file(BASE_PATH.'/storage/uploads/support/'.$savedResume['local_name'])))$savedResume=null;
        return $this->view('pages.event-details', [
            'title' => $event['title'] . ' | Events',
            'description' => $event['short_description'],
            'seoImage' => '/assets/' . ltrim((string) ($event['image'] ?: 'images/mentoris-hero-sage-v2.png'), '/'),
            'seoType' => 'event',
            'structuredData' => !empty($event['date_iso']) ? [SeoService::eventSchema($event)] : [],
            'event' => $event,
            'registrationReady' => $event['can_register'] && (new CircleRepository())->applicationsAvailable(),
            'enrollmentReady'=>\App\Services\ManualPaymentService::profileReady($user),'enrollmentUser'=>$user,
            'errors' => $errors,
            'savedApplication'=>$saved,'savedResume'=>$savedResume,
            'existingCircleRequest'=>$existing,
            'old' => $old+['name'=>$user['name']??'','phone'=>$user['phone']??'']+$savedDetails+['city'=>$profile['city']??'','university'=>$profile['university']??'','education'=>\App\Services\MemberProfileService::OPTIONS['degree'][$profile['degree']??'']??'','specialization'=>$profile['specialization']??$profile['field_of_study']??'','experience'=>$profile['experience']??'','practice_areas'=>$profile['specialty_fields']??''],
            'success' => $success,
            'notice' => $notice,
        ])->withHeader('Cache-Control','private, no-store');
    }

    public function resume(Request $request): Response
    {
        $user=(new AuthService())->user();
        if(!$user || !\App\Core\Authorization::can($user,'engagements.view'))return Response::html('دسترسی مجاز نیست.',403);
        $application=(new CircleRepository())->application((string)$request->route('id'));
        if(!$application || empty($application['resume']))return Response::html('رزومه‌ای برای این درخواست ثبت نشده است.',404);
        try {$file=$application['resume'];$bytes=(new \App\Services\SupportFileService())->bytes($file);}
        catch(RuntimeException){return Response::html('فایل در دسترس نیست.',404);}
        return new Response($bytes,200,['Content-Type'=>$file['mime_type'],'Content-Disposition'=>"attachment; filename*=UTF-8''".rawurlencode($file['filename']),'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class CoursesController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = array_map(fn (array $course): array => PublicContentService::course($course['slug']) ?? $course, PublicContentService::courses());
        return $this->view('pages.courses', [
            'title' => t('nav.courses') . ' | Mentoris Academy',
            'description' => 'دوره‌ها و کارگاه‌های منتوریس برای توسعه مهارت درمانگر؛ سرفصل‌ها، مدرسان، شرایط پذیرش و پرداخت نقدی یا دو مرحله‌ای را ببینید.',
            'indexable' => $courses !== [],
            'courses' => $courses,
            'categories' => PublicContentService::courseCategories(),
            'statuses' => PublicContentService::courseStatusLabels(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $course = PublicContentService::course($slug);
        if ($course === null) {
            return Response::html('<h1>404 - Course Not Found</h1>', 404);
        }
        return $this->renderCourse($course,success:$course['can_request'] && (int)(new \App\Core\Session())->get('course.receipt.'.$slug,0)>time());
    }

    public function requestPlace(Request $request,string $slug): Response
    {
        $course=PublicContentService::course($slug);
        if(!$course) return Response::html('<h1>404</h1>',404);
        if(!$course['can_request'] && !$course['can_enroll']) return $this->renderCourse($course,[],[],false,'ثبت درخواست برای این دوره فعال نیست.');
        $user=(new \App\Services\AuthService())->user();
        if(!$user){
            $target='/courses/'.$slug.'#request';\App\Services\EnrollmentReturnService::remember($target);
            return $this->redirect('/login?next='.rawurlencode($target),303);
        }
        $existing=(new \App\Repositories\CourseRequestRepository())->forUser($slug,$user['id']);
        if($existing)return $this->redirect($existing['status']==='approved'?'/payments':'/courses/'.$slug.'#request',303);
        $data=$request->only(['name','phone','payment_plan']);
        $data['name']=$user['name'];$data['phone']=$user['phone'];
        foreach($data as $key=>$value) $data[$key]=is_string($value) ? trim($value) : '';
        $data['phone']=\App\Core\PhoneNumber::normalize($data['phone'] ?? '');
        $validator=new \App\Core\Validator();
        $validator->validate($data,['name'=>'required|string|min:2|max:120','phone'=>['required','regex:/^09[0-9]{9}$/']]); $errors=$validator->errors();
        if(!in_array($data['payment_plan'] ?? '',$course['payment_options'],true)) $errors['payment_plan']=['شیوه پرداخت معتبر انتخاب کنید.'];
        if($errors) return $this->renderCourse($course,$errors,$data);
        $repository=new \App\Repositories\CourseRequestRepository();
        if(!$repository->available()) return $this->renderCourse($course,[],$data,false,'ثبت درخواست پس از نصب به‌روزرسانی دیتابیس فعال می‌شود.');
        try { $repository->request($slug,$data,(new \App\Services\AuthService())->user()['id'] ?? null); }
        catch(\RuntimeException $e) { return $this->renderCourse($course,[],$data,false,$e->getMessage()); }
        (new \App\Core\Session())->put('course.receipt.'.$slug,time()+1800);
        return $this->redirect('/courses/'.$slug.'#request',303);
    }

    private function renderCourse(array $course,array $errors=[],array $old=[],bool $success=false,?string $notice=null): Response
    {
        $user=(new \App\Services\AuthService())->user();
        return $this->view('pages.course-details', [
            'title' => $course['title'] . ' | Courses',
            'description' => $course['short_description'],
            'seoImage' => !empty($course['image']) ? '/assets/'.$course['image'] : null,
            'structuredData' => [\App\Services\SeoService::courseSchema($course),\App\Services\SeoService::faqSchema($course['faq']??[])],
            'requestReady'=>(new \App\Repositories\CourseRequestRepository())->available(),
            'enrollmentReady'=>$user!==null,'enrollmentUser'=>$user,
            'existingCourseRequest'=>$user?(new \App\Repositories\CourseRequestRepository())->forUser($course['slug'],$user['id']):null,
            'errors'=>$errors,'old'=>$old+['name'=>$user['name'] ?? '','phone'=>$user['phone'] ?? ''],'success'=>$success,'notice'=>$notice,
            'course' => $course,
        ])->withHeader('Cache-Control','private, no-store');
    }
}

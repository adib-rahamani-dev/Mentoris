<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CircleRepository;
use App\Services\AuthService;
use App\Services\PublicContentService;
use RuntimeException;

final class FeedbackController extends Controller
{
    private const EVENT = 'therapists-circle-second';
    private const EVENTS = ['therapists-circle-tabriz', 'therapists-circle-second'];

    private function eventSlug(): string
    {
        $value=(new Session())->get('feedback.event',self::EVENT);
        return in_array($value,self::EVENTS,true) ? $value : self::EVENT;
    }

    public function index(Request $request): Response
    {
        $slug=$request->query('event');
        if (is_string($slug) && in_array($slug,self::EVENTS,true) && $slug!==$this->eventSlug()) {
            (new Session())->put('feedback.event',$slug);
            (new Session())->put('feedback.signup','');
        }
        if(!(new AuthService())->user()) (new Session())->put('auth.intended','/feedback?event='.$this->eventSlug());
        return $this->render();
    }

    public function access(Request $request): Response
    {
        $event=$request->input('event',self::EVENT);
        if (!is_string($event) || !in_array($event,self::EVENTS,true)) return $this->render(['access'=>'نشست معتبر انتخاب کنید.']);
        (new Session())->put('feedback.event',$event);
        (new Session())->put('feedback.signup','');
        $identity=(new AuthService())->user();
        if ($identity && $request->input('member_access')==='1') {
            if ($request->input('participated')!=='1') return $this->render(['access'=>'شرکت در نشست انتخاب‌شده را مشخص کنید.']);
            $repository=new CircleRepository();
            if (!$repository->feedbackAvailable()) return $this->render(['access'=>'ابتدا ساختار پایگاه داده رویداد آماده شود.']);
            $phone=CircleRepository::phone((string)$identity['phone']);
            if (!preg_match('/^09[0-9]{9}$/',$phone)) return $this->render(['access'=>'شماره موبایل را در پروفایل خود وارد کنید.']);
            $signup=$repository->findSignup($event,$phone);
            if ($signup && ($signup['user_id'] ?? null)!==$identity['id']) return $this->render(['access'=>'برای اتصال پروندهٔ حضور قبلی به حساب خود با برگزارکننده تماس بگیرید یا از کد سالن استفاده کنید.']);
            if (!$signup) {
                // Self-reported participation enables feedback, never attendance or a certificate.
                try { $signup=$repository->signup($event,['name'=>$identity['name'],'phone'=>$phone,'city'=>''],$identity['id']); }
                catch (RuntimeException $e) { return $this->render(['access'=>$e->getMessage()]); }
            }
            if ($signup['status']==='rejected') return $this->render(['access'=>'پروندهٔ حضور شما نیاز به بررسی برگزارکننده دارد.']);
            (new Session())->put('feedback.signup',$signup['id']);
            return $this->redirect('/feedback');
        }
        $phone = CircleRepository::phone((string) $request->input('phone', ''));
        $code = trim((string) $request->input('access_code', ''));
        $expected = $this->accessCode();
        if (!preg_match('/^09[0-9]{9}$/', $phone) || $expected === '' || !hash_equals($expected, $code)) {
            return $this->render(['access' => 'شماره یا کد سالن معتبر نیست.']);
        }
        $repository = new CircleRepository();
        if (!$repository->feedbackAvailable()) return $this->render(['access' => 'فضای نشست پس از آماده‌سازی پایگاه داده فعال می‌شود.']);
        $signup = $repository->findSignup($this->eventSlug(), $phone);
        if (!$signup || $signup['status']==='rejected') return $this->render(['access' => 'پروندهٔ حضور برای این شماره پیدا نشد. وارد حساب شوید یا با برگزارکننده تماس بگیرید.']);
        if ($identity && $signup['user_id']!==null && $signup['user_id']!==$identity['id']) return $this->render(['access'=>'این پرونده به حساب دیگری متصل است.']);
        (new Session())->put('feedback.signup', $signup['id']);
        return $this->redirect('/feedback');
    }

    public function feedback(Request $request): Response
    {
        $event=$request->input('event');
        if(is_string($event) && in_array($event,self::EVENTS,true) && $event!==$this->eventSlug()) { (new Session())->put('feedback.event',$event); (new Session())->put('feedback.signup',''); }
        $signup = $this->activeSignup();
        $data = $request->only(['content_rating','hosting_rating','challenge','comment']);
        $errors = [];
        foreach (['content_rating','hosting_rating'] as $field) if (!in_array((string) ($data[$field] ?? ''), ['1','2','3','4','5'], true)) $errors[$field] = 'امتیاز ۱ تا ۵ را انتخاب کنید.';
        $data['challenge']=$data['challenge'] ?? 'none';
        if($data['challenge']==='') $data['challenge']='none';
        if (!in_array($data['challenge'], ['none','burnout','technique','countertransference','supervision'], true)) $errors['challenge'] = 'گزینه معتبر انتخاب کنید.';
        if (!is_string($data['comment'] ?? '') || mb_strlen((string) ($data['comment'] ?? '')) > 1000) $errors['comment'] = 'متن باید حداکثر ۱۰۰۰ نویسه باشد.';
        if ($errors) return $request->expectsJson() ? Response::json(['message'=>'دو امتیاز را انتخاب کنید.','errors'=>$errors],422) : $this->render($errors, $data);
        if(!$signup && (new AuthService())->user() && $request->input('member_access')==='1') {
            $access=$this->access($request); $signup=$this->activeSignup();
            if(!$signup) return $request->expectsJson() ? Response::json(['message'=>'شماره پروفایل یا اتصال پرونده حضور را بررسی کنید.'],422) : $access;
        }
        if (!$signup) return $request->expectsJson() ? Response::json(['message'=>'برای ثبت نظر وارد حساب شوید.'],401) : $this->redirect('/feedback');
        try {
            (new CircleRepository())->saveFeedback($signup['id'], $data);
        } catch (RuntimeException $exception) {
            if($request->expectsJson()) return Response::json(['message'=>$exception->getMessage(),'recorded'=>(new CircleRepository())->feedback($signup['id'])!==null]);
            return $this->render(['feedback' => $exception->getMessage()]);
        }
        if($request->expectsJson()) return Response::json(['message'=>'بازخورد شما ثبت شد. ممنون از همراهی‌تان.','recorded'=>true]);
        return $this->redirect('/feedback?done=1');
    }

    public function toolbox(Request $request): Response
    {
        if (!$this->activeSignup()) return $this->redirect('/feedback');
        $user = (new AuthService())->user();
        if (!$user) {
            (new Session())->put('auth.intended', '/feedback');
            return $this->redirect('/register');
        }
        $path = base_path('output/pdf/anchoring-grace-toolkit.pdf');
        if (!is_file($path)) return Response::html('فایل کارگاه فعلاً در دسترس نیست.', 503);
        return new Response((string) file_get_contents($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="mentoris-anchoring-grace.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function activeSignup(): ?array
    {
        $id = (string) (new Session())->get('feedback.signup', '');
        if ($id === '') return null;
        $repository = new CircleRepository();
        if (!$repository->feedbackAvailable()) return null;
        // Session contains an opaque signup ID; only a matching event row is accepted.
        $signup = $repository->findSignupById($this->eventSlug(), $id);
        if (!$signup || $signup['status']==='rejected') return null;
        $identity = (new AuthService())->user();
        if ($identity) {
            if ($signup['user_id'] === null) {
                $repository->claimSignup($id, (string) $identity['id']);
                $signup = $repository->findSignupById($this->eventSlug(), $id);
            }
            if (($signup['user_id'] ?? null) !== $identity['id']) return null;
        }
        return $signup;
    }

    private function render(array $errors = [], array $old = []): Response
    {
        $signup = $this->activeSignup();
        $ready = (new CircleRepository())->feedbackAvailable();
        $feedback = $signup ? (new CircleRepository())->feedback($signup['id']) : null;
        $identity = (new AuthService())->user();
        $profileComplete = $signup && $identity && ((new CircleRepository())->profileComplete((string) $identity['id']) || !empty((new \App\Repositories\MemberProfileRepository())->find($identity['id'])['completed_at']));
        return $this->view('pages.feedback', [
            'title' => 'همراه نشست دوم | منتوریس',
            'description' => 'بازخورد نشست، جعبه‌ابزار لنگراندازی و عضویت در جامعه منتوریس.',
            'indexable' => false,
            'event' => PublicContentService::event($this->eventSlug()),
            'events'=>array_map(fn($slug)=>PublicContentService::event($slug),self::EVENTS),
            'identity'=>$identity,'hallCodeEnabled'=>$this->accessCode()!=='',
            'signup' => $signup,
            'feedback' => $feedback,
            'profileComplete' => $profileComplete,
            'feedbackReady' => $ready,
            'errors' => $errors,
            'old' => $old,
            'done' => isset($_GET['done']),
        ]);
    }

    private function accessCode(): string
    {
        return trim((string) env('FEEDBACK_ACCESS_CODE', '')) ?: trim((string) env('LIVE_ACCESS_CODE', ''));
    }
}

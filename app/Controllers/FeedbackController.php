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

    public function index(Request $request): Response
    {
        return $this->render();
    }

    public function access(Request $request): Response
    {
        $phone = CircleRepository::phone((string) $request->input('phone', ''));
        $code = trim((string) $request->input('access_code', ''));
        $expected = $this->accessCode();
        if (!preg_match('/^09[0-9]{9}$/', $phone) || $expected === '' || !hash_equals($expected, $code)) {
            return $this->render(['access' => 'شماره یا کد سالن معتبر نیست.']);
        }
        $repository = new CircleRepository();
        if (!$repository->feedbackAvailable()) return $this->render(['access' => 'فضای نشست پس از آماده‌سازی پایگاه داده فعال می‌شود.']);
        $signup = $repository->findSignup(self::EVENT, $phone);
        if (!$signup) return $this->render(['access' => 'برای این شماره درخواست حضور ثبت نشده است. ابتدا فرم رویداد را تکمیل کنید.']);
        (new Session())->put('feedback.signup', $signup['id']);
        return $this->redirect('/feedback');
    }

    public function feedback(Request $request): Response
    {
        $signup = $this->activeSignup();
        if (!$signup) return $this->redirect('/feedback');
        $data = $request->only(['content_rating','hosting_rating','challenge','comment']);
        $errors = [];
        foreach (['content_rating','hosting_rating'] as $field) if (!in_array((string) ($data[$field] ?? ''), ['1','2','3','4','5'], true)) $errors[$field] = 'امتیاز ۱ تا ۵ را انتخاب کنید.';
        if (!in_array((string) ($data['challenge'] ?? ''), ['burnout','technique','countertransference','supervision'], true)) $errors['challenge'] = 'یک گزینه انتخاب کنید.';
        if (!is_string($data['comment'] ?? '') || mb_strlen((string) ($data['comment'] ?? '')) > 1000) $errors['comment'] = 'متن باید حداکثر ۱۰۰۰ نویسه باشد.';
        if ($errors) return $this->render($errors, $data);
        try {
            (new CircleRepository())->saveFeedback($signup['id'], $data);
        } catch (RuntimeException $exception) {
            return $this->render(['feedback' => $exception->getMessage()]);
        }
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
        if (!(new CircleRepository())->profileComplete((string) $user['id'])) return $this->redirect('/profile');
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
        $signup = $repository->findSignupById(self::EVENT, $id);
        if (!$signup) return null;
        $identity = (new AuthService())->user();
        if ($identity) {
            if ($signup['user_id'] === null) {
                $repository->claimSignup($id, (string) $identity['id']);
                $signup = $repository->findSignupById(self::EVENT, $id);
            }
            if (($signup['user_id'] ?? null) !== $identity['id']) return null;
        }
        return $signup;
    }

    private function render(array $errors = [], array $old = []): Response
    {
        $signup = $this->activeSignup();
        $ready = (new CircleRepository())->feedbackAvailable() && $this->accessCode() !== '';
        $feedback = $signup ? (new CircleRepository())->feedback($signup['id']) : null;
        $identity = (new AuthService())->user();
        $profileComplete = $signup && $identity && (new CircleRepository())->profileComplete((string) $identity['id']);
        return $this->view('pages.feedback', [
            'title' => 'همراه نشست دوم | منتوریس',
            'description' => 'بازخورد نشست، جعبه‌ابزار لنگراندازی و عضویت در جامعه منتوریس.',
            'indexable' => false,
            'event' => PublicContentService::event(self::EVENT),
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

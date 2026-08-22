<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\PaymentService;
use App\Services\PublicContentService;
use RuntimeException;

final class PaymentController extends Controller
{
    public function checkout(Request $request, string $slug): Response
    {
        $course = PublicContentService::course($slug);
        if ($course === null) return Response::html('<h1>404 - Course Not Found</h1>', 404);
        return $this->checkoutView($course, (new AuthService())->user() ?? []);
    }

    public function pay(Request $request, string $slug): Response
    {
        $course = PublicContentService::course($slug);
        if ($course === null) return Response::html('<h1>404 - Course Not Found</h1>', 404);
        $user = (new AuthService())->user() ?? [];
        if ($request->input('accept') !== '1') return $this->checkoutView($course, $user, ['accept' => ['پذیرش قوانین خرید الزامی است.']]);
        try {
            $result = (new PaymentService())->checkoutCourse($course, $user);
            return $this->redirect($result['redirect_url']);
        } catch (RuntimeException $exception) {
            return $this->checkoutView($course, $user, ['payment' => [$exception->getMessage()]]);
        }
    }

    public function callback(Request $request): Response
    {
        try {
            $result = (new PaymentService())->verify((string) $request->query('Authority', ''), (string) $request->query('Status', 'NOK'));
            return $this->redirect('/payment/result?order=' . rawurlencode($result['order']['id']) . '&payment=' . ($result['successful'] ? 'success' : 'failed'));
        } catch (RuntimeException) {
            return $this->redirect('/payment/result?payment=failed');
        }
    }

    public function sandbox(Request $request, string $authority): Response
    {
        $service = new PaymentService();
        $transaction = $service->transaction($authority);
        $order = $transaction ? $service->order($transaction['order_id']) : null;
        if ($transaction === null || $order === null || $transaction['gateway'] !== 'iranian-sandbox') return Response::html('<h1>404 - Transaction Not Found</h1>', 404);
        return $this->view('payment.sandbox', ['title' => 'درگاه آزمایشی پرداخت', 'order' => $order, 'transaction' => $transaction]);
    }

    public function sandboxSubmit(Request $request, string $authority): Response
    {
        $service = new PaymentService();
        $transaction = $service->transaction($authority);
        if ($transaction === null || $transaction['gateway'] !== 'iranian-sandbox') return Response::html('<h1>404 - Transaction Not Found</h1>', 404);
        $status = $request->input('action') === 'pay' ? 'OK' : 'NOK';
        return $this->redirect('/payment/callback?Authority=' . rawurlencode($authority) . '&Status=' . $status);
    }

    public function result(Request $request): Response
    {
        $user = (new AuthService())->user() ?? [];
        $orderId = (string) $request->query('order', '');
        $order = $orderId !== '' ? (new PaymentService())->order($orderId) : null;
        if ($order !== null && $order['user_id'] !== ($user['id'] ?? null)) $order = null;
        return $this->view('payment.result', ['title' => 'نتیجه پرداخت', 'order' => $order, 'successful' => $order !== null && $order['status'] === 'paid', 'paymentState' => (string) $request->query('payment', '')]);
    }

    public function orders(Request $request): Response
    {
        $user = (new AuthService())->user() ?? [];
        return $this->view('user.orders', ['title' => 'سفارش‌های من | Mentoris', 'user' => $user, 'orders' => (new PaymentService())->userOrders($user['id'])]);
    }

    public function order(Request $request, string $id): Response
    {
        $user = (new AuthService())->user() ?? [];
        $service = new PaymentService();
        $order = $service->order($id);
        if ($order === null || $order['user_id'] !== $user['id']) return Response::html('<h1>404 - Order Not Found</h1>', 404);
        return $this->view('user.order-details', ['title' => 'سفارش ' . $order['number'], 'user' => $user, 'order' => $order, 'transactions' => $service->orderTransactions($id)]);
    }

    private function checkoutView(array $course, array $user, array $errors = []): Response
    {
        return $this->view('payment.checkout', ['title' => 'تکمیل ثبت‌نام ' . $course['title'], 'description' => 'مرور سفارش و ورود امن به درگاه پرداخت.', 'course' => $course, 'user' => $user, 'errors' => $errors]);
    }
}

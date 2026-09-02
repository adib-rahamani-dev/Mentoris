<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Security;
use App\Payments\IranianGateway;
use App\Payments\PaymentGatewayInterface;
use App\Payments\SandboxIranianGateway;
use App\Repositories\CommerceRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class PaymentService
{
    private PaymentGatewayInterface $gateway;
    private CommerceRepository $commerce;
    private UserRepository $users;

    public function __construct(?PaymentGatewayInterface $gateway = null, ?CommerceRepository $commerce = null, ?UserRepository $users = null)
    {
        $this->gateway = $gateway ?? self::configuredGateway();
        $this->commerce = $commerce ?? new CommerceRepository();
        $this->users = $users ?? new UserRepository();
    }

    public function checkoutCourse(array $course, array $user): array
    {
        if (!$course['can_enroll']) throw new RuntimeException('این دوره در حال حاضر قابل ثبت‌نام نیست.');
        $order = $this->commerce->createCourseOrder(['user_id' => $user['id'], 'customer_name' => $user['name'], 'customer_email' => $user['email'], 'item_id' => $course['slug'], 'item_title' => $course['title'], 'amount' => $course['price_amount'], 'currency' => $course['currency']], (int) $course['available']);
        if ($order['amount'] === 0) {
            $transaction = $this->commerce->createTransaction(['order_id' => $order['id'], 'gateway' => 'free', 'authority' => 'free_' . Security::randomToken(16), 'amount' => 0, 'currency' => $order['currency'], 'status' => 'initiated']);
            $order = $this->commerce->finalizePayment($order['id'], $transaction['id'], 'FREE-' . strtoupper(substr($order['id'], 0, 10)));
            return ['order' => $order, 'transaction' => $transaction, 'redirect_url' => '/payment/result?order=' . $order['id']];
        }
        $callback = rtrim((string) env('APP_URL', 'http://mentoris.test'), '/') . '/payment/callback';
        $request = $this->gateway->request($order, $callback);
        $transaction = $this->commerce->createTransaction(['order_id' => $order['id'], 'gateway' => $this->gateway->name(), 'authority' => $request->authority ?? 'failed_' . Security::randomToken(12), 'amount' => $order['amount'], 'currency' => $order['currency'], 'status' => $request->successful ? 'initiated' : 'failed', 'message' => $request->message, 'gateway_response' => $request->raw]);
        if (!$request->successful || $request->redirectUrl === null) {
            $this->commerce->markFailed($order['id'], $transaction['id'], 'failed', $request->message ?? 'ایجاد پرداخت ناموفق بود.', $request->raw);
            throw new RuntimeException($request->message ?? 'امکان اتصال به درگاه وجود ندارد.');
        }
        return ['order' => $order, 'transaction' => $transaction, 'redirect_url' => $request->redirectUrl];
    }

    public function verify(string $authority, string $gatewayStatus): array
    {
        $transaction = $this->commerce->findTransactionByAuthority($authority);
        if ($transaction === null) throw new RuntimeException('تراکنش پیدا نشد.');
        $order = $this->commerce->findOrder($transaction['order_id']);
        if ($order === null) throw new RuntimeException('سفارش پیدا نشد.');
        if ($order['status'] === 'paid') { return ['successful' => true, 'order' => $order, 'transaction' => $transaction, 'message' => 'این پرداخت قبلاً تأیید شده است.']; }
        if (strtotime($order['expires_at'] . ' UTC') < time()) {
            $this->commerce->markFailed($order['id'], $transaction['id'], 'expired', 'مهلت پرداخت سفارش پایان یافته است.');
            return ['successful' => false, 'order' => $this->commerce->findOrder($order['id']), 'transaction' => $transaction, 'message' => 'مهلت پرداخت سفارش پایان یافته است.'];
        }
        if (strtoupper($gatewayStatus) !== 'OK') {
            $this->commerce->markFailed($order['id'], $transaction['id'], 'canceled', 'پرداخت توسط کاربر لغو شد.');
            return ['successful' => false, 'order' => $this->commerce->findOrder($order['id']), 'transaction' => $transaction, 'message' => 'پرداخت لغو شد.'];
        }
        $verification = $this->gatewayForTransaction($transaction)->verify($transaction, $order);
        if (!$verification->successful) {
            $this->commerce->markFailed($order['id'], $transaction['id'], 'failed', $verification->message ?? 'تأیید پرداخت ناموفق بود.', $verification->raw);
            return ['successful' => false, 'order' => $this->commerce->findOrder($order['id']), 'transaction' => $transaction, 'message' => $verification->message ?? 'پرداخت تأیید نشد.'];
        }
        $order = $this->commerce->finalizePayment($order['id'], $transaction['id'], $verification->referenceId ?? '', $verification->raw);
        return ['successful' => true, 'order' => $order, 'transaction' => $this->commerce->findTransactionByAuthority($authority), 'message' => 'پرداخت با موفقیت تأیید شد.'];
    }

    public function order(string $id): ?array { return $this->commerce->findOrder($id); }
    public function transaction(string $authority): ?array { return $this->commerce->findTransactionByAuthority($authority); }
    public function userOrders(string $userId): array { return array_reverse($this->commerce->ordersForUser($userId)); }
    public function orderTransactions(string $orderId): array { return $this->commerce->transactionsForOrder($orderId); }

    private static function configuredGateway(): PaymentGatewayInterface
    {
        if (env('PAYMENT_GATEWAY', 'sandbox') === 'iranian') return self::iranianGateway();
        return new SandboxIranianGateway();
    }

    private function gatewayForTransaction(array $transaction): PaymentGatewayInterface
    {
        return $transaction['gateway'] === 'iranian-sandbox' ? new SandboxIranianGateway() : self::iranianGateway();
    }

    private static function iranianGateway(): IranianGateway
    {
        return new IranianGateway((string) env('IRANIAN_GATEWAY_MERCHANT_ID', ''), (string) env('IRANIAN_GATEWAY_REQUEST_URL', ''), (string) env('IRANIAN_GATEWAY_VERIFY_URL', ''), (string) env('IRANIAN_GATEWAY_START_URL', ''), (int) env('IRANIAN_GATEWAY_AMOUNT_MULTIPLIER', 10));
    }
}

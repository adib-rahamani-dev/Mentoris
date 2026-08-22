<?php

declare(strict_types=1);

namespace App\Payments;

use App\Core\Security;

final class SandboxIranianGateway implements PaymentGatewayInterface
{
    public function name(): string { return 'iranian-sandbox'; }

    public function request(array $order, string $callbackUrl): PaymentRequestResult
    {
        $authority = 'sandbox_' . Security::randomToken(20);
        return new PaymentRequestResult(true, $authority, '/payment/sandbox/' . $authority, null, ['mode' => 'sandbox']);
    }

    public function verify(array $transaction, array $order): PaymentVerificationResult
    {
        $authority = (string) ($transaction['authority'] ?? '');
        if (!str_starts_with($authority, 'sandbox_')) return new PaymentVerificationResult(false, null, 'شناسه پرداخت آزمایشی معتبر نیست.');
        return new PaymentVerificationResult(true, 'SBX-' . strtoupper(substr(hash('sha256', $authority . $order['id']), 0, 12)), 'پرداخت آزمایشی تأیید شد.', ['mode' => 'sandbox']);
    }
}

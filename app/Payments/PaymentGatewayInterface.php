<?php

declare(strict_types=1);

namespace App\Payments;

interface PaymentGatewayInterface
{
    public function name(): string;
    public function request(array $order, string $callbackUrl): PaymentRequestResult;
    public function verify(array $transaction, array $order): PaymentVerificationResult;
}

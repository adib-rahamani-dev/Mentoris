<?php

declare(strict_types=1);

namespace App\Payments;

final class PaymentVerificationResult
{
    public function __construct(public readonly bool $successful, public readonly ?string $referenceId = null, public readonly ?string $message = null, public readonly array $raw = []) {}
}

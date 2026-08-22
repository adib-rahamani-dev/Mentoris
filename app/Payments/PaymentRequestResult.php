<?php

declare(strict_types=1);

namespace App\Payments;

final class PaymentRequestResult
{
    public function __construct(public readonly bool $successful, public readonly ?string $authority = null, public readonly ?string $redirectUrl = null, public readonly ?string $message = null, public readonly array $raw = []) {}
}

<?php

declare(strict_types=1);

namespace App\Payments;

final class IranianGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $merchantId,
        private readonly string $requestUrl,
        private readonly string $verifyUrl,
        private readonly string $startUrl,
        private readonly int $amountMultiplier = 10,
    ) {}

    public function name(): string { return 'iranian'; }

    public function request(array $order, string $callbackUrl): PaymentRequestResult
    {
        if ($this->merchantId === '' || $this->requestUrl === '' || $this->verifyUrl === '' || $this->startUrl === '') return new PaymentRequestResult(false, message: 'تنظیمات درگاه پرداخت کامل نیست.');
        $payload = ['merchant_id' => $this->merchantId, 'amount' => ((int) $order['amount']) * $this->amountMultiplier, 'callback_url' => $callbackUrl, 'description' => 'Mentoris order ' . $order['number'], 'metadata' => ['email' => $order['customer_email'] ?? null]];
        $response = $this->postJson($this->requestUrl, $payload);
        $authority = $response['data']['authority'] ?? null;
        if (!is_string($authority) || $authority === '') return new PaymentRequestResult(false, message: (string) ($response['errors']['message'] ?? 'درخواست پرداخت پذیرفته نشد.'), raw: $response);
        return new PaymentRequestResult(true, $authority, rtrim($this->startUrl, '/') . '/' . rawurlencode($authority), raw: $response);
    }

    public function verify(array $transaction, array $order): PaymentVerificationResult
    {
        $response = $this->postJson($this->verifyUrl, ['merchant_id' => $this->merchantId, 'amount' => ((int) $order['amount']) * $this->amountMultiplier, 'authority' => $transaction['authority']]);
        $code = (int) ($response['data']['code'] ?? 0);
        if (!in_array($code, [100, 101], true)) return new PaymentVerificationResult(false, null, (string) ($response['errors']['message'] ?? 'تراکنش توسط درگاه تأیید نشد.'), $response);
        return new PaymentVerificationResult(true, (string) ($response['data']['ref_id'] ?? ''), 'تراکنش تأیید شد.', $response);
    }

    private function postJson(string $url, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'], CURLOPT_POSTFIELDS => $body]);
            $result = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            if ($result === false) return ['errors' => ['message' => $error ?: 'خطا در ارتباط با درگاه.']];
        } else {
            $context = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 20, 'ignore_errors' => true, 'header' => "Content-Type: application/json\r\nAccept: application/json\r\n", 'content' => $body]]);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) return ['errors' => ['message' => 'خطا در ارتباط با درگاه.']];
        }
        $decoded = json_decode((string) $result, true);
        return is_array($decoded) ? $decoded : ['errors' => ['message' => 'پاسخ نامعتبر از درگاه.']];
    }
}

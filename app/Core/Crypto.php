<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Crypto
{
    public static function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, 'mentoris-session-v1');
        if (!is_string($ciphertext) || strlen($tag) !== 16) throw new RuntimeException('Session encryption failed.');
        return "MTR1" . $iv . $tag . $ciphertext;
    }

    public static function decrypt(string $payload): ?string
    {
        if (!str_starts_with($payload, 'MTR1') || strlen($payload) < 32) return null;
        $plaintext = openssl_decrypt(substr($payload, 32), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($payload, 4, 12), substr($payload, 16, 16), 'mentoris-session-v1');
        return is_string($plaintext) ? $plaintext : null;
    }

    public static function keyedHash(string $value): string
    {
        return hash_hmac('sha256', $value, self::key());
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    private static function key(): string
    {
        $configured = trim((string) env('APP_KEY', ''));
        if (str_starts_with($configured, 'base64:')) $configured = (string) base64_decode(substr($configured, 7), true);
        if (strlen($configured) < 32) throw new RuntimeException('APP_KEY must contain at least 32 random bytes. Run: php bin/console key:generate');
        return hash('sha256', $configured, true);
    }
}

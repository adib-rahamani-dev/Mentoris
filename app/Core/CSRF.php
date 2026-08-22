<?php

declare(strict_types=1);

namespace App\Core;

final class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session = new Session())
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = Security::randomToken();
            $this->session->put(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public function regenerate(): string
    {
        $token = Security::randomToken();
        $this->session->put(self::SESSION_KEY, $token);
        return $token;
    }

    public function validate(?string $token): bool
    {
        $stored = $this->session->get(self::SESSION_KEY);
        return is_string($token) && is_string($stored) && $token !== '' && hash_equals($stored, $token);
    }

    public function field(): string
    {
        return '<input type="hidden" name="_token" value="' . Security::escape($this->token()) . '">';
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $handlerRegistered = false;

    public function __construct(private readonly string $name = 'mentoris_session')
    {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!headers_sent()) {
            $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
            $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.cookie_httponly', '1');
            session_name($this->name);
            session_set_cookie_params([
                'httponly' => true,
                'secure' => $isSecure,
                'samesite' => 'Lax',
                'path' => '/',
            ]);
            if (!self::$handlerRegistered && env('SESSION_DRIVER', 'files') === 'database') {
                $lifetime = max(300, (int) env('SESSION_LIFETIME', 120) * 60);
                session_set_save_handler(new DatabaseSessionHandler(Database::connection(), $lifetime), true);
                self::$handlerRegistered = true;
            }
        }
        if (!session_start()) {
            throw new \RuntimeException('Secure session could not be started.');
        }

        $now = time();
        $idleLimit = max(300, (int) env('SESSION_IDLE_TIMEOUT', 7200));
        $lastActivity = (int) ($_SESSION['_meta.last_activity'] ?? $now);
        if ($now - $lastActivity > $idleLimit) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_meta.last_activity'] = $now;
        $rotatedAt = (int) ($_SESSION['_meta.rotated_at'] ?? 0);
        if ($rotatedAt === 0 || $now - $rotatedAt >= max(300, (int) env('SESSION_ROTATE_INTERVAL', 900))) {
            session_regenerate_id(true);
            $_SESSION['_meta.rotated_at'] = $now;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $_SESSION);
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->put('_flash.new.' . $key, $value);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function invalidate(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000, 'path' => $params['path'], 'domain' => $params['domain'],
                'secure' => $params['secure'], 'httponly' => true, 'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}

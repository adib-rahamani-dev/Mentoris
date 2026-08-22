<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $routeParams = [];

    public function __construct(
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $files = [],
        private readonly array $cookies = [],
        private readonly array $server = [],
        private readonly string $rawBody = ''
    ) {
    }

    public static function capture(): self
    {
        $rawBody = (string) file_get_contents('php://input');
        $body = $_POST;
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json') && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($_GET, $body, $_FILES, $_COOKIE, $_SERVER, $rawBody);
    }

    public function method(): string
    {
        $method = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
        $override = $this->body['_method'] ?? $this->header('X-HTTP-Method-Override');

        return is_string($override) && $override !== '' ? strtoupper($override) : $method;
    }

    public function uri(): string
    {
        $uri = parse_url((string) ($this->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($uri), '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $input = array_replace($this->query, $this->body);

        return $key === null ? $input : ($input[$key] ?? $default);
    }

    public function only(array $keys): array
    {
        return array_intersect_key((array) $this->input(), array_flip($keys));
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (strtolower($name) === 'content-type') {
            $key = 'CONTENT_TYPE';
        }

        return $this->server[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $authorization = (string) $this->header('Authorization', '');
        return preg_match('/^Bearer\s+(.+)$/i', $authorization, $match) ? $match[1] : null;
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function isJson(): bool
    {
        return str_contains(strtolower((string) $this->header('Content-Type', '')), 'application/json');
    }

    public function expectsJson(): bool
    {
        return $this->isJson() || str_contains(strtolower((string) $this->header('Accept', '')), 'application/json');
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function raw(): string
    {
        return $this->rawBody;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->routeParams : ($this->routeParams[$key] ?? $default);
    }
}

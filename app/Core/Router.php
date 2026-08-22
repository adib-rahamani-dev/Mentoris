<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;

final class Router
{
    private array $routes = [];
    private array $aliases = [];
    private array $groupStack = [];

    public function alias(string $name, string|callable $middleware): self
    {
        $this->aliases[$name] = $middleware;
        return $this;
    }

    public function add(array|string $methods, string $uri, callable|array|string $handler, array $middleware = []): self
    {
        $prefix = implode('', array_column($this->groupStack, 'prefix'));
        $groupMiddleware = array_merge([], ...array_column($this->groupStack, 'middleware'));
        $path = $this->normalize($prefix . '/' . ltrim($uri, '/'));

        $this->routes[] = [
            'methods' => array_map('strtoupper', (array) $methods),
            'uri' => $path,
            'pattern' => $this->compile($path),
            'handler' => $handler,
            'middleware' => [...$groupMiddleware, ...$middleware],
        ];
        return $this;
    }

    public function get(string $uri, callable|array|string $handler, array $middleware = []): self { return $this->add('GET', $uri, $handler, $middleware); }
    public function post(string $uri, callable|array|string $handler, array $middleware = []): self { return $this->add('POST', $uri, $handler, $middleware); }
    public function put(string $uri, callable|array|string $handler, array $middleware = []): self { return $this->add('PUT', $uri, $handler, $middleware); }
    public function patch(string $uri, callable|array|string $handler, array $middleware = []): self { return $this->add('PATCH', $uri, $handler, $middleware); }
    public function delete(string $uri, callable|array|string $handler, array $middleware = []): self { return $this->add('DELETE', $uri, $handler, $middleware); }
    public function any(string $uri, callable|array|string $handler, array $middleware = []): self
    {
        return $this->add(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $routes): void
    {
        $this->groupStack[] = ['prefix' => '/' . trim($prefix, '/'), 'middleware' => $middleware];
        try {
            $routes($this);
        } finally {
            array_pop($this->groupStack);
        }
    }

    public function dispatch(Request $request): Response
    {
        $methodNotAllowed = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->uri(), $matches)) {
                continue;
            }
            if (!in_array($request->method(), $route['methods'], true)) {
                $methodNotAllowed = true;
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setRouteParams($params);
            $destination = fn (Request $incoming): Response => $this->toResponse(
                $this->invokeHandler($route['handler'], $incoming, $params)
            );

            return $this->pipeline($route['middleware'], $destination)($request);
        }

        return $methodNotAllowed
            ? Response::json(['message' => 'Method Not Allowed'], 405)->withHeader('Allow', $this->allowedMethods($request->uri()))
            : ($request->expectsJson()
                ? Response::json(['message' => 'Not Found'], 404)
                : Response::html('<h1>404 - Not Found</h1>', 404));
    }

    public function routes(): array
    {
        return $this->routes;
    }

    private function pipeline(array $middleware, callable $destination): callable
    {
        return array_reduce(
            array_reverse($middleware),
            function (callable $next, mixed $definition): callable {
                return function (Request $request) use ($definition, $next): Response {
                    $instance = $this->resolveMiddleware($definition);
                    if ($instance instanceof MiddlewareInterface) {
                        return $instance->handle($request, $next);
                    }
                    if (is_callable($instance)) {
                        return $instance($request, $next);
                    }
                    throw new \RuntimeException('Invalid middleware definition.');
                };
            },
            $destination
        );
    }

    private function resolveMiddleware(mixed $definition): mixed
    {
        if (!is_string($definition)) {
            return $definition;
        }

        [$name, $parameterString] = array_pad(explode(':', $definition, 2), 2, '');
        $target = $this->aliases[$name] ?? $name;
        $parameters = $parameterString === '' ? [] : array_map(
            static fn (string $value): int|string => ctype_digit($value) ? (int) $value : $value,
            explode(',', $parameterString)
        );

        if (is_string($target) && class_exists($target)) {
            return new $target(...$parameters);
        }
        return $target;
    }

    private function invokeHandler(callable|array|string $handler, Request $request, array $params): mixed
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler, 2);
            $handler = [new $controller(), $method];
        } elseif (is_array($handler) && is_string($handler[0] ?? null)) {
            $handler = [new $handler[0](), $handler[1]];
        }

        if (!is_callable($handler)) {
            throw new \RuntimeException('Route handler is not callable.');
        }

        return $handler($request, ...array_values($params));
    }

    private function toResponse(mixed $result): Response
    {
        return match (true) {
            $result instanceof Response => $result,
            is_array($result), is_object($result) => Response::json($result),
            $result === null => Response::noContent(),
            default => Response::html((string) $result),
        };
    }

    private function normalize(string $uri): string
    {
        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    private function compile(string $uri): string
    {
        $parts = preg_split('/(\{[A-Za-z_][A-Za-z0-9_]*(?::[^}]+)?\})/', $uri, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pattern = '';
        foreach ($parts as $part) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)(?::([^}]+))?\}$/', $part, $match)) {
                $pattern .= '(?P<' . $match[1] . '>' . ($match[2] ?? '[^/]+') . ')';
            } else {
                $pattern .= preg_quote($part, '#');
            }
        }
        return '#^' . $pattern . '$#u';
    }

    private function allowedMethods(string $uri): string
    {
        $methods = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $uri)) {
                $methods = [...$methods, ...$route['methods']];
            }
        }
        return implode(', ', array_unique($methods));
    }
}

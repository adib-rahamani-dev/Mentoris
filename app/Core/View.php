<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = $this->evaluate($this->resolve($view), $data);
        if ($layout === null) {
            return $content;
        }

        return $this->evaluate($this->resolve($layout), [...$data, 'content' => $content]);
    }

    public function exists(string $view): bool
    {
        return is_file($this->resolve($view));
    }

    private function resolve(string $view): string
    {
        $relative = str_replace(['.', '\\'], ['/', '/'], trim($view));
        $path = rtrim($this->basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relative, '/\\') . '.php';
        $base = realpath($this->basePath);
        $directory = realpath(dirname($path));

        if ($base === false || $directory === false || !str_starts_with($directory, $base) || !is_file($path)) {
            throw new \RuntimeException("View [{$view}] was not found.");
        }
        return $path;
    }

    private function evaluate(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $path;
            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}

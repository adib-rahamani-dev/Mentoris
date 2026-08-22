<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    public function __construct(protected ?View $viewEngine = null)
    {
        $this->viewEngine ??= new View(defined('VIEW_PATH') ? VIEW_PATH : dirname(__DIR__) . '/Views');
    }

    protected function view(string $view, array $data = [], ?string $layout = 'layouts.main'): Response
    {
        return Response::html($this->viewEngine->render($view, $data, $layout));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}

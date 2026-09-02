<?php

declare(strict_types=1);

// Router for PHP's local development server. Existing public assets are served
// directly; every other request goes through Mentoris' front controller.
$uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$requested = realpath(__DIR__ . rawurldecode($uri));
$public = realpath(__DIR__);

if ($uri !== '/' && $requested !== false && $public !== false && str_starts_with($requested, $public . DIRECTORY_SEPARATOR) && is_file($requested)) {
    return false;
}

require __DIR__ . '/index.php';

<?php

declare(strict_types=1);

use App\Services\PublicContentService;

require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void {
    if (!$condition) {
        throw new RuntimeException("FAILED: {$message}");
    }
    $passed++;
};

$lines = PublicContentService::academyLines();
$programs = PublicContentService::programs();
$specializations = PublicContentService::specializations();

$assert(count($lines) === 7, 'Academy has exactly seven active lines.');
$assert($programs === [], 'Unapproved programs are not published.');
$assert($specializations === [], 'Unapproved specializations are not published.');

foreach ($lines as $line) {
    $assert($line['specializations'] === [], "Line {$line['slug']} exposes no fictional specializations.");
    $assert(PublicContentService::academyLine($line['slug']) !== null, "Line {$line['slug']} resolves by slug.");
}

$assert(PublicContentService::program('does-not-exist') === null, 'Unpublished program does not resolve.');

echo "Academy Content: {$passed} assertions passed." . PHP_EOL;

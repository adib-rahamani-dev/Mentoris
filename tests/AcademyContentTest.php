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
$assert(count($programs) === 7, 'Every academy line has an initial program.');
$assert(count($specializations) === 21, 'Academy has three specializations per line.');

foreach ($lines as $line) {
    $assert(count($line['specializations']) === 3, "Line {$line['slug']} has three specializations.");
    $assert(PublicContentService::academyLine($line['slug']) !== null, "Line {$line['slug']} resolves by slug.");
}

$required = ['title', 'description', 'target_audience', 'objectives', 'related_courses', 'related_events', 'related_mentors'];
foreach ($programs as $program) {
    $hydrated = PublicContentService::program($program['slug']);
    $assert($hydrated !== null, "Program {$program['slug']} resolves by slug.");
    foreach ($required as $field) {
        $assert(isset($hydrated[$field]) && $hydrated[$field] !== [], "Program {$program['slug']} has {$field}.");
    }
    $assert($hydrated['line']['slug'] === $program['line_slug'], "Program {$program['slug']} belongs to its academy line.");
}

echo "Academy Content: {$passed} assertions passed." . PHP_EOL;

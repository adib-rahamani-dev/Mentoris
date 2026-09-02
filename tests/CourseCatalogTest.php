<?php

declare(strict_types=1);

use App\Services\PublicContentService;

require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void { if (!$condition) throw new RuntimeException("FAILED: {$message}"); $passed++; };

$assert(PublicContentService::courses() === [], 'No unapproved or fictional course is published.');
$assert(count(PublicContentService::courseCategories()) === 7, 'The seven academy fields remain available as taxonomy.');
$assert(array_keys(PublicContentService::courseStatusLabels()) === ['active', 'coming-soon', 'full', 'completed'], 'Course lifecycle statuses remain defined.');
$assert(PublicContentService::course('schema-therapy') === null, 'Removed fictional course cannot resolve.');
$view = file_get_contents(BASE_PATH . '/app/Views/pages/courses.php');
$assert(str_contains($view, 'content-empty.php'), 'Empty course catalog renders the shared coming-soon state.');

echo "Course Catalog: {$passed} assertions passed." . PHP_EOL;

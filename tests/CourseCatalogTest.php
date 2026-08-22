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

$courses = PublicContentService::courses();
$categories = PublicContentService::courseCategories();
$statuses = PublicContentService::courseStatusLabels();
$required = ['title', 'subtitle', 'description', 'audience', 'instructor_slug', 'curriculum', 'duration', 'schedule', 'type', 'price', 'price_amount', 'currency', 'capacity', 'status', 'faq', 'certificate'];

$assert(count($courses) === 14, 'Course catalog exposes fourteen initial courses.');
$assert(count($categories) === 7, 'Courses cover all seven academy categories.');
$assert(array_keys($statuses) === ['active', 'coming-soon', 'full', 'completed'], 'All required course statuses are defined.');

$seenStatuses = [];
$seenCategories = [];
foreach ($courses as $course) {
    foreach ($required as $field) {
        $assert(isset($course[$field]) && $course[$field] !== [], "Course {$course['slug']} has {$field}.");
    }
    $assert(isset($categories[$course['category_slug']]), "Course {$course['slug']} has a known category.");
    $assert(isset($statuses[$course['status']]), "Course {$course['slug']} has a known status.");
    $assert($course['capacity'] > 0, "Course {$course['slug']} has positive capacity.");
    $assert($course['enrolled'] >= 0 && $course['enrolled'] <= $course['capacity'], "Course {$course['slug']} enrollment fits capacity.");
    $assert(count($course['audience']) >= 3, "Course {$course['slug']} has a useful audience definition.");
    $assert(count($course['curriculum']) >= 4, "Course {$course['slug']} has a complete curriculum.");
    $assert(count($course['faq']) >= 3, "Course {$course['slug']} has FAQs.");

    $hydrated = PublicContentService::course($course['slug']);
    $assert($hydrated !== null, "Course {$course['slug']} resolves by slug.");
    $assert($hydrated['instructor'] !== null, "Course {$course['slug']} resolves its instructor.");
    $assert($hydrated['instructor_bio'] !== '', "Course {$course['slug']} has an instructor bio.");
    $assert($hydrated['line'] !== null, "Course {$course['slug']} resolves its academy line.");
    $assert($hydrated['available'] === $course['capacity'] - $course['enrolled'], "Course {$course['slug']} calculates available seats.");
    $assert($hydrated['can_enroll'] === ($course['status'] === 'active' && $hydrated['available'] > 0), "Course {$course['slug']} calculates enrollment eligibility.");
    $seenStatuses[$course['status']] = true;
    $seenCategories[$course['category_slug']] = true;
}

foreach (array_keys($statuses) as $status) {
    $assert(isset($seenStatuses[$status]), "Catalog represents {$status} status.");
}
foreach (array_keys($categories) as $category) {
    $assert(isset($seenCategories[$category]), "Catalog represents {$category} category.");
}
$assert(PublicContentService::course('does-not-exist') === null, 'Unknown course does not resolve.');

echo "Course Catalog: {$passed} assertions passed." . PHP_EOL;

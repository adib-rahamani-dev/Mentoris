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

$events = PublicContentService::events();
$statuses = PublicContentService::eventStatusLabels();
$modes = PublicContentService::eventModeLabels();
$required = ['slug', 'title', 'description', 'short_description', 'date', 'date_iso', 'time', 'instructor_slug', 'location', 'mode', 'status', 'capacity', 'registered'];

$assert(count($events) === 8, 'MVP includes eight representative events.');
$assert(array_keys($statuses) === ['upcoming', 'registration-open', 'full', 'completed', 'canceled'], 'All five required statuses are defined.');
$assert(array_keys($modes) === ['online', 'offline', 'hybrid'], 'All required event modes are defined.');

$seenStatuses = [];
foreach ($events as $event) {
    foreach ($required as $field) {
        $assert(array_key_exists($field, $event) && $event[$field] !== '', "Event {$event['slug']} has {$field}.");
    }
    $assert($event['capacity'] > 0, "Event {$event['slug']} has a positive capacity.");
    $assert($event['registered'] >= 0 && $event['registered'] <= $event['capacity'], "Event {$event['slug']} has valid registration numbers.");
    $assert(isset($statuses[$event['status']]), "Event {$event['slug']} uses a known status.");
    $assert(isset($modes[$event['mode']]), "Event {$event['slug']} uses a known mode.");

    $hydrated = PublicContentService::event($event['slug']);
    $assert($hydrated !== null, "Event {$event['slug']} resolves by slug.");
    $assert($hydrated['instructor'] !== null, "Event {$event['slug']} resolves its instructor.");
    $assert($hydrated['line'] !== null, "Event {$event['slug']} resolves its academy line.");
    $assert($hydrated['available'] === $event['capacity'] - $event['registered'], "Event {$event['slug']} calculates availability.");
    $assert($hydrated['can_register'] === ($event['status'] === 'registration-open' && $hydrated['available'] > 0), "Event {$event['slug']} calculates registration eligibility.");
    $seenStatuses[$event['status']] = true;
}

foreach (array_keys($statuses) as $status) {
    $assert(isset($seenStatuses[$status]), "Catalog represents {$status} status.");
}

$community = PublicContentService::community();
$assert(count($community['benefits']) === 4, 'Community MVP exposes four membership benefits.');
$assert(count($community['rules']) === 5, 'Community MVP exposes five community rules.');
$assert($community['member_count'] !== '', 'Community membership social proof is present.');
$assert(PublicContentService::event('does-not-exist') === null, 'Unknown event does not resolve.');

echo "Events & Community: {$passed} assertions passed." . PHP_EOL;

<?php

declare(strict_types=1);

use App\Services\PublicContentService;

require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void { if (!$condition) throw new RuntimeException("FAILED: {$message}"); $passed++; };

$events = PublicContentService::events();
$assert(count($events) === 1, 'Only the approved Therapists Circle event is published.');
$event = $events[0];
$assert($event['slug'] === 'therapists-circle-tabriz', 'Approved event has a stable slug.');
$assert($event['date_iso'] === '2026-09-18' && $event['location'] === 'تبریز', 'Approved date and location are preserved.');
$assert($event['external_registration_url'] === 'https://forms.gle/BKUrF5Pddj2r7AyT8', 'Approved registration form is preserved exactly.');
$assert($event['capacity'] === 0 && $event['capacity_label'] === 'ظرفیت محدود', 'Unknown numeric capacity is never fabricated.');
$hydrated = PublicContentService::event($event['slug']);
$assert($hydrated !== null && $hydrated['can_register'], 'Approved event resolves and accepts initial registration.');
$assert($hydrated['instructor']['slug'] === 'maryam-haghani', 'Founder identity resolves for the event.');
$assert($hydrated['line']['slug'] === 'therapist-development', 'Event resolves its academy line.');
$assert($hydrated['available'] === null, 'Unknown seat count remains unknown.');
$assert(PublicContentService::event('does-not-exist') === null, 'Unknown event does not resolve.');
$community = PublicContentService::community();
$assert($community['member_count'] === null && $community['benefits'] === [] && $community['rules'] === [], 'Unverified community claims are not published.');

echo "Events & Community: {$passed} assertions passed." . PHP_EOL;

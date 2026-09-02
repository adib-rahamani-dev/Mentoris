<?php

declare(strict_types=1);

use App\Core\Authorization;
use App\Core\Translator;
use App\Services\PublicContentService;
use App\Services\SeoService;

require dirname(__DIR__) . '/bootstrap/constants.php';
require dirname(__DIR__) . '/bootstrap/autoload.php';

$passed = 0;
$assert = static function (bool $condition, string $message) use (&$passed): void { if (!$condition) throw new RuntimeException("FAILED: {$message}"); $passed++; };

foreach (['fa'=>'دکتر مریم حقانی','ar'=>'الدكتورة مريم حقاني','ku'=>'د. مەریەم حەقانی','en'=>'Dr. Maryam Haghani'] as $locale=>$name) {
    Translator::boot(['lang'=>$locale], ['mentoris_locale'=>$locale], []);
    $assert(Translator::locale() === $locale, "Locale {$locale} boots.");
    $assert(PublicContentService::founder()['name'] === $name, "Founder biography is localized for {$locale}.");
    $assert(Translator::get('empty.title') !== 'empty.title', "Coming-soon state is translated for {$locale}.");
}
$assert(Translator::direction() === 'ltr', 'English uses LTR direction.');
Translator::boot(['lang'=>'fa'], ['mentoris_locale'=>'fa'], []);
$assert(Translator::direction() === 'rtl', 'Persian uses RTL direction.');
Translator::boot(['lang'=>'ckb'], [], []);
$assert(Translator::locale() === 'ku' && Translator::htmlLocale() === 'ckb', 'Sorani Kurdish uses the correct ckb language tag.');
Translator::boot(['lang'=>'fa'], ['mentoris_locale'=>'fa'], []);

$super = ['account_role'=>'super_admin','status'=>'active'];
$editor = ['account_role'=>'editor','status'=>'active'];
$support = ['account_role'=>'support','status'=>'active'];
$suspended = ['account_role'=>'admin','status'=>'suspended'];
$assert(Authorization::can($super, 'settings.manage'), 'Super admin receives every permission.');
$assert(Authorization::can($editor, 'content.manage') && !Authorization::can($editor, 'users.manage'), 'Editor permissions are scoped.');
$assert(Authorization::can($support, 'users.view') && !Authorization::can($support, 'users.manage'), 'Support has read-only user access.');
$assert(!Authorization::can($suspended, 'admin.access'), 'Suspended account loses admin access.');

$assert(is_file(BASE_PATH . '/public/assets/images/founder-maryam-haghani-v1.png'), 'Founder portrait asset exists.');
$assert(str_contains(file_get_contents(BASE_PATH . '/app/Views/layouts/main.php'), 'locale_direction()'), 'Public layout responds to locale direction.');
$assert(str_contains(file_get_contents(BASE_PATH . '/public/assets/css/variables.css'), ':root[data-theme="light"]'), 'Light and dark tokens both exist.');
$assert(str_contains(file_get_contents(BASE_PATH . '/routes/admin.php'), "can:users.manage"), 'Admin mutation route is permission protected.');

$_ENV['APP_URL'] = $_SERVER['APP_URL'] = 'https://mentorisacademy.com';
$_SERVER['REQUEST_URI'] = '/founder?lang=fa';
$seo = SeoService::metadata('Founder', 'Founder profile');
$assert($seo['canonical'] === 'https://mentorisacademy.com/founder?lang=fa', 'Canonical URL is stable and localized.');
$assert(in_array('ckb', array_column($seo['alternates'], 'language'), true), 'Kurdish alternate uses the correct ckb hreflang.');
$assert($seo['robots'] === 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1', 'Public pages are indexable.');
$_SERVER['REQUEST_URI'] = '/admin/users';
$assert(str_starts_with(SeoService::metadata('Admin', 'Admin')['robots'], 'noindex'), 'Private panels are excluded from search engines.');
$assert(str_contains(file_get_contents(BASE_PATH . '/public/robots.txt'), 'Sitemap: https://mentorisacademy.com/sitemap.xml'), 'Robots file declares the sitemap.');
$assert(str_contains(file_get_contents(BASE_PATH . '/public/sitemap.xml'), 'therapists-circle-tabriz'), 'Real event is present in the sitemap.');
$assert(json_decode(file_get_contents(BASE_PATH . '/public/manifest.json'), true) !== null, 'Web app manifest is valid JSON.');

echo "Launch Readiness: {$passed} assertions passed." . PHP_EOL;

# Mentoris

Mentoris includes a small custom PHP framework built for the project. Phase 1 provides this request lifecycle:

```text
URL -> Router -> Middleware -> Controller -> View -> Response
```

## Requirements

- PHP 8.1+
- Apache with `mod_rewrite`, or another web server whose document root points to `public/`
- Composer

## Local setup

```bash
composer install
php tests/CoreEngineTest.php
```

When Laragon maps `mentoris.test` to the project root, the root `.htaccess` forwards requests to the public front controller automatically.

Available smoke-test routes:

- `GET /` — Mentoris public homepage
- `GET /about` — mission, vision, story and academy lines
- `GET /founder` — founder story and professional path
- `GET /programs` — searchable and filterable program catalog
- `GET /programs/{slug}` — complete program details and relationships
- `GET /academy` — the seven Mentoris academy lines
- `GET /academy/{slug}` — academy line details and related programs
- `GET /specializations` — all 21 specializations grouped by line
- `GET /specializations/{slug}` — specialization context and next steps
- `GET /courses` — searchable course catalog with category, status and delivery filters
- `GET /courses/{slug}` — complete course details, curriculum, instructor, capacity, FAQ and certificate
- `GET /events` — searchable event catalog with status and delivery-mode filters
- `GET /events/{slug}` — event details, capacity and registration state
- `POST /events/{slug}/register` — CSRF-protected MVP registration request
- `GET /community` — Community landing, benefits, rules and events
- `POST /community/join` — CSRF-protected MVP membership request
- `GET|POST /register` — account registration
- `GET|POST /login` — account login
- `POST /logout` — CSRF-protected logout
- `GET|POST /forgot-password` — request a one-hour password reset token
- `GET|POST /reset-password/{token}` — validate and consume a password reset token
- `GET /dashboard` — protected user overview
- `GET|POST /profile` — protected profile management
- `GET /my-courses` — protected user course area
- `GET /my-events` — protected user event area
- `GET /my-certificates` — protected certificate area
- `GET /notifications` — protected notification center
- `GET|POST /checkout/course/{slug}` — protected checkout and payment initiation
- `GET /payment/callback` — server-side gateway verification callback
- `GET|POST /payment/sandbox/{authority}` — local Iranian gateway simulator
- `GET /payment/result` — protected payment result receipt
- `GET /orders` — protected order history
- `GET /orders/{id}` — protected order and transaction details
- `GET /mentors` — mentors and experts directory
- `GET|POST /contact` — CSRF-protected contact form
- `GET /design-system` — live component and token gallery
- `GET /api/health` — JSON response
- `GET /framework/{name}` — route parameter example

## Registering routes

```php
use App\Core\Request;

$router->get('/courses/{id:\d+}', function (Request $request, string $id): array {
    return ['course_id' => (int) $id];
}, ['auth', 'rate:60,60']);
```

Route middleware aliases are `auth`, `guest`, `csrf`, and `rate`. A POST form protected by CSRF must contain `<?= csrf_field() ?>` or send the token in the `X-CSRF-TOKEN` header.

## Main directories

- `app/Core` — application, router, HTTP, session, view, validation and security primitives
- `app/Middleware` — authentication, guest, CSRF and rate-limiting middleware
- `app/Controllers` — request controllers
- `app/Views` — PHP views and layouts
- `routes` — web, auth, admin and API route registration
- `public` — front controller and public assets
- `tests/CoreEngineTest.php` — executable Core Engine integration test
- `tests/AcademyContentTest.php` — academy, Program and specialization content test
- `tests/EventsCommunityTest.php` — event states, capacity and Community content test
- `tests/CourseCatalogTest.php` — course fields, categories, statuses and relationships test
- `tests/AuthenticationTest.php` — password, profile, reset-token and user-repository security test
- `tests/PaymentEnrollmentTest.php` — order, transaction, capacity, verification and enrollment test
- `tests/ThemeTest.php` — light/dark tokens, persistence and accessibility test

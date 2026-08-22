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

- `GET /` — Controller and View rendering
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

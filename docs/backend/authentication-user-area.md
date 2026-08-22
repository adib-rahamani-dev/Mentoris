# Authentication & User Area

Phase 7 adds registration, login, logout, password recovery, profile editing, and an authenticated user area. Protected pages include Dashboard, My Courses, My Events, My Certificates, Profile, and Notifications.

## Storage and security

The MVP uses `storage/data/users.json`, outside the public document root. The repository uses shared/exclusive file locks for concurrent reads and writes, normalizes emails, rejects duplicate accounts, and never exposes password or reset-token hashes through the public user representation. Passwords use PHP `password_hash()` and are rehashed when needed. Login regenerates the session identifier, session cookies are HTTP-only with SameSite Lax, state-changing requests require CSRF, and authentication endpoints are rate limited.

Reset tokens contain 32 random bytes, are stored only as SHA-256 hashes, expire after one hour, and are invalidated after use. In `APP_ENV=local`, the forgot-password page displays a development preview link. Production must send that link through an email provider and must not expose it in the response.

`USERS_STORAGE_PATH` can optionally override the user-store path. The configured directory must be writable by PHP and remain outside `public/`.

## MVP boundary

The user data model already contains course, event, certificate, and notification collections. New accounts start with empty learning collections and one welcome notification. Course enrollment, event-registration synchronization, certificate PDF generation, email delivery, payments, content progress, lessons, and assessments belong to later LMS/data phases.

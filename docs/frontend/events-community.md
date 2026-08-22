# Events & Community MVP

Phase 5 introduces a dedicated event catalog, event detail and registration flow, plus the first Community landing and membership flow.

## Event model

Each event includes date and time, instructor, location, delivery mode, capacity, registered seats, status, academy line, highlights, and related Programs. Supported statuses are `upcoming`, `registration-open`, `full`, `completed`, and `canceled`; supported modes are `online`, `offline`, and `hybrid`.

Only events with `registration-open` status and remaining capacity accept registration. POST requests use CSRF and rate-limit middleware. The MVP stores a registration marker in the current PHP session to prevent duplicate submissions; it does not yet persist data, reserve paid seats, or send email.

## Community scope

The first Community version includes a landing page, membership benefits, participation rules, related events, and a CSRF-protected join form. Membership requests are session-backed in this phase. Profiles, feeds, messaging, member directories, and social-network features are intentionally outside MVP scope.

# Courses

Phase 6 adds a complete public course catalog at `/courses` and detailed course pages at `/courses/{slug}`.

Each course contains title, subtitle, description, audience, instructor and bio, curriculum, duration, schedule, delivery type, price, capacity, status, FAQ, and certificate information. The initial catalog contains fourteen courses across all seven academy lines.

Supported statuses are `active`, `coming-soon`, `full`, and `completed`. Status controls the detail-page call to action: active courses can request enrollment, upcoming courses can request a notification, full courses can request the waiting list, and completed courses direct visitors back to active options.

The listing provides client-side Persian search plus category, status, and delivery-type filters. Enrollment, payment, seat reservation, and a student panel are intentionally not persisted in this phase; calls to action currently route to the contact flow.

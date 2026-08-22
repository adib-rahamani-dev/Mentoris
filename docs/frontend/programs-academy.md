# Programs and Academy

Phase 4 separates four concepts:

- **Academy Line** — a durable domain of practice and learning.
- **Specialization** — a focused competency area inside an Academy Line.
- **Program** — a multi-stage learning journey with a defined professional outcome.
- **Course** — one learning unit that can belong to one or more Programs.

## Current Academy Lines

1. Research
2. Therapist Development
3. Wellbeing
4. Therapy
5. Assessment
6. Public Mental Health
7. Professional Development

Each line currently has three specializations and one initial Program.

## Program contract

Every Program must define:

- title, subtitle and description
- target audience
- objectives
- duration, level and format
- parent Academy Line
- related Courses
- related Events
- related Mentors

`tests/AcademyContentTest.php` enforces this contract.

## Routes

- `/programs` and `/programs/{slug}`
- `/academy` and `/academy/{slug}`
- `/specializations` and `/specializations/{slug}`

Demo content is currently stored in `PublicContentService`. The method signatures and view contracts are ready to be replaced by repository/database queries in a backend phase.

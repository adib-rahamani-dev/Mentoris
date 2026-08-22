# Mentoris public website

Phase 3 provides a presentation-ready public website built on the Phase 2 design system.

## Page map

- `/` — Hero, mission/vision, academy lines, events, courses, founder, mentors, research/content, community and CTA
- `/about` — story, mission, vision, statistics, values and academy lines
- `/founder` — founder introduction, origin story, quote, timeline and focus areas
- `/programs` — searchable/filterable catalog and guidance CTA
- `/mentors` — expert directory, standards and collaboration CTA
- `/contact` — contact details, validated CSRF-protected form and FAQ
- `/design-system` — the Phase 2 living style guide

## Content model

Shared demo content is centralized in `app/Services/PublicContentService.php`. Replace this service with database-backed repositories in a later phase without changing card components.

## Demo assets

`mentoris-hero-v1.png` and `founder-fictional-v1.png` are AI-generated project assets. The founder identity, name, biography and portrait are explicitly fictional placeholders and must be replaced with approved production information before public launch.

## Contact form

The contact route validates all required fields and is protected by CSRF and rate limiting. Phase 3 acknowledges successful submissions but does not send email or persist messages; connect `MailService` or a database in the relevant backend phase.

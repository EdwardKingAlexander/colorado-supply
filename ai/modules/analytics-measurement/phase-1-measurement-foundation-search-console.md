# Phase 1 — Measurement Foundation and Search Console

## Goal

Create one consent-aware analytics boundary for the Vue/Inertia application and
make the public site ready for Search Console ownership and indexing reports.

## Scope

- Move the hard-coded GA4 measurement ID into non-secret application config.
- Add a small analytics utility with an allowlisted event API that fails closed
  unless current analytics consent is granted and GPC is inactive.
- Define route exclusions for authenticated SAM/federal, admin, payment, and
  other sensitive surfaces.
- Handle Inertia SPA page views exactly once where analytics is permitted.
- Provide a public XML sitemap for stable marketing, repair, category, and
  indexable product pages.
- Provide or update `robots.txt` to advertise the sitemap and exclude private
  application routes.
- Support a non-secret Search Console HTML verification token through config
  only if DNS verification is not used.
- Document the owner-only Search Console setup and sitemap submission steps.

## Expected Files to Create

- `config/analytics.php`
- `resources/js/Utils/analytics.js`
- `app/Http/Controllers/SitemapController.php` or an equivalent focused class
- `resources/views/sitemap.blade.php` or an equivalent XML response builder
- `tests/Feature/Analytics/AnalyticsBootstrapTest.php`
- `tests/Feature/Seo/SitemapTest.php`
- `tests/Feature/Seo/RobotsTest.php`
- `tests/Browser/analytics-consent.spec.js` if Playwright remains available
- `docs/analytics-deployment.md`

## Expected Files to Modify

- `resources/views/partials/analytics.blade.php`
- `resources/views/app.blade.php`
- `resources/js/app.js`
- `resources/js/Utils/consent.js`
- `routes/web.php`
- `public/robots.txt` if present, otherwise create it
- `config/privacy.php` only if the implementation changes disclosed cookie
  behavior; do not change unrelated compliance work

Exact files may be narrowed during implementation after confirming the current
privacy phase has not changed these integration points.

## Tests to Write

- Measurement ID is config-driven and absent configuration disables GA cleanly.
- Denied, missing, stale, revoked, and GPC consent never dispatch analytics.
- Granted current consent permits an allowlisted event.
- Sensitive/excluded routes do not dispatch page views.
- Inertia navigation produces one permitted page view, not duplicates.
- Sitemap XML is valid, canonical, public-only, and company-safe.
- Robots response references the production sitemap and excludes private routes.
- Verification metadata is absent unless explicitly configured.

## Owner Validation

- Create/claim the Search Console property using the existing Google account.
- Prefer domain-level DNS verification; otherwise enter the HTML token directly
  in deployment configuration.
- Submit `/sitemap.xml` and confirm it can be fetched.
- Confirm no paid product or advertising integration was enabled.

## Success Criteria

- All analytics calls pass through one consent/privacy boundary.
- No GA request occurs without current affirmative consent or under GPC.
- Search Console can verify and crawl public pages without exposing private
  routes.
- Targeted PHP and browser tests pass.
- `npm run build`, `php artisan test`, and `vendor/bin/pint --dirty` pass in
  proportion to the phase's touched files.

**STOP. Await phase approval before implementation.**

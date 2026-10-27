---
plan: laravel-13-upgrade
phase: 5
title: Remaining package sweep + full test suite
status: completed
last_updated: 2026-07-09
depends_on: [phase-3, phase-4]
checkpoint: stripe-smoke-test
---

# Phase 5 — Remaining Package Sweep + Full Test Suite

## Goal

Resolve every remaining composer package not individually pre-verified
during planning (`barryvdh/laravel-dompdf`, `chrome-php/chrome`,
`stripe/stripe-php`, `smalot/pdfparser`, `phpoffice/phpword`,
`thiagoalessio/tesseract_ocr`, `symfony/http-client`,
`symfony/mailgun-mailer`, `laravel/breeze`, `laravel/pail`, `laravel/sail`,
`laravel/mcp`, `barryvdh/*` dev tools), and run the full test suite +
storefront smoke test as the final correctness backstop before Phase 6's
deployment prep.

## Steps

1. Run a full `composer update` (no package args this time — Phases 1–4
   already pinned/bumped the packages that needed explicit handling) and
   read the output for any package that fails to resolve or gets held back.
2. For anything held back or failing: check that package's own Laravel 13 /
   PHP 8.3 compatibility (packagist `p2` metadata or the package's GitHub
   releases) and either bump its constraint or, if no compatible version
   exists yet, document it as a blocker in `STATE.md` and stop — do not
   force-resolve with `--with-all-dependencies` or ignore-platform-reqs
   flags without flagging it to the user first.
3. Re-confirm `spatie/laravel-permission` (`^6.24`) and
   `spatie/laravel-activitylog` (`^4.0`) pins survived this full update.
4. Run `php artisan test` — the **full** suite this time, not a filtered
   subset. Compare against the pre-upgrade baseline captured in Phase 2.
   Any failure not present in that baseline must be root-caused and fixed
   in this phase.
5. Run `vendor/bin/pint --dirty` across the whole diff accumulated since
   Phase 1 started (not just this phase's own changes) as a final formatting
   pass.
6. Run `npm run build` one more time as a final frontend build check.
7. Manual smoke test of the storefront checkout/quote flow specifically
   (not covered by Phase 3's smoke test, which focused on Inertia
   page-load/navigation): add a product to cart, generate a quote, run
   through the Stripe test-mode checkout flow end-to-end (test cards per
   the existing Stripe integration test coverage in
   `ai/modules/stripe-integration-complete/`).

## Files to Create/Modify

- `composer.json`, `composer.lock` — any additional constraint bumps found
  in Step 2.
- Any application code fixed in response to a genuine Step 4 test failure.

## Tests

- Full `php artisan test` suite green (or only the same pre-existing,
  already-documented-as-unrelated failures called out in the Stripe
  integration `STATE.md` log — e.g. `SamInsightsDashboardTest` memory
  exhaustion — reconfirm those are still the *only* failures, not new ones
  hiding behind the same excuse).

## Success Criteria / Definition of Done

- `composer update` completes with no unresolved/held-back packages, or
  every hold-back is documented with a decision (bump, pin, or flagged
  blocker).
- Full test suite passes with no new failures relative to the Phase 2
  baseline.
- `vendor/bin/pint --dirty` clean across the entire upgrade diff.
- `npm run build` succeeds.
- Manual Stripe checkout smoke test completes successfully in test mode.

**STOP. Await phase approval.**

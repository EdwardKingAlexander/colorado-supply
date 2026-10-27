# Phase 6 — Visual QA, Build & Test

## Goal

Prove the rebrand is consistent and regression-free across signed-in pages,
in light and dark, on mobile and desktop.

## Sub-phases
- 6a. Playwright spec `tests/Browser/customer-portal.spec.ts` (reuse
  `tests/Browser/support` login helpers): visits dashboard, reports,
  notifications, company, profile; asserts the brand logo is present, the
  account nav has `aria-current`, there is no horizontal overflow at 390px,
  the theme switch toggles `html.dark` and persists, and the page passes an
  axe scan (serious/critical) in both themes.
- 6b. Screenshots (desktop 1440 and mobile 390, light and dark) of the
  dashboard and profile, reviewed by eye and saved under the module folder's
  `qa/` directory.
- 6c. Regression: `npm run build`, full `php artisan test`, existing browser
  specs, Pint on edited PHP files. Spot-check login/register (primitive
  changes) and one Filament page (unchanged).

## Success Criteria
- Screenshots show one consistent shell in both themes and both viewports.
- No new test failures; build clean.
- STATE.md records the evidence; module set to `Awaiting Validation`.

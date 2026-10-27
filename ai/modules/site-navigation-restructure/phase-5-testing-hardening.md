# Phase 5 — Testing & Hardening

## Goal

Cover the new pages, nav/footer behavior, and the admin store toggle with
feature tests; confirm no regressions.

## Files to Create/Modify

- `tests/Feature/PageControllerTest.php`
  - `GET /about`, `/capabilities`, `/contact` each return 200,
    unauthenticated, and render the expected Inertia component
    (`About/Index`, `Capabilities/Index`, `Contact/Index`).
- `tests/Feature/WelcomeTest.php` (or extend an existing homepage test if
  one exists — check first)
  - Homepage still renders 200 and contains links to
    `route('about.index')`, `route('capabilities.index')`,
    `route('repair-services.index')`, `route('contact.index')` (assert on
    rendered HTML or Inertia props, whichever the existing test
    conventions in this repo use for similar checks).
- `tests/Feature/Filament/StoreAvailabilityToggleTest.php`
  - Toggling the Livewire component flips `McpSettings::for('store-settings')`
    correctly in both directions.
  - `McpDashboard`'s header actions no longer include a store toggle
    (regression guard against re-adding a duplicate control).

## Final Checklist

- Manually click through: from `/repair-services`, confirm "About",
  "Capabilities", "Contact", and "Store" all navigate correctly (Store
  redirects a logged-out session to `/login`).
- Confirm the homepage has no duplicate/orphaned anchor links pointing to
  removed sections.
- `php artisan test` passes (full suite, not just new tests) — pre-existing
  unrelated failures already documented in `ai/modules/repair-services/STATE.md`
  are expected and not this module's concern.
- `vendor/bin/pint --dirty` clean.

## Success Criteria

- All new tests green; no new failures beyond the already-documented
  pre-existing ones.
- Module considered complete and ready for sign-off.

**STOP. Await phase approval.**

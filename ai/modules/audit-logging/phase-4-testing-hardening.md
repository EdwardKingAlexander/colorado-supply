# Phase 4 — Testing & Hardening

## Goal

Confirm the whole module works end-to-end, doesn't regress performance or
existing tests, and doesn't leak sensitive data into logs.

## Work

- **Sensitive-data audit**: re-check every logged attribute list from
  Phase 2 for anything that shouldn't sit in a queryable log table
  long-term — in particular `Vendor.tax_id` (an EIN/SSN-adjacent value) and
  `User.email`. Decide per-field whether to log the value itself or just
  "changed" (a boolean) for especially sensitive fields. This is a real
  decision, not a checkbox — flag it back to the orchestrator if any field
  looks like it needs masking rather than assuming either way.
- **Bulk-operation check**: verify a Filament bulk action (e.g., bulk order
  status update) doesn't produce N log-write slowdowns disproportionate to
  the batch size, and produces one sensible log entry per affected record
  (not zero, not silently swallowed).
- **Full regression run**: `php artisan test` — compare against the
  pre-existing failure baseline documented in `ai/modules/repair-services/
  STATE.md` and `ai/modules/store-checkout-redesign/STATE.md`. As of
  2026-07-04 that baseline is down to `CustomerResourceGooglePlacesTest`
  and a Filament/Blade memory-exhaustion crash — confirm no new failures
  beyond those two.
- `vendor/bin/pint --dirty` across all module-touched files.

## Success Criteria

- All new tests from Phases 1-3 pass together.
- Sensitive-field decision made and documented (masked or justified as
  safe to log in full).
- No new full-suite regressions.
- `STATE.md` updated to `Awaiting Validation`.

**STOP. Await phase approval.**

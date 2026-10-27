# Phase 3 — Testing & Hardening

## Goal

Confirm error monitoring and backups both work together, don't leak
sensitive data, and don't regress the existing suite.

## Work

- **Full regression run**: `php artisan test` — compare against the
  documented pre-existing baseline (as of 2026-07-04:
  `CustomerResourceGooglePlacesTest` and a Filament/Blade memory-exhaustion
  crash). Confirm no new failures.
- **Sentry data-scrubbing re-check**: deliberately trigger exceptions from
  a request carrying fake-but-realistic sensitive data (a payment field, a
  tax ID shape) and manually inspect what actually reaches Sentry's
  dashboard (or a sandboxed test project) — confirm scrubbing works against
  the real SDK, not just the mocked transport from Phase 1's unit test.
- **Backup restore drill re-confirmation**: if Phase 2's drill was done
  against a local/fake disk before real credentials existed, redo it once
  against the real destination.
- `vendor/bin/pint --dirty` across all module-touched files.

## Success Criteria

- All new tests pass together.
- No new full-suite regressions.
- Sensitive-data scrubbing confirmed against the real Sentry SDK path, not
  just a mock.
- Backup restore verified against the real destination at least once.
- `STATE.md` updated to `Awaiting Validation`.

**STOP. Await phase approval.**

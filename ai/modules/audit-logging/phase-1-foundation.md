# Phase 1 — Package Install, Version Decision, Multi-Guard Causer Config

## Goal

Get `spatie/laravel-activitylog` installed with the correct version for
this app's actual deployment target, and configured so causer resolution
works correctly for both the `web` and `admin` guards. No model
instrumentation yet — this phase proves the plumbing works with one
throwaway test model/log entry.

## Version Decision (confirmed 2026-07-05)

Production runs **PHP 8.3**. `spatie/laravel-activitylog` v5 hard-requires
PHP 8.4+, so this installs **`spatie/laravel-activitylog:^4.0`** instead —
the pre-breaking-change line, compatible with PHP 8.3 and this app's
declared `^8.2` requirement. This means: batch UUID support is still
present (v5 removed it — not needed either way per this module's scope),
and the causer resolver API is v4's `CauserResolver::setCauserResolver()`
(v5 renamed things around `defaultCauser()` — not applicable here).

## Files to Create/Modify

- `composer.json` / `composer.lock` — add `spatie/laravel-activitylog:^4.0`.
- `config/activitylog.php` (published) — set `default_log_name`,
  `subject_returns_soft_deleted_models`, and register a custom causer
  resolver.
- `app/Support/ActivitylogCauserResolver.php` (new, or inline in a service
  provider) — resolves the causer by checking `Auth::guard('admin')->user()`
  first, then `Auth::guard('web')->user()`, matching the same dual-guard
  pattern already used throughout this app (`AuthenticateWebOrAdmin`,
  `StoreController`, etc.). Registered via
  `Spatie\Activitylog\Facades\CauserResolver::setCauserResolver(...)` (v4 API).
- `database/migrations/xxxx_create_activity_log_table.php` (published from
  the package).

## Out of Scope

- No models instrumented yet (Phase 2).
- No Filament UI yet (Phase 3).

## Tests

- `tests/Feature/ActivitylogCauserResolutionTest.php`: log an activity while
  authenticated as `web` guard User → causer resolves to that User; log one
  while authenticated as `admin` guard Admin → causer resolves to that
  Admin; log one with no authenticated user (e.g., a queued job context) →
  causer is null, no error thrown.

## Success Criteria

- `php artisan migrate` creates the `activity_log` table cleanly.
- Causer resolution test passes for both guards and the unauthenticated
  case.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

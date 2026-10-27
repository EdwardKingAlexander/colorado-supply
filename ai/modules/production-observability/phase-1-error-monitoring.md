# Phase 1 — Error Monitoring (Sentry)

## Blocking Question (should be answered before this phase starts)

Confirm actual production hosting (Forge/Vapor/a plain VPS/container
platform/etc.) — if it's Laravel Forge or Vapor, Laravel's first-party
Flare service integrates more natively and may be preferable to Sentry.
Absent evidence of either in this repo, Sentry is the default assumption
below, but this is cheap to confirm and expensive to redo.

## Goal

Capture and report unhandled exceptions from web requests, API requests,
queued jobs, and the Filament admin panel, with sensitive data scrubbed
before it ever leaves the server.

## Files to Create/Modify

- `composer.json` — add `sentry/sentry-laravel`.
- `.env` / `.env.example` — add `SENTRY_LARAVEL_DSN` (left blank in
  `.env.example`; the orchestrator supplies the real DSN in `.env`
  directly, never pasted into chat).
- `config/sentry.php` (published) — configure:
  - `traces_sample_rate` (start conservative, e.g. `0.1`, to control cost/
    volume — this is a real dial, not a fixed constant).
  - `before_send` callback: redact `Authorization`/`Cookie` headers, strip
    known-sensitive request fields (payment tokens, `tax_id`, password
    fields) from breadcrumbs/context before the event is sent to Sentry.
    This is the single most important line item in this phase — do not
    ship without it.
- `bootstrap/app.php` — wire Sentry into `->withExceptions()` per the
  package's Laravel 12 integration docs (confirm exact hook — Sentry's
  Laravel SDK auto-registers via its service provider in most setups, but
  Laravel 12's exception-handling structure changed from the old
  `app/Exceptions/Handler.php` pattern, so this needs verifying against
  the currently-installed SDK version's own docs, not assumed from an
  older Laravel version's setup instructions).

## Out of Scope

- No changes to backup tooling (Phase 2).
- No performance/APM tracing beyond a conservative default sample rate —
  this phase is about error capture, not full APM.

## Tests

- `tests/Feature/SentryIntegrationTest.php`: trigger a deliberate exception
  in a test route/job, assert Sentry's SDK was invoked (mock/fake the
  transport, don't send real test events to a real Sentry project) with
  the exception captured and the `before_send` scrubbing actually removing
  a known-sensitive test field from the payload.

## Success Criteria

- An unhandled exception in a web request, an API request, and a queued
  job all reach Sentry (verified against a real or sandboxed Sentry
  project once a DSN is supplied — not just unit-tested against a fake
  transport).
- A test confirming sensitive fields are actually redacted before send.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

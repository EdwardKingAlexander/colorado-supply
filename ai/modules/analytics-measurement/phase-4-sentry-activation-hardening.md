# Phase 4 — Sentry Activation and End-to-End Hardening

## Goal

Finish and validate the existing Sentry Laravel implementation against a free
Developer project while keeping error data private and usage below free limits.

## Existing Foundation to Reuse

- `sentry/sentry-laravel` is already installed.
- `config/sentry.php` is published.
- Laravel exception handling is already connected in `bootstrap/app.php`.
- `App\Support\SentryEventScrubber` already redacts sensitive request and
  breadcrumb data.
- `tests/Feature/SentryIntegrationTest.php` already verifies transport and
  scrubbing with an in-memory transport.

This phase must extend and validate that work, not reinstall or replace it.

## Scope

- Re-audit Sentry settings against the current free Developer quotas.
- Keep `send_default_pii=false`, SQL bindings disabled, replay disabled, logs
  ingestion disabled, profiling disabled, and tracing conservatively sampled.
- Add safe release/environment tags without executing Git commands from cached
  configuration.
- Add inbound/environment filtering where supported so local/test noise does
  not consume quota.
- Expand scrubber tests for contact, vendor, checkout, payment, SAM, headers,
  nested breadcrumbs, URLs, and query strings.
- Document free-project creation, DSN deployment, email alert setup, quota
  monitoring, and rollback/disable steps.
- Send one controlled production-safe test exception and confirm delivery,
  environment, release, stack trace, alert, and redaction.

## Expected Files to Create

- Additional focused scrubber/integration tests as needed
- `docs/sentry-deployment.md` or merge into `docs/analytics-deployment.md`

## Expected Files to Modify

- `config/sentry.php`
- `app/Support/SentryEventScrubber.php`
- `tests/Feature/SentryIntegrationTest.php`
- Deployment documentation

No `.env` value or DSN is committed. The owner enters
`SENTRY_LARAVEL_DSN` directly in the production environment.

## Free-Tier Guardrails

- Use the $0 Developer plan and one account user.
- Do not enable pay-as-you-go, Seer, session replay, profiling, or paid
  integrations.
- Capture errors at full rate initially; use a low trace sampling rate or zero
  until production traffic justifies traces.
- Configure email alerts for new/regressed high-severity issues and quota
  warnings without generating alert storms.

## Tests to Write

- Representative nested sensitive keys and values are redacted.
- Authorization, cookie, CSRF, and payment headers are removed.
- Sensitive query-string values and breadcrumb metadata are removed.
- Test/local events are not sent when no DSN or an excluded environment is
  configured.
- Config cache remains valid.
- Existing in-memory transport capture continues working.

## Owner Validation

- Create the free Sentry project and enter its DSN directly in Forge/environment
  configuration.
- Deploy, clear/cache config through the normal deployment process, and trigger
  one controlled test exception.
- Inspect the received event for forbidden data and confirm the email alert.
- Remove/disable any temporary diagnostic endpoint immediately after validation.

## Success Criteria

- A real production-safe test error reaches Sentry and triggers the configured
  alert.
- The event contains useful stack/environment/release context and no tested
  sensitive data.
- Expected monthly usage remains inside the free plan under configured sampling.
- The production-observability module's Sentry phase can be marked Complete
  after this shared validation evidence is recorded in both module states.
- Targeted tests, `php artisan config:cache`, `php artisan test`, and
  `vendor/bin/pint --dirty` pass.

**STOP. Await phase approval before implementation.**

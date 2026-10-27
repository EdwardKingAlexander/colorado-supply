# Production Observability (Error Monitoring + Backups) — Overview

## Problem Statement

Right now, if production throws an exception, the only record is whatever
lands in the log file on the server — nobody gets notified, and there's no
aggregation, trends, or alerting. And if the production database is ever
lost or corrupted, there's no automated backup: checked directly, this repo
has no `spatie/laravel-backup` (or any backup tooling) installed. For a
platform holding customer orders, payment records, and federal-contracting
data, both gaps are significant operational risk, independent of the
"government sales" framing — this is baseline production hygiene any
serious business should have.

## Constraints

- **Error monitoring**: use Sentry (`sentry/sentry-laravel`) — the most
  widely adopted, framework-agnostic option, with a well-documented Laravel
  integration. (Laravel's first-party "Flare" service is tightly coupled to
  Laravel Forge/Vapor hosting, which this project doesn't appear to use —
  confirm hosting setup before Phase 1 in case Flare is actually a better
  fit for wherever this is actually deployed.)
- **Backups**: `spatie/laravel-backup` — confirmed compatible with
  Laravel 12, well-established, supports the database + files, and can
  target S3 or any configured filesystem disk.
- Must not log/report sensitive data (payment details, tax IDs, full
  request bodies with PII) to a third-party error-tracking service —
  Sentry's default breadcrumbs can include request data; this needs
  explicit scrubbing configuration, not the out-of-the-box defaults.
- Backup destination needs a real decision: local disk is not a real backup
  strategy (same server, same failure domain) — needs S3 or an equivalent
  off-server target. Requires AWS (or equivalent) credentials, which
  aren't currently in `.env` (checked: `AWS_ACCESS_KEY_ID`/
  `AWS_SECRET_ACCESS_KEY` are present as empty placeholders in `.env`
  already — Phase 2 needs real values, which only the orchestrator can
  provide).

## Architecture (textual)

```
Any unhandled exception (web, API, queued job, Filament)
   -> Laravel's exception handler (bootstrap/app.php ->withExceptions())
   -> Sentry SDK captures + scrubs sensitive fields
   -> reported to Sentry project; alerting configured there (email/Slack)

Scheduled (daily, via Laravel's scheduler):
   -> spatie/laravel-backup: database dump + relevant file storage
   -> uploaded to S3 (or configured disk)
   -> on failure, notification fires (email at minimum)
   -> old backups pruned per a retention policy (config/backup.php)
```

## Risks

- **Sensitive data leaking into Sentry** — the single biggest risk for a
  gov/enterprise-facing app. Must configure `before_send` scrubbing
  (redact `Authorization` headers, payment fields, SSN/EIN-shaped values)
  before this ships to production, not as an afterthought.
- **Backup credentials/destination not yet decided** — this phase cannot
  actually run against real infrastructure without the orchestrator
  supplying S3 (or equivalent) credentials. Phase 2 can build everything
  and verify against a local/fake disk in tests, but the *first real backup
  run* needs those credentials supplied out-of-band (never asked for or
  typed into chat — per this app's own operating rules, credentials belong
  in `.env`, set directly by whoever owns them).
- **Backup restore is untested until someone tries it** — a backup nobody
  has ever restored from is not a verified backup. Phase 2 should include
  at least one documented, actually-executed restore-to-a-scratch-database
  drill, not just confirm the backup file was created.
- **Cost** — S3 storage + Sentry's paid tiers (free tiers exist but have
  volume limits) are an actual recurring cost, small but real; flagging so
  it's a conscious decision, not a surprise line item.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Error monitoring (Sentry) | `phase-1-error-monitoring.md` |
| 2 | Automated backups | `phase-2-backups.md` |
| 3 | Testing & hardening | `phase-3-testing-hardening.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per `PROJECT_CANNON.md`.

**STOP. Await approval before any implementation begins.**

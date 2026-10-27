---
plan: dibbs-rfq-tracking
phase: 2
title: Ingestion foundation
status: completed
last_updated: 2026-10-04
depends_on: [phase-1]
checkpoint: 2.6-alerting
---

# Phase 2 — Ingestion foundation

## Goal

Reliable, idempotent ingestion of DLA's daily RFQ, batch-quote and
approved-source files into the database, via scheduled download or manual
upload, with failure alerts.

## Sub-phases

### 2.1 Schema and models
Steps:
1. Migrations for `dibbs_files`, `dibbs_rfqs`, `dibbs_approved_sources`,
   `dibbs_quote_templates` (columns per overview, finalized from Phase 1).
2. Indexes: `dibbs_rfqs(fsc)`, `(return_by_date)`, `(nsn)`, unique key from 1.4;
   `dibbs_files(sha256)` unique; `dibbs_approved_sources(nsn)`, `(cage_code)`.
3. Models with casts, factories, relationships.
DoD: migrations run up/down cleanly; factories used in tests.

### 2.2 Parsers
Steps:
1. `App\Services\Dibbs\InFileParser` — fixed-width per Phase 1 layout; returns
   DTOs; per-row validation (NSN 13 digits, date parse); collects row errors
   without aborting the file.
2. `BqFileParser` — CSV; preserves the raw row verbatim for export.
3. `AsFileParser` — approved sources.
4. Layout guard: reject a file whose record length/column count doesn't match
   the documented layout, with a clear error.
DoD: parser tests on Phase 1 fixtures: counts, field values, bad-row handling.

### 2.3 Importer (idempotent)
Steps:
1. `DibbsFileImporter::import(DibbsFile)` — skip if sha256 already imported;
   upsert RFQs on the unique key (amendments update the row, keeping
   `first_seen_file_id`); transactional per file.
2. Records row_count, error count, status on `dibbs_files`.
DoD: importing the same fixture twice yields no duplicates; amendment test.

### 2.4 Download client (automated path)
Only if Phase 1.3 verdict = works.
Steps:
1. `DibbsClient` — honest UA (config), cookie jar per host, consent form
   detection + acceptance, ≥2 s between requests (config), content validation
   (HTML where a file was expected → treat as missing/blocked).
2. `DibbsDownloadJob` — list recent dates, download files not yet in
   `dibbs_files`, hand to importer.
3. Schedule in `routes/console.php` at the time found in Phase 1.4 (business days).
4. Config `config/dibbs.php`: base URL, UA, contact, interval, enabled flag,
   alert email (reuse `SAM_ALERT_EMAIL` fallback pattern).
DoD: Http::fake tests for banner flow, missing file (302→HTML), blocked
response, and success.

### 2.5 Upload fallback
Steps:
1. Filament page "Upload DIBBS file" (accepts `in*.txt`, `bq*.zip`), stores the
   file, creates `dibbs_files` (source=`upload`), queues import, shows result.
2. Reuse the same importer; same idempotency.
DoD: Livewire test uploading the fixture creates RFQs; re-upload is a no-op.

### 2.6 Failure alerting
Steps:
1. Notification on download block, layout mismatch, or import failure
   (pattern of `SamFetchFailedNotification`), best-effort, never crashes the job.
DoD: tests assert notification on each failure type.

## Files

Create: migrations, `app/Models/Dibbs*`, `app/Services/Dibbs/*`,
`app/Jobs/DibbsDownloadJob.php`, `app/Filament/Pages/DibbsUpload.php` (+view),
`app/Notifications/DibbsIngestionFailedNotification.php`, `config/dibbs.php`.
Modify: `routes/console.php`.

## Tests

`tests/Feature/Dibbs/` — parser, importer idempotency, client (Http::fake),
upload page, alerting. Full suite + pint.

## Success criteria

- Fixture files import correctly and idempotently by both paths.
- Scheduled job downloads and imports a real day's file (live check, if
  automation allowed) — recorded in `STATE.md`.

## Risks / blockers

- Phase 1 verdict "blocked" → skip 2.4, keep 2.5 as primary path.
- Large files: chunked inserts; measure import time on a full file.

STOP. Await phase approval.

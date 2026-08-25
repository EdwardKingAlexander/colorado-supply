---
plan: sam-fetch-repair
phase: 4
title: Scheduler & persistence integrity
status: awaiting_validation
last_updated: 2026-08-24
depends_on: [phase-1]
checkpoint: see STATE.md "Changes Made"
---

# Phase 4 — Scheduler & Persistence Integrity

## Goal

Fix the crashing daily scheduled fetch (Root cause B) and make persistence
trustworthy: one state-file writer, DB write failures that actually fail, and
complete field mapping. Addresses Root cause B and L7–L10.

This phase is **independent of SAM.gov connectivity** — every defect here is in
our code and is verifiable with mocked HTTP. It can proceed even if Phase 1.1
is still blocked on GSA.

## Sub-phases

### 4.1 — Fix the scheduled task crash — `checkpoint: scheduler-fix`

`bootstrap/app.php` calls `FetchSamOpportunitiesTool::handle([...])` statically.
Reproduced directly:

```
Error: Non-static method App\Mcp\Servers\Tool::handle() cannot be called statically
```

`handle()` is an instance method typed `handle(Request $request): Response` —
wrong receiver, wrong argument type, wrong return type. The guard is
`catch (Exception $e)`, and `Error` does not extend `Exception`, so the crash
escapes uncaught. **The daily 06:00 fetch has never run successfully.**

- Replace the closure body with a dispatch of the existing job, matching the
  path the Filament panel already exercises:
  ```php
  FetchSamOpportunitiesJob::dispatch(params: [...], userId: null);
  ```
  This gets queue retries, the job timeout, and one code path shared with the
  UI, instead of a second bespoke invocation.
- Widen the guard to `catch (\Throwable $e)` so `Error` can never again escape
  silently.
- Keep the existing result logging, but move it into the job (which already has
  equivalent logging) rather than duplicating it in `bootstrap/app.php`.
- Remove the now-dead `use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;`
  import from `bootstrap/app.php` if nothing else needs it.
- Consider extracting the schedule definition to `routes/console.php`, where
  Laravel 13 conventions put it and where it is testable. Keep this optional —
  do not expand scope if `bootstrap/app.php` is working after the fix.

Note: `app/Console/Commands/TestSamOpportunitiesTool.php` constructs the tool
with `new FetchSamOpportunitiesTool` and calls it directly — verify it uses
`fetch()`, not `handle()`, and fix it the same way if not.

### 4.2 — Single state-file writer — `checkpoint: single-writer`

The state file is written **twice per run**:

1. `FetchSamOpportunitiesTool::runWorkflow()` → `SamStateFileManager::saveLegacy()`
2. `FetchSamOpportunitiesJob::persistState()` → raw `file_put_contents()`

Plus a third code path, `FetchSamControlPanel::persistLastResult()`, which is
defined but never called.

- Make `SamStateFileManager` the only writer.
- Delete `FetchSamOpportunitiesJob::persistState()` and the unused
  `FetchSamControlPanel::persistLastResult()`.
- Keep `persistErrorState()` behaviour but route it through
  `SamStateFileManager` so rotation and formatting stay consistent.
- Write atomically: `file_put_contents($tmp)` then `rename()`, so a reader
  polling every few seconds can never observe a half-written JSON file.
- Check `JSON_PRETTY_PRINT` + `json_encode` failure: currently an encoding
  failure writes the string `false` to the file. Guard it.

### 4.3 — Fail loudly on DB write failure — `checkpoint: db-write-integrity`

`FetchSamOpportunitiesJob::saveOpportunitiesToDatabase()` wraps everything in
`try { ... } catch (\Throwable $e) { Log::error(...); }` and returns normally.
The job then logs `FetchSamOpportunitiesJob completed`. A total persistence
failure is therefore invisible: `failed_jobs` stays 0 and the panel reports
success.

- Let the exception propagate so the job fails and lands in `failed_jobs`, or
  record a `persist_failed` flag in the result that the panel surfaces.
- Wrap the write loop in a transaction so a mid-loop failure does not leave a
  partial batch.
- Count and return rows actually written; log `saved` vs `received` and warn on
  mismatch.
- `updateOrCreate` keys on `notice_id` — confirm a unique index exists on
  `sam_opportunities.notice_id`. Without it, concurrent runs can double-insert.
  Add a migration if missing.

### 4.4 — Complete the field mapping — `checkpoint: field-mapping`

- **L9:** `description` is never mapped in `SamApiClient::mapOpportunities()`,
  so `$opp['description'] ?? null` in the job always writes `null`. Map it from
  the live response (confirm the source field name against the Phase 1.4
  fixture — likely `description`, which may be a URL requiring a second fetch
  rather than inline text; if so, store the link and note it, do not silently
  write null).
- **L10:** the job does `'links' => json_encode([...])`. Check
  `SamOpportunity::casts()` — if `links` is cast to `array`, this
  double-encodes. Pass the array and let the cast handle it.
- `raw_data` is cast to `array` on the model but never populated. Store the raw
  API record so future mapping changes can be backfilled without re-querying
  SAM.gov. Confirm the column exists before relying on it.
- Map `lastModifiedDate` → `last_modified_date` (the model casts it to
  `datetime`, and the deduplicator already relies on the value).

## Files

**Modify**
- `bootstrap/app.php`
- `app/Jobs/FetchSamOpportunitiesJob.php`
- `app/Support/SamStateFileManager.php`
- `app/Services/SamApiClient.php` (mapping)
- `app/Filament/Pages/FetchSamControlPanel.php` (remove dead writer)
- `app/Console/Commands/TestSamOpportunitiesTool.php` (if it calls `handle()`)

**Create (conditional)**
- migration adding a unique index on `sam_opportunities.notice_id`, if absent

**Tests**
- `tests/Feature/Jobs/FetchSamOpportunitiesJobTest.php` (extend)
- `tests/Feature/Schedule/SamScheduledFetchTest.php` (new)
- `tests/Feature/Services/SamStateFileManagerTest.php` (extend)

## Tests

- **Scheduled task test** — resolve the `fetch-sam-opportunities` scheduled
  event and invoke it with `Queue::fake()`; assert `FetchSamOpportunitiesJob`
  was dispatched and **no `Error` was thrown**. *This is the direct regression
  guard for Root cause B — assert on the schedule as registered, not on a
  hand-rolled copy of the closure.*
- State file written exactly once per run (spy on the writer).
- Atomic write: no partially written file is observable mid-write.
- DB failure (force a query exception) → job fails / `persist_failed` set;
  assert it is **not** reported as a successful completion.
- `saved` count matches opportunities received.
- `description`, `links`, `raw_data`, `last_modified_date` are persisted with
  correct types; `links` is not double-encoded.
- Re-running the same fetch updates rows rather than duplicating (unique index).

## Success Criteria

1. `php artisan schedule:run` executes the SAM task without throwing.
2. Exactly one component writes the state file; writes are atomic.
3. A DB write failure surfaces as a failure, never as success.
4. Persisted rows have populated `description`, `links`, `raw_data`,
   `last_modified_date`.
5. `php artisan test` green; `vendor/bin/pint --dirty` clean.

## Risks / Blockers

- `bootstrap/app.php` is application-wide; a mistake there breaks boot for
  everything. Change only the schedule closure and its import.
- 4.3 changes failure semantics — jobs that used to "succeed" will now fail
  visibly. That is the intent, but it will make previously hidden problems
  appear suddenly. Expect noise on the first real run.
- Adding a unique index will fail if duplicate `notice_id` rows already exist.
  The table is currently empty (0 rows), so this is safe now — re-check before
  running the migration.

STOP. Await phase approval.

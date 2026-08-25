---
plan: sam-fetch-repair
phase: 6
title: Operator visibility & regression suite
status: not_started
last_updated: 2026-08-24
depends_on: [phase-2, phase-3, phase-4, phase-5]
checkpoint: none
---

# Phase 6 — Operator Visibility & Regression Suite

## Goal

Close the two gaps that let this failure persist unnoticed: an operator UI that
cannot distinguish "working" from "broken", and a test suite that mocks the
transport so thoroughly it can never catch a transport-level defect. Addresses
L11 and the module-level testing gap.

## Background

### Why the suite missed all of this

There are 23 SAM test files and they pass. They missed a dead endpoint, a
wrong NAICS parameter name, a wrong set-aside parameter name, missing
pagination, a no-op filter method, and a scheduled task that throws on every
run. The reason is structural: **every test fakes the HTTP layer and asserts on
the faked response, and none assert on the outgoing request**. A test that
mocks `Http::fake(['*' => Http::response($fixture)])` passes identically
whether the client sends `naics=`, `ncode=`, or `banana=`.

Phase 2 adds `Http::assertSent()` contract assertions. This phase adds the
outer guards.

### L11 — the panel cannot report its own health

`FetchSamControlPanel` defines `checkQueueStatus()` and
`isQueueWorkerRunning()` — **neither is ever called**. `queueWorkerStatus` is
assigned the literal `'manual'` in two places and never computed.
`updateQueueWorkerStatus()` is only reachable from `stopQueueWorker()`, which
is itself unreachable from the UI.

So if the queue worker is stopped, `executeFetch()` dispatches a job that never
runs, the panel polls indefinitely, and the operator sees a spinner forever
with no error — presenting exactly as "the fetch feature does not work".

## Sub-phases

### 6.1 — Queue worker visibility — `checkpoint: worker-visibility`

- Call `isQueueWorkerRunning()` from `mount()` and set `queueWorkerStatus` from
  its real result.
- Before dispatching in `executeFetch()`, if no worker is detected, warn
  prominently and clearly: the job is queued but will not run until a worker
  starts. Do not block the dispatch — queue it and tell the truth.
- Surface pending/failed job counts (`checkQueueStatus()` already computes
  pending; add failed).
- The Windows branch of `isQueueWorkerRunning()` shells out to PowerShell via
  `shell_exec` and depends on `storage/logs/queue-worker.pid`, which nothing
  writes. Either make the PID file get written or replace the check with a
  `jobs`-table heartbeat. Prefer the heartbeat: no shell-out, cross-platform,
  and testable.

### 6.2 — Poll timeout — `checkpoint: poll-timeout`

`checkFetchStatus()` returns `false` forever if nothing updates the state file.
The Blade view polls with no deadline.

- Add a client-side and server-side deadline (e.g. 5 minutes, above the job's
  300s timeout).
- On expiry: stop polling, clear `sam_fetch_started_at`, and show a timeout
  notification naming the likely cause (worker not running / job failed) and
  pointing at `php artisan queue:failed`.
- Fix the second-granularity comparison: `fetched_at > started_at` misses a
  fetch completing within the same second as dispatch. Compare with `>=`, or
  better, correlate on a dispatch id written into the state file rather than on
  wall-clock timestamps.

### 6.3 — Contract regression tests — `checkpoint: contract-tests`

Add `tests/Feature/Services/SamApiContractTest.php` asserting the **outgoing
request**, which nothing currently does:

- exact query parameter names (guards L1/L2 from silently regressing)
- `offset` present and incrementing across pages (guards L4)
- base URL sourced from config (guards the Phase 1 extraction)
- the API key is never logged (`Log::spy()` — `SamApiClient` logs a
  `$debugParams` copy; assert the real key never appears)

### 6.4 — End-to-end smoke test — `checkpoint: e2e-smoke`

`tests/Feature/Integration/SamFetchEndToEndTest.php`: dispatch
`FetchSamOpportunitiesJob` with a faked HTTP layer returning the Phase 1.4
fixture, then assert the whole chain — rows land in `sam_opportunities` with
correct field mapping, the state file is written once with matching counts, and
the panel renders the result.

This is the test that would have caught the original failure end to end.

### 6.5 — Live health check — `checkpoint: health-check`

- Extend `sam:diagnose` (Phase 1.3) with `--json` for monitoring.
- Optional, user's call: a scheduled daily health check that alerts when the
  endpoint stops responding, so the next outage surfaces immediately rather
  than as "zero opportunities" weeks later. The commented-out Slack/email
  notification hooks in `bootstrap/app.php` are the natural place.

## Files

**Modify**
- `app/Filament/Pages/FetchSamControlPanel.php`
- `resources/views/filament/pages/fetch-sam-control-panel.blade.php`
- `app/Console/Commands/SamApiDiagnose.php`

**Create**
- `tests/Feature/Services/SamApiContractTest.php`
- `tests/Feature/Integration/SamFetchEndToEndTest.php`
- `tests/Feature/Filament/FetchSamControlPanelQueueStatusTest.php`

## Tests

- Worker stopped → panel shows a warning; job still dispatches.
- Worker running → no warning.
- Poll exceeds deadline → polling stops, timeout notification shown, session
  key cleared.
- Fetch completing in the same second as dispatch is still detected.
- Contract tests per 6.3, including the key-never-logged assertion.
- E2E: faked API response → DB rows + state file + rendered panel all agree on
  the count.

## Success Criteria

1. An operator can tell from the panel alone whether the pipeline is healthy.
2. Polling always terminates with a definite outcome.
3. Changing a query parameter name breaks a test.
4. Removing the endpoint (simulated 404 empty body) breaks a test.
5. The E2E test covers dispatch → API → dedup → filter → DB → state → UI.
6. Full suite green; `vendor/bin/pint --dirty` clean.

## Risks / Blockers

- 6.1's worker detection is environment-specific; the heartbeat approach avoids
  `shell_exec` and is preferred for that reason.
- 6.4 depends on a realistic fixture from Phase 1.4. If Phase 1 is still
  blocked, build the fixture from GSA's documented schema and mark it for
  replacement with a real capture — note the substitution in `STATE.md` so it
  is not mistaken for verified data.
- Do not let this phase grow into a general test-coverage project. Scope is
  regression guards for the defects this module identified.

STOP. Await phase approval.

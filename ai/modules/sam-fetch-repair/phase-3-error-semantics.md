---
plan: sam-fetch-repair
phase: 3
title: Error semantics & diagnostics
status: not_started
last_updated: 2026-08-24
depends_on: [phase-1]
checkpoint: none
---

# Phase 3 — Error Semantics & Diagnostics

## Goal

Make the failure output tell the truth. Today a genuinely empty result set and
a dead endpoint produce the same operator-facing message, and the message that
*is* shown — "API endpoint not found" — buries the actual diagnostic signal.
Addresses L3.

## Background

`SamApiClient::categorizeHttpError()` maps 404 → `endpoint_not_found` and
`buildErrorResponse()` renders it as `"API endpoint not found"`. GSA's
published response codes list **404 as "no data"**.

So the current design conflates two very different conditions:

| Condition | Today | Should be |
|-----------|-------|-----------|
| Query matched nothing | `success: false`, "API endpoint not found" | `success: true`, 0 opportunities |
| Endpoint genuinely gone | `success: false`, "API endpoint not found" | `success: false`, actionable diagnostic |

The distinguishing signal is available and already being captured but not used:
a "no data" 404 comes back with a **JSON body**, whereas the current outage
returns **`Content-Length: 0`** with `server: istio-envoy`. `buildErrorResponse()`
already logs `response_size` and `content_type` — it just does not branch on
them.

There is a real risk in this phase: treating *all* 404s as success would make
the current total outage look like "0 opportunities found, everything fine".
That is strictly worse than today. The empty-body check is what prevents it,
and it must be tested explicitly.

## Sub-phases

### 3.1 — Split the 404 taxonomy — `checkpoint: 404-taxonomy`

In `SamApiClient`:

- 404 **with a non-empty JSON body** → treat as a successful empty result:
  `success: true`, `count: 0`, `total_records: 0`, `opportunities: []`, plus
  `no_data: true` for logging.
- 404 **with an empty body** → hard failure, but with a specific, actionable
  message rather than the generic one, e.g.
  `"SAM.gov returned 404 with an empty body for <base_url> — the endpoint is unreachable or has moved. Run: php artisan sam:diagnose"`.
- Keep the existing category for the empty-body case but rename it to
  `endpoint_unreachable` to distinguish it from the "no data" path.

### 3.2 — Preserve diagnostics through the response chain — `checkpoint: diagnostic-passthrough`

The state file shows the failure arriving at the UI stripped of everything
useful:

```json
{ "message": "API endpoint not found", "naics": null, "type": null,
  "status_code": null, "details": null }
```

`SamApiClient` populates `naics`, `status_code`, and `error_type`, and
`SamMultiNaicsFetcher::getFailedNaics()` forwards them — so they are being lost
in `SamResponseBuilder::extractErrors()` / `toLegacyFormat()`.

- Trace and fix the field mapping so `naics`, `status_code`, and `error_type`
  survive to the state file.
- Add `base_url` and `checked_at` to the error payload.
- Read `SamResponseBuilder` before editing — its exact field names determine
  the fix, and it has existing tests that must keep passing.

### 3.3 — Distinguish "all failed" from "all empty" — `checkpoint: status-semantics`

Once 3.1 lands, 9 NAICS returning genuine "no data" is a **success** with 0
results, not a failure. Verify `SamResponseBuilder::build()` reports:

- all succeeded, 0 opportunities → `status: success`, no error
- all failed → `status: failure` with the diagnostic from 3.1
- mixed → `status: partial_success`

### 3.4 — Operator-facing message — `checkpoint: ui-message`

`FetchSamControlPanel::checkFetchStatus()` renders `'Error: ' . $error`. With
3.1–3.3 the message becomes actionable on its own. Additionally:

- On zero results with `status: success`, show an informational notification
  ("No opportunities matched — try widening the date range or removing the
  state filter"), not a red failure.
- On `endpoint_unreachable`, show the remediation hint including
  `php artisan sam:diagnose`.

## Files

**Modify**
- `app/Services/SamApiClient.php`
- `app/Support/SamResponseBuilder.php`
- `app/Filament/Pages/FetchSamControlPanel.php`

**Tests**
- `tests/Feature/Services/SamApiClientTest.php` (extend)
- `tests/Feature/Services/SamResponseBuilderTest.php` (extend)
- `tests/Feature/Services/SamApiClient404SemanticsTest.php` (new)

## Tests

- 404 + JSON body → `success: true`, `count: 0`, `no_data: true`.
- **404 + empty body → `success: false`** and the message names the base URL
  and the diagnose command. *This is the regression guard for the current
  outage — it must fail loudly, never be reported as "0 results".*
- 401/403 → credential-specific message, unchanged.
- 500 → server-error category, unchanged.
- Failed-NAICS payload retains `naics`, `status_code`, `error_type` end to end
  (assert against the serialised state file, not just the in-memory array).
- All-empty-but-successful run → `status: success`.
- Panel test: zero-result success renders an info notification, not a danger one.

## Success Criteria

1. An empty result set never reports as a system failure.
2. The current outage still fails loudly, with a message naming the endpoint
   and the remediation command.
3. `failed_naics` entries in the state file carry populated fields.
4. `php artisan test` green; `vendor/bin/pint --dirty` clean.

## Risks / Blockers

- **Primary risk:** over-broad 404-as-success would mask a real outage. The
  empty-body discriminator and its test are non-negotiable.
- If Phase 1 reveals SAM.gov signals "no data" differently (e.g. 200 with an
  empty array), adjust 3.1 to match observed behaviour and keep the empty-body
  404 guard regardless.

STOP. Await phase approval.

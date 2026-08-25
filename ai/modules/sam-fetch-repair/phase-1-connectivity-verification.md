---
plan: sam-fetch-repair
phase: 1
title: Connectivity & credential verification
status: awaiting_validation
last_updated: 2026-08-24
depends_on: []
checkpoint: see STATE.md "Changes Made"
---

# Phase 1 — Connectivity & Credential Verification

## Goal

Establish, with evidence, the **currently working** SAM.gov opportunities
endpoint and the credential type it requires. Make the base URL and credential
configurable so a future SAM.gov relocation is a `.env` change rather than a
code change. Ship a repeatable, opt-in diagnostic command so this class of
outage is diagnosable in under a minute instead of a multi-hour investigation.

This phase gates every other phase: without a reachable endpoint, no change in
Phases 2–6 can be validated end to end.

## Why this is first

Every `api.sam.gov` path currently returns HTTP 404 with an empty body, from
two independent networks, with and without an API key. Until that is resolved,
"does the fetch work?" is unanswerable. See `00-overview.md` → Root cause A.

## Sub-phases

### 1.1 — External resolution (no code) — `checkpoint: external-resolution`

Not a coding task; it is the blocking dependency and must be tracked.

1. Open a ticket with the SAM.gov Federal Service Desk (fsd.gov) reporting:
   HTTP 404, empty body, `server: istio-envoy`, reproduced from two networks,
   keyless requests also 404 (so it is not key-related), across
   `/opportunities/v2/search`, `/prod/opportunities/v2/search`, and
   `api-alpha.sam.gov/prodlike/...`.
2. Confirm from GSA which of these is true:
   - endpoint relocated (obtain the new base URL), or
   - endpoint retired in favour of a new API/version, or
   - active outage (obtain ETA).
3. Confirm the required credential type for that endpoint: **api.data.gov
   public key (40-char alphanumeric)** vs **SAM.gov System Account key
   (UUID)**. Our current `SAM_API_KEY` is a UUID — establish whether that is
   correct for the endpoint we are told to use, and whether IP allowlisting
   applies.
4. Record the answers in `STATE.md`.

**Definition of done:** a confirmed base URL + credential type recorded in
`STATE.md`, or a documented outage ETA. If GSA confirms an outage with no code
change needed, mark 1.1 done and proceed — the rest of the module still stands.

### 1.2 — Make endpoint and credential configurable — `checkpoint: config-extraction`

Currently `SamApiClient::API_BASE_URL` is a hardcoded `protected const`, and
`FetchSamOpportunitiesTool::$apiBaseUrl` holds a **second, unused copy** of the
same URL. Remove the duplication and externalise it.

- `config/services.php` → extend the `sam` block:
  ```php
  'sam' => [
      'api_key'   => env('SAM_API_KEY'),
      'base_url'  => env('SAM_API_BASE_URL', 'https://api.sam.gov/opportunities/v2/search'),
      'auth_mode' => env('SAM_API_AUTH_MODE', 'query'), // 'query' | 'header'
      'timeout'   => env('SAM_API_TIMEOUT', 30),
  ],
  ```
- `.env` / `.env.example` → add `SAM_API_BASE_URL`, `SAM_API_AUTH_MODE`.
- `app/Services/SamApiClient.php` → read base URL, timeout, and auth mode from
  config; keep the current values as defaults so behaviour is unchanged until
  the env is set.
- `app/Mcp/Servers/Business/Tools/FetchSamOpportunitiesTool.php` → delete the
  now-redundant `$apiBaseUrl` property.
- `phpunit.xml` → pin `SAM_API_BASE_URL` and a dummy `SAM_API_KEY` so tests
  never depend on real env.

### 1.3 — Diagnostic artisan command — `checkpoint: diagnostic-command`

Create `app/Console/Commands/SamApiDiagnose.php` (`sam:diagnose`). Opt-in only,
never run by the test suite or the scheduler.

It must probe and print a table of:

- configured base URL, key length, key shape (UUID vs 40-char alnum) — **never
  print the key itself**
- a minimal request (`postedFrom`/`postedTo`/`limit=1`) → status, body length,
  `server` and `x-envoy-upstream-service-time` headers
- the same request **without** a key, to distinguish 403 (route alive, key
  problem) from 404 (route missing)
- a control request to `https://sam.gov` to separate "SAM.gov down" from
  "our network down"
- for a 200: `totalRecords` and the top-level JSON keys actually returned

Exit non-zero on failure so it is usable as a health check.

### 1.4 — Confirm the live contract — `checkpoint: contract-confirmed`

Once 1.1 yields a reachable endpoint, run `sam:diagnose` and capture a **real
response body** to a fixture at
`tests/Fixtures/Sam/opportunities-v2-live-sample.json` (scrubbed of the key).
Phases 2, 3, and 6 build on this fixture, and it settles R2 (`naics` vs
`ncode`) with evidence rather than documentation.

Specifically verify against the live endpoint:

- Does `ncode=` filter correctly? Does `naics=`? (Compare `totalRecords` for
  the same window with each, and with neither. If both return identical
  unfiltered counts, neither is filtering.)
- Is `typeOfSetAside` single- or multi-valued?
- Is `offset` honoured, and does `totalRecords` reflect the full set?

## Files

**Create**
- `app/Console/Commands/SamApiDiagnose.php`
- `tests/Feature/Console/SamApiDiagnoseTest.php`
- `tests/Fixtures/Sam/opportunities-v2-live-sample.json` (sub-phase 1.4)

**Modify**
- `config/services.php`
- `.env`, `.env.example`
- `phpunit.xml`
- `app/Services/SamApiClient.php`
- `app/Mcp/Servers/Business/Tools/FetchSamOpportunitiesTool.php`

## Tests

- `SamApiDiagnoseTest` — with `Http::fake()`:
  - 200 → command exits 0 and reports `totalRecords`
  - 404 empty body → exits non-zero, output names the endpoint as unreachable
  - 403 keyless → output distinguishes "route alive, credential rejected"
  - the API key never appears in output
- `SamApiClientTest` (extend) — asserts the client calls
  `config('services.sam.base_url')` and honours `SAM_API_AUTH_MODE=header`.

## Success Criteria

1. `php artisan sam:diagnose` produces an unambiguous verdict in one run.
2. Base URL, timeout, and auth mode come from config; no hardcoded URL remains
   in `SamApiClient`, and the duplicate in the tool class is gone.
3. Existing SAM tests still pass (defaults preserve current behaviour).
4. `STATE.md` records GSA's confirmed endpoint + credential type.
5. If 1.1 resolves: a live fetch returns HTTP 200 with `totalRecords > 0`.

## Risks / Blockers

- **Blocking:** 1.1 depends on GSA. 1.2 and 1.3 can proceed in parallel and are
  valuable regardless — do not let them wait on the ticket.
- If GSA directs us to the System Account model, 1.2 widens to cover a
  different auth header and possible IP allowlisting; flag before implementing.
- Do **not** change `naics` → `ncode` in this phase. That is Phase 2, and only
  with live evidence from 1.4.

STOP. Await phase approval.

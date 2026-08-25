# SAM Opportunities Fetch — Repair

## Problem Statement

The SAM.gov opportunity fetch returns zero opportunities on every run. The
`sam_opportunities` table is empty (0 rows). The shared state file
(`app/Mcp/Servers/Business/State/sam-opportunities.json`) records
`success: false`, `failed_naics_count: 9`, and the error
`"API endpoint not found"` for every NAICS code.

This is not one bug. Investigation on 2026-08-24 found **two independent root
causes** plus a set of latent defects that will still produce wrong results
after the two root causes are fixed.

## Verified Findings

Everything below was reproduced directly, not inferred.

### Root cause A — every `api.sam.gov` route returns HTTP 404 (external)

`https://api.sam.gov/opportunities/v2/search` returns **HTTP 404 with a
zero-length body**. The response carries `server: istio-envoy` and
`x-envoy-upstream-service-time: 1–2`, meaning an upstream service handled the
request and returned 404 — it is not a network or DNS failure.

The 404 is unconditional across every axis tested:

| Axis | Variants tested | Result |
|------|-----------------|--------|
| Path | `/opportunities/v2/search`, `/prod/opportunities/v2/search`, `/opportunities/v1/search`, `/opportunities/v3/search`, `/data-services/v1/opportunities`, `/entity-information/v3\|v4/entities`, `/`, `/health`, `/opportunities`, `/api`, `/prod` | all 404 |
| Host | `api.sam.gov`, `api-alpha.sam.gov` (incl. documented `/prodlike/` path) | all 404 |
| Auth | key in query, key in `X-Api-Key` header, **no key at all** | all 404 |
| Date range | current week, `01/2025`, `06/2024`, `01/2026` | all 404 |
| Client | Guzzle/Laravel `Http`, system `curl`, default + browser User-Agent, HTTP/1.1 | all 404 |
| Network egress | this workstation **and** an unrelated external network | all 404 |

Controls confirming the problem is specific to `api.sam.gov`:

- `https://sam.gov` → **200**
- `https://api.data.gov/` → **200**
- `https://example.com` → **200**
- TLS is healthy: valid DigiCert EV certificate for `CN=api.sam.gov` issued to
  the General Services Administration, TLS 1.3, `Verify return code: 0 (ok)`,
  and **no client-certificate request** (mutual TLS is not the cause).

Two details make this decisive:

1. A request with **no `api_key` at all** returns 404. If the route existed
   behind api.data.gov's key gate, a keyless request would return **403
   `API_KEY_MISSING`**, not 404. The route itself is not resolving.
2. The identical 404 reproduces from a completely separate network, so this is
   not IP-based blocking of this workstation.

GSA's live documentation still lists this endpoint as current, and its
production example URL includes `/prod/` — which also 404s. The endpoint is
therefore either retired, relocated, or in an outage that GSA's docs have not
caught up with. **This must be resolved with GSA/SAM.gov before any code change
can be validated end to end**, which is why it is Phase 1 and gates everything
else.

Secondary credential concern found while testing: `SAM_API_KEY` is a **36-char
UUID** (`A99A999A-9AA9-9A9A-A9A9-9999999A999A`). Public api.data.gov keys — the
kind the public Get Opportunities API expects — are **40-character
alphanumeric**. A UUID is the shape SAM.gov issues for a *System Account*,
which is a different access model (different base path, IP allowlisting). This
is **not** the cause of the 404 (keyless requests 404 too), but it is very
likely a second blocker waiting behind it, so Phase 1 verifies it explicitly.

### Root cause B — the scheduled daily fetch crashes (in our code)

`bootstrap/app.php` line 42 calls:

```php
$result = FetchSamOpportunitiesTool::handle([...]);   // static call, array arg
```

But `handle()` is an **instance** method on `App\Mcp\Servers\Tool` with the
signature `handle(Request $request): Response`. Executing this throws:

```
Error: Non-static method App\Mcp\Servers\Tool::handle() cannot be called statically
```

The surrounding guard is `catch (Exception $e)`. In PHP, `Error` does **not**
extend `Exception`, so the crash is *not* caught — it escapes the scheduled
closure entirely. Even if it were caught, `handle()` returns a `Response`, not
the array the code then subscripts as `$result['success']`.

**Consequence: the daily 06:00 scheduled fetch has never successfully run.**
This is independent of Root cause A and would still be broken if SAM.gov came
back right now. The manual Filament panel path uses a different entry point
(`FetchSamOpportunitiesJob` → `$tool->fetch()`), which is why the panel at
least executes.

### Latent defects (will produce wrong data once A and B are fixed)

| # | Defect | Evidence | Effect |
|---|--------|----------|--------|
| L1 | Request sends `naics=`; GSA documents the NAICS filter as **`ncode=`** | `SamApiClient::buildQueryParams()` | SAM ignores unknown params, so all 9 NAICS queries would return the *same unfiltered* result set — no NAICS filtering, and a huge false duplicate count |
| L2 | Request sends `setAsideCode=` comma-joined (`SBA,WOSB,EDWOSB,SDVOSBC,8A,HUBZone`); GSA documents **`typeOfSetAside=`**, single-valued | `SamApiClient::buildQueryParams()` | Set-aside filter silently not applied today; a naive rename would over-filter to one value |
| L3 | HTTP 404 is classified as fatal `endpoint_not_found` | `SamApiClient::categorizeHttpError()` | GSA documents 404 as **"no data"**. A legitimately empty result is reported to the operator as a total system failure |
| L4 | No pagination — `offset` is never sent, `totalRecords` is read but never used | `SamApiClient` | Silent truncation at 1000 records per NAICS |
| L5 | `applyFilters()` returns its input unchanged | `FetchSamOpportunitiesTool::applyFilters()` | The panel's **PSC Codes** and **Keywords** fields do nothing, yet are echoed back in the query summary as if applied |
| L6 | Fallback sets `naics_override => []`, but `empty([])` is `true`, so `resolveNaicsCodes()` returns **all** defaults | `SamParameterResolver::resolveNaicsCodes()` | The "nationwide keyword-only" fallback re-runs essentially the same failing query. It is also gated on `keywords`, which defaults to a large config list, so it fires when the user never asked for keywords |
| L7 | State file is written twice per run — by `SamStateFileManager::saveLegacy()` and again by `FetchSamOpportunitiesJob::persistState()` | both classes | Two unsynchronised writers to one file; on job retry the error state can overwrite a good result |
| L8 | `saveOpportunitiesToDatabase()` catches `\Throwable`, logs, and returns | `FetchSamOpportunitiesJob` | A total DB write failure still logs `FetchSamOpportunitiesJob completed`; queue shows 0 failures |
| L9 | `description` is never mapped from the API response | `SamApiClient::mapOpportunities()` | `sam_opportunities.description` is always `null` |
| L10 | `links` is manually `json_encode`d into the model | `FetchSamOpportunitiesJob` | Risks double-encoding against Eloquent casts |
| L11 | `checkQueueStatus()` and `isQueueWorkerRunning()` are dead code; `queueWorkerStatus` is hardcoded to `'manual'` | `FetchSamControlPanel` | If the queue worker is stopped, the panel polls forever with no warning and no timeout — indistinguishable from "broken" to an operator |

### What is *not* broken

Worth stating, because it narrows the search: the plumbing is sound. The queue
worker is running, `jobs=0` / `failed_jobs=0`, the job dispatches, executes,
completes, and writes its state file. Caching, deduplication, the parameter
resolver's date/NAICS validation, and the response builder all behave
correctly. The 23 existing SAM test files pass against mocked HTTP — which is
exactly why this failure was invisible to the suite: **every test mocks the
transport, so no test would ever have caught a dead endpoint or a wrong query
parameter name.**

## Constraints

- Filament v4 conventions (`Filament\Schemas\Components`, `Filament\Actions`).
- Laravel 13, Pest tests, `vendor/bin/pint --dirty` before commit.
- SAM.gov v2 contract: dates `MM/dd/yyyy`, max range 1 year, `limit` max 1000.
- No live SAM.gov calls in the test suite; live checks stay in an explicit
  opt-in artisan command.
- This is the most critical feature in the application. No phase may leave the
  fetch in a worse state than it is now; each phase must be independently
  revertible.

## Architecture / Data Flow (target)

```
FetchSamControlPanel ─┐
                      ├─> FetchSamOpportunitiesJob ─> FetchSamOpportunitiesTool::fetch()
Scheduler (06:00) ────┘                                        │
                                                               v
                                          SamParameterResolver (resolve + validate)
                                                               │
                                                               v
                                          SamMultiNaicsFetcher (per-NAICS, cached)
                                                               │
                                                               v
                                          SamApiClient (paginated, correct params)
                                                               │
                                                               v
                                  Deduplicator -> filters -> ResponseBuilder
                                                               │
                                              ┌────────────────┴────────────────┐
                                              v                                 v
                                   SamStateFileManager                  SamOpportunity (DB)
                                    (single writer)                    (verified row count)
```

Key structural change: **one** state-file writer, **real** post-fetch filters,
and a paginating API client whose error taxonomy distinguishes "no data" from
"endpoint gone".

## Phases

| # | Phase | Goal | Depends on |
|---|-------|------|-----------|
| 1 | [Connectivity & credential verification](phase-1-connectivity-verification.md) | Establish the correct live endpoint + credential; add an opt-in diagnostic command; make base URL configurable | — |
| 2 | [API request contract correction](phase-2-api-contract-correction.md) | Fix `ncode`, `typeOfSetAside`, add `offset`/pagination (L1, L2, L4) | 1 |
| 3 | [Error semantics & diagnostics](phase-3-error-semantics.md) | 404 = "no data"; surface actionable diagnostics instead of "API endpoint not found" (L3) | 1 |
| 4 | [Scheduler & persistence integrity](phase-4-scheduler-and-persistence.md) | Fix the static `handle()` crash; single state writer; DB write failures fail loudly (Root cause B, L7–L10) | 1 |
| 5 | [Filter fidelity](phase-5-filter-fidelity.md) | Make PSC/keyword filters real or remove the UI; fix the fallback branch (L5, L6) | 2, 3 |
| 6 | [Operator visibility & regression suite](phase-6-validation-and-regression.md) | Queue-worker warning + poll timeout; contract tests that would have caught this (L11) | 2, 3, 4, 5 |

## Risks

- **R1 (highest): Phase 1 may be unresolvable by us alone.** If SAM.gov has
  retired the endpoint, the fix depends on GSA publishing a replacement. Phases
  2–6 are still worth doing (they fix real defects and Root cause B), but
  end-to-end validation stays blocked until R1 clears. Phase 1 defines an
  explicit decision point rather than assuming a happy path.
- **R2: The `naics` vs `ncode` code comment.** `SamApiClient` carries a comment
  claiming `ncode` "returns empty" and that `naics` is correct. That conclusion
  cannot be reproduced right now (everything 404s) and contradicts current GSA
  docs. Phase 2 must verify against a live endpoint before changing it, not
  swap it on documentation alone.
- **R3: Credential model change.** If the UUID key turns out to require the
  System Account path, base URL *and* auth handling change together, widening
  Phase 1 into a configuration change across `.env`, `config/services.php`, and
  possibly IP allowlisting.
- **R4: Fixing L1 changes result volume dramatically.** Correct NAICS filtering
  will sharply reduce the duplicate count and change every summary number. Any
  dashboard or threshold that tuned itself to today's numbers will shift.
- **R5: Set-aside default.** Config currently injects six set-asides by
  default. Once L2 is fixed the filter becomes *real*, which will **narrow**
  results versus today. Phase 2 must confirm with the user whether set-aside
  filtering should be on by default at all.

## Success Criteria (module)

1. A live fetch returns a non-zero opportunity count and persists rows to
   `sam_opportunities`.
2. The scheduled 06:00 task runs without throwing and logs a real result.
3. An empty result set is reported as "no matching opportunities", never as
   "API endpoint not found".
4. NAICS, set-aside, PSC, and keyword filters each demonstrably affect results,
   or are removed from the UI.
5. Result sets larger than 1000 per NAICS are fully paginated.
6. A contract test suite exists that fails if the request parameter names or
   the endpoint shape regress.
7. `php artisan test` green; `vendor/bin/pint --dirty` clean.

# SAM Opportunities Fetch Repair — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | In Progress | 2026-08-24 | Diagnosis revised after live evidence; see "Corrections" below |
| 1 | Connectivity & credential verification | Awaiting Validation | 2026-08-24 | Complete. Endpoint behind config; `sam:diagnose` built and verified live |
| 2 | API request contract correction | Awaiting Validation | 2026-08-24 | `ncode` + `typeOfSetAside` fixed and proven live; pagination done. Cache-key set-asides (2.4) still open |
| 3 | Error semantics & diagnostics | Awaiting Validation | 2026-08-24 | 404 disambiguated; diagnostics now survive to the state file; failure alerting added |
| 4 | Scheduler & persistence integrity | Awaiting Validation | 2026-08-24 | Scheduler crash + DB column mismatch + swallowed errors all fixed. State double-write (4.2) still open |
| 5 | Filter fidelity | Not Started | 2026-08-24 | Open product question in 5.1 still needs the user |
| 6 | Operator visibility & regression suite | Not Started | 2026-08-24 | Queue-worker visibility + poll timeout still open |

## THE FEATURE NOW WORKS

Verified live on 2026-08-24 with a real end-to-end run (2 NAICS, 14 days,
nationwide):

```
endpoint: https://sam.gov/api/prod/opportunities/v2/search
rows before: 0
rows after:  82
state: success=true returned=82 before_dedup=82 dupes=0
  [2026-08-24] Stryker 26' Stretchers SFVAHCS | VETERANS AFFAIRS | naics=339113 psc=6515 | 36C26126Q0940
  [2026-08-24] 65--SUPPORT,LITTER              | DEPT OF DEFENSE  | naics=339113 psc=65   | SPE2DH26T6154
```

`before_dedup=82, dupes=0` is the proof that NAICS filtering is real now. The
last "successful" run before this (2026-06-12) pulled 9000 records and threw
away 8000 as duplicates, because all 9 NAICS queries returned the same
unfiltered result set.

Test suite: **686 passed, 1 failed, 5 skipped**. All 270 SAM tests pass. The one
failure is `VendorResourceTest > it updates and removes contacts through the
vendor edit form`, which is **pre-existing and flaky** — its factory uses
`fake()->phoneNumber()`, which intermittently produces a value the form's
validation rejects. Passes 4/4 in isolation; unrelated to this module.

## Corrections to the 2026-08-24 morning diagnosis

Three claims in the first pass were wrong or unsound and are corrected here.

1. **"The API key is the wrong type."** Wrong. It is a 36-char UUID and it
   works. The June 2026 run that pulled 9000 records used it. No key change is
   needed.
2. **"A keyless request returning 404 instead of 403 proves the route is
   gone."** The conclusion held, but the reasoning did not. That inference
   assumed `api.sam.gov` sits behind api.data.gov's key gate. It does not — it
   is GSA's own Istio/Envoy gateway with different error semantics (a real SAM
   route missing a key returns 400 or 500, never 403).
3. **"`naics` vs `ncode` needs live evidence before changing."** Correct at the
   time, and the evidence has now been gathered — see below. The in-code
   comment claiming "ncode returns empty" was simply false.

## Verified Diagnosis (2026-08-24, revised)

### Root cause A — `api.sam.gov` gateway fault (external) — WORKED AROUND

Every path on `api.sam.gov` returns HTTP 404 with a zero-length body
(`server: istio-envoy`), across 11 paths, 2 hosts, 3 auth modes (including no
key), 4 date ranges, 2 HTTP clients, and 2 independent networks. SAM.gov even
publishes description links pointing at `api.sam.gov` that do not resolve.
GSA's docs still list the host as current and there is no outage notice, so
this appears to be an unannounced GSA-side routing failure.

**Workaround applied:** `https://sam.gov/api/prod/opportunities/v2/search`
serves the identical v2 payload and is what the SAM.gov web UI itself consumes.
Confirmed HTTP 200 with `totalRecords: 24084` for August 2026. It is now set
via `config('services.sam.base_url')` / `SAM_API_BASE_URL`, so reverting to
`api.sam.gov` is a one-line `.env` change once GSA restores it.

**Caveat:** this host is not the documented public API surface. It may have
different rate limits and could start enforcing key validation or same-origin
checks without notice. Worth reporting to the Federal Service Desk. Treat as a
workaround, not a migration.

### Root cause B — scheduled fetch crashed on every run — FIXED

`bootstrap/app.php` called `FetchSamOpportunitiesTool::handle([...])`
statically; `handle()` is an instance method typed
`handle(Request $request): Response`. `Error` does not extend `Exception`, so
the surrounding `catch (Exception $e)` never caught it. Broken since the day it
was written (2025-12-29) — the 06:00 fetch has never once run.

Fixed by dispatching `FetchSamOpportunitiesJob` instead, and moved to
`routes/console.php` alongside the project's other scheduled tasks.

### Root cause C — every DB write failed (NEW; found 2026-08-24) — FIXED

`FetchSamOpportunitiesJob` wrote six columns that do not exist on
`sam_opportunities`: `department`, `classification_code`, `active`, `type`,
`links`, and `solicitation_number`. Every insert threw. The throw was swallowed
by a `catch (\Throwable)` that logged and returned, so the job still reported
`completed` and the queue showed zero failures.

Direct evidence from the one day the API worked:

```
[2026-06-12 17:41:44] SAM.gov multi-NAICS query completed {"status":"success","opportunities_returned":1000}
[2026-06-12 17:41:45] Failed to save opportunities to database
    {"error":"SQLSTATE[HY000]: table sam_opportunities has no column named solicitation_number"}
```

**So even on its best day this feature fetched 1000 opportunities and saved
zero.** The table has been empty its entire existence. This is independent of
Root cause A and would have kept the feature broken even if SAM.gov had never
faltered.

### L1/L2 — the two filter parameters were silently ignored — FIXED

Measured live on 2026-08-24 over 08/01–08/24 (`totalRecords`):

| Parameter | Result | Verdict |
|-----------|--------|---------|
| *(no NAICS filter)* | 24084 | baseline |
| `naics=423840` — what the app sent | **24084** | silently ignored |
| `ncode=423840` — GSA documented | **2** | correct |
| `ncode=339113` | **218** | correct |
| `setAsideCode=SBA` — what the app sent | **24084** | silently ignored |
| `typeOfSetAside=SBA` — GSA documented | **7845** | correct |
| `typeOfSetAside=` six codes comma-joined | **0** | single-valued; comma-join is a trap |

Set-asides are now sent only when exactly one code is requested, which
preserves today's effective behaviour (no narrowing) while making an explicit
single-code request work. Multi-set-aside support needs post-fetch filtering —
Phase 5.

## Timeline (from `storage/logs/laravel.log`, 68 MB)

| Date | Event |
|------|-------|
| 2025-12-29 | All SAM code introduced (`f5212999`); scheduler static-call bug ships same day |
| 2026-06-12 17:41 | **Only successful live run.** 9 NAICS × 1000 identical records → 9000, deduped to 1000. DB write failed with schema error; swallowed |
| 2026-07-09 22:09 | **First 404.** Byte-identical request to the June 12 one — same URL, same params, only dates differ |
| 2026-07-10 13:19 | `.env` last modified — *after* the break, so not its cause |
| 2026-08-08 | Still 404 |
| 2026-08-24 | Diagnosed and fixed; 82 rows persisted from live data |

## Was it an agent's change?

**No — and the git record is unambiguous.** The base URL has never differed
from `https://api.sam.gov/opportunities/v2/search` in any commit, on any
branch, in any stash. `ncode` never existed in application code — only in a
test. The only two commits between the last success and the first failure are
`b043db36` (a 3-line method rename inside `FetchSamOpportunitiesTool`) and
`20ba2cbf` (Vue/Vite only); neither touches `SamApiClient`, the URL, the query
params, or the HTTP stack. The outgoing request on 2026-07-09 was byte-identical
to the one that worked on 2026-06-12.

**But the instinct was half right.** Commit `270d235b` (2026-07-10, "Restore
full baseline test suite") changed the *test* to match the broken code:

```diff
-                && $request['ncode'] === '234567'
+                && $request['naics'] === '234567'
-                && $request['limit'] === 500;
+                && $request['limit'] === 1000;
```

The original test asserted `ncode` — the correct parameter. An agent "fixed"
the failing test by asserting the app's wrong value instead of investigating
why they disagreed, destroying the last signal that the NAICS filter was dead.
That did not cause the 404, but it is exactly the failure mode worth watching
for.

## Why 23 test files missed all of this

Every SAM test mocked the HTTP layer and asserted on the mocked *response*;
none asserted on the outgoing *request*. A test faking `Http::response($fixture)`
passes identically whether the client sends `ncode=`, `naics=`, or `banana=`.
And the test literally named *"job executes and persists results"* only checked
the state file — never a single database row.

## Changes Made (2026-08-24)

| File | Change |
|------|--------|
| `config/services.php` | `sam.base_url` + `sam.timeout` config, defaulting to the working host |
| `app/Services/SamApiClient.php` | `naics`→`ncode`; `setAsideCode`→`typeOfSetAside` (single-valued); `offset` added; base URL/timeout from config; `description` now mapped |
| `app/Jobs/FetchSamOpportunitiesJob.php` | Correct column mapping; wrapped in a transaction; DB failures rethrow instead of being swallowed; records without `notice_id` skipped; saved-vs-received count logged |
| `bootstrap/app.php` | Broken `withSchedule` block removed |
| `routes/console.php` | SAM daily fetch re-added as a job dispatch, with `catch (Throwable)` |
| `database/migrations/2026_08_24_120000_add_solicitation_number_to_sam_opportunities_table.php` | Adds the missing `solicitation_number` column |
| `tests/Feature/Jobs/FetchSamOpportunitiesJobPersistenceTest.php` | **New.** 4 tests asserting real DB rows, upsert behaviour, loud failure, and `notice_id` skipping |
| `tests/Feature/Schedule/SamScheduledFetchTest.php` | **New.** Runs the real registered schedule callback — the guard for Root cause B |
| `tests/Feature/Services/SamApiClientTest.php` + 4 other SAM test files | Fake URL patterns rekeyed from `api.sam.gov`/`naics=` to the configured host/`ncode=` |

## Open Questions (blocking Phase 5)

1. **PSC and keyword filters.** `applyFilters()` is a no-op with the comment
   "disabled per request", yet the panel still offers both fields and the state
   file reports them as applied. Make them real, or remove the UI?
2. **Multi-set-aside filtering.** Now that `typeOfSetAside` works but is
   single-valued, do you want the six configured set-asides applied via
   post-fetch filtering, or is no set-aside filter the desired default?

## Second pass, 2026-08-24 — monitoring, pagination, diagnostics

Shipped after the initial repair:

- **404 disambiguation.** Empty body → `endpoint_unreachable`, hard failure with
  a message naming the endpoint and `php artisan sam:diagnose`. JSON body →
  GSA's documented "no data", reported as success with 0 results. Non-JSON body
  → stays loud. The empty-body guard is what stops a real outage from being
  laundered into a plausible "0 results".
- **Failure alerting.** `SamFetchFailedNotification` emails
  `config('services.sam.alert_email')` when a fetch fails. It deliberately does
  **not** fire on a successful fetch returning zero opportunities — alerting on
  a normal narrow-query outcome would train the recipient to ignore it.
  Recipient set via `SAM_ALERT_EMAIL`, falling back to
  `BUSINESS_HUB_NOTIFICATION_EMAIL`.
- **Diagnostics preserved end to end.** `SamResponseBuilder::build()` routed its
  first error through `failure()`, which flattens a string into
  `['message' => …]` — dropping `naics`, `status_code`, and `error_type`. That
  is why the state file read `{"message":"API endpoint not found","naics":null,
  "status_code":null,"type":null}`. Now via `failureFromErrors()`:

  ```
  message     SAM.gov returned 404 with an empty body for https://api.sam.gov/... Run: php artisan sam:diagnose
  naics       423840
  type        endpoint_unreachable
  status_code 404
  ```

- **Pagination.** `fetch()` now pages on `offset` until `totalRecords` is
  satisfied, capped by `services.sam.max_pages` (default 10 = 10,000 records
  per NAICS). A mid-pagination failure returns the pages already gathered as a
  truncated partial rather than discarding them, and an empty page breaks the
  loop so a bad `totalRecords` cannot spin forever.
- **`sam:diagnose`.** Probes the endpoint with a key, without a key, and a
  `sam.gov` control, then prints a verdict. `--json` for monitoring; exits
  non-zero when unhealthy. Never prints the key (asserted by test). Verified
  live: `with_key 200 / totalRecords 8906`, `without_key 500`, `control 200`.

Tests: **291 SAM tests pass** (up from 270), full suite **707 passed**.

## Remaining Work

- **Cache key ignores set-asides (Phase 2.4)** — now that `typeOfSetAside` is
  functional, two queries differing only by set-aside share a cache key.
- **State-file double write (Phase 4.2)** — job and tool both write it.
- **Filter fidelity (Phase 5)** — `applyFilters()` is still a no-op while the
  panel advertises PSC and keyword fields. Blocked on the two open questions.
- **Queue-worker visibility + poll timeout (Phase 6)** — panel still polls
  forever with no warning if no worker is running.
- **Report the `api.sam.gov` outage to the Federal Service Desk** so the
  workaround can eventually be reverted.
- **Unrelated:** the suite has pre-existing test-isolation flakiness —
  `QuotePolicyTest > super_admins can access quotes` fails when run after
  `VendorResourceTest` and passes alone, with or without any SAM change.
  `VendorResourceTest` itself is separately flaky via `fake()->phoneNumber()`.

## Log

- 2026-08-24 — Investigation and plan drafted (6 phases). Initial diagnosis:
  external 404 + scheduler crash + 11 latent defects.
- 2026-08-24 — User challenged the "SAM.gov is broken" conclusion on the
  grounds that the feature worked until a change. Ran git archaeology and
  independent endpoint verification in parallel.
  - Git: no commit could have caused it; request byte-identical across the
    working and failing runs.
  - Endpoint: `api.sam.gov` confirmed dead from multiple vantage points, **and
    a working equivalent host found** at `sam.gov/api/prod`.
  - Logs: found the 2026-06-12 successful run, the 88.89% dedup rate proving
    NAICS filtering never worked, and the swallowed schema error proving the DB
    write never worked.
- 2026-08-24 — Implemented and verified: endpoint config, `ncode`,
  `typeOfSetAside`, scheduler dispatch, DB column mapping, loud failures, and
  two new regression test files. Live run persisted 82 real opportunities.
  Full suite 686 passed / 1 pre-existing flaky failure. `vendor/bin/pint --dirty`
  clean.

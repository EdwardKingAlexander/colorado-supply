# Phase 1 findings — new key and host (2026-11-13)

New key: 40 characters, `SAM-<uuid>` format. Old key: bare 36-character UUID.

## 1.2 Single-query probes (7-day window, `ncode=423840`, `limit=1`)

| Host | Status | Notes |
|---|---|---|
| `https://api.sam.gov/opportunities/v2/search` | 200 | `totalRecords: 0` for that NAICS/week (valid empty result). No rate-limit headers |
| `https://sam.gov/api/prod/opportunities/v2/search` | 200 | Identical body. Its `links.self` points at `api.sam.gov/prod/...`: same backend |

`ccode=53`, 14 days: **761** notices on both hosts, same first title.
Entity API (`sam.gov/api/prod/entity-information/v3/entities`, CAGE 1F9R6): 200, 1 record.
`php artisan sam:diagnose`: healthy, `totalRecords=6361` (7 days, no filters).

## 1.3 Full fetch on `api.sam.gov` (default NAICS + PSC, 14 days, nationwide, no cache)

- 52 NAICS only: 52/52 succeeded, 2,279 results, 61 s.
- NAICS + PSC: 104 requests, **101 succeeded, 3 timed out** (30 s cURL timeout on
  `psc:39`, `psc:5325`, `psc:9150`), 3,391 results. **No 429s**, no rate-limit
  headers.

## 1.4 Full fetch on the default `sam.gov/api/prod` (same parameters)

**104/104 succeeded**, 0 failed, 3,405 results (5,128 records before
dedup), 120 s. No key in the new log lines. This is the panel's real path.
Compared with the 2026-10-04 baseline of ~2,651 matches over 14 days, the
volume is in range and higher.

## Host decision

Keep `sam.gov/api/prod` as the default. The official host works with the new
key and did not rate-limit a full fetch. But it timed out on 3 of 104 requests
where the current default has been reliable, and both hosts serve the same
backend. Switching needs only `SAM_API_BASE_URL`, no code change.

## Security finding (fixed in Phase 2)

A cURL timeout message quotes the full request URL, so the key ended up in
`laravel.log`. The same message also feeds the fetch result's `error`, the
panel's failure list and the alert email. The 6 local log occurrences were
masked. `SamApiClient` now redacts `api_key=` from network and unexpected
exception messages.

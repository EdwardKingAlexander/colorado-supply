---
plan: sam-fetch-repair
phase: 2
title: API request contract correction
status: awaiting_validation
last_updated: 2026-08-24
depends_on: [phase-1]
checkpoint: see STATE.md "Changes Made"
---

# Phase 2 — API Request Contract Correction

## Goal

Make the outgoing SAM.gov request match the actual API contract, so that NAICS
and set-aside filters really filter and large result sets are fully retrieved.
Addresses L1, L2, and L4.

## Background

`SamApiClient::buildQueryParams()` currently emits:

```php
'naics'        => $naicsCode,                       // GSA documents: ncode
'setAsideCode' => 'SBA,WOSB,EDWOSB,SDVOSBC,8A,HUBZone', // GSA documents: typeOfSetAside, single-valued
'limit'        => 1000,                             // no offset, no pagination
```

SAM.gov ignores unrecognised query parameters rather than rejecting them. If
`naics` is not a real parameter, then **every one of the 9 per-NAICS queries
returns the same unfiltered result set** — which would explain large duplicate
counts and NAICS filtering that appears to do nothing, while looking
superficially like it works.

The existing code comment asserts the opposite (that `ncode` returns empty).
That claim could not be reproduced during investigation because every endpoint
404s. **Phase 1.4 settles this with live evidence. Do not start Phase 2 until
it has.**

## Sub-phases

### 2.1 — NAICS parameter — `checkpoint: naics-param`

Using the Phase 1.4 evidence, set the correct parameter name. Guard the
decision so it cannot silently regress:

- Introduce `config('services.sam.naics_param')` defaulting to the verified
  value, so a future SAM.gov change is configuration, not a code hunt.
- Replace the existing "ncode returns empty" comment with a dated note citing
  the 1.4 measurement (parameter used, window, `totalRecords` with/without).

**Definition of done:** two live queries for different NAICS codes over the
same window return *different* `totalRecords`, proving the filter is active.

### 2.2 — Set-aside parameter — `checkpoint: setaside-param`

- Rename `setAsideCode` → the verified parameter (`typeOfSetAside` per GSA).
- If it is single-valued (expected), comma-joining is invalid. Choose one:
  - **(a)** send no set-aside filter by default and let post-fetch filtering
    handle it (aligns with Phase 5, keeps one query per NAICS); or
  - **(b)** fan out one request per set-aside code — multiplies request count
    by 6 and will hit rate limits.
  Recommendation: **(a)**.
- **Confirm with the user before implementing** (risk R5): today the six
  default set-asides are silently *not* applied, so fixing this will **narrow**
  results relative to what the panel shows now. The user may want set-aside
  filtering off by default entirely.

### 2.3 — Pagination — `checkpoint: pagination`

`limit` caps at 1000 and `offset` is never sent, so any NAICS with more than
1000 matches is silently truncated. `totalRecords` is already parsed and
discarded.

- Add `offset` to `buildQueryParams()`.
- In `SamApiClient::fetch()`, loop: request → read `totalRecords` → continue
  while `count(fetched) < totalRecords`.
- Guardrails: hard cap on pages (config `sam.max_pages`, default 10), reuse the
  existing backoff between pages, and stop on any page error while **returning
  the records already gathered** as a partial success rather than discarding
  them.
- Surface `pages_fetched` and `truncated` (bool) in the per-NAICS result so the
  operator can see when the cap was hit.

### 2.4 — Cache key correctness — `checkpoint: cache-key`

`SamOpportunitiesCache::buildCacheKey()` hashes `place`, `days_back`,
`notice_type_codes`, `posted_from`, `posted_to` — but **not** set-asides. Once
2.2 makes set-asides functional, two different set-aside queries would collide
on one cache key and serve wrong results.

- Add `set_aside_codes` to the hash.
- Bump `PREFIX` to `sam_opp_v4` to invalidate stale entries.

## Files

**Modify**
- `app/Services/SamApiClient.php` (params, pagination)
- `app/Support/SamOpportunitiesCache.php` (key + prefix)
- `config/services.php` (`naics_param`, `max_pages`)

**Tests**
- `tests/Feature/Services/SamApiClientTest.php` (extend)
- `tests/Feature/Services/SamOpportunitiesCacheTest.php` (extend)
- `tests/Feature/Services/SamApiClientPaginationTest.php` (new)

## Tests

Contract assertions (these are what the current suite lacks — every existing
test mocks the transport without asserting the *outgoing query string*):

- `Http::fake()` + `Http::assertSent()` verifying the request contains the
  verified NAICS parameter name with the right value, and does **not** contain
  the legacy `naics=` key.
- Set-aside parameter name asserted explicitly; assert no comma-joined
  multi-value is sent if 2.2 resolves to (a).
- Pagination: fake a 2500-record response across 3 pages; assert 3 requests
  with `offset` 0/1000/2000 and 2500 mapped opportunities.
- Page cap: fake 50,000 records; assert the loop stops at `max_pages` and flags
  `truncated: true`.
- Mid-pagination failure: page 2 returns 500; assert page-1 records are
  returned as partial success, not dropped.
- Cache: two queries differing only by set-aside produce different cache keys.

## Success Criteria

1. Outgoing query string matches the live SAM.gov contract, asserted by test.
2. Different NAICS codes return different result sets (live check).
3. A >1000-record NAICS is fully retrieved, or explicitly flagged `truncated`.
4. Set-aside behaviour is intentional and user-confirmed.
5. `php artisan test` green; `vendor/bin/pint --dirty` clean.

## Risks / Blockers

- **Blocked on Phase 1.4.** Changing parameter names from documentation alone,
  while the endpoint is unreachable, risks trading one silent failure for
  another.
- R4: correct NAICS filtering will change every summary number in the UI and on
  the insights dashboard. Expected, but call it out so it is not read as a new
  bug.
- R5: set-aside default is a product decision, not a technical one — get the
  user's answer in 2.2 before coding.

STOP. Await phase approval.

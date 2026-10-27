---
plan: sam-psc-discovery
phase: 2
title: Term-based fetcher (NAICS + PSC)
status: completed
last_updated: 2026-10-04
depends_on: [phase-1]
checkpoint: 2.5-psc-filter-removed
---

# Phase 2 — Term-based fetcher (NAICS + PSC)

## Goal

Fetch by NAICS **or** PSC: resolver produces search terms, the client sends
`ncode` or `ccode` per term, cache and results are tracked per term, and each
deduped notice records which terms found it.

## Sub-phases

### 2.1 Resolver produces search terms
Steps:
1. Add `search_terms` to `SamParameterResolver::resolve()` output: list of
   `['dimension' => 'naics'|'psc', 'code' => string]`, NAICS first.
2. New param `psc_search` (array) → PSC terms. Phase 1 rules: accept 2-char
   numeric DLA groups (e.g. `53`) and 4-char alphanumeric PSCs (e.g. `5305`,
   `J053`); reject other lengths (3-char codes always return 0). Config gains
   `psc_groups` (2-char, default list from `findings.md` 1.2) next to `psc_codes`.
3. Keep `naics_codes` and add `psc_search_codes` in `query_metadata`.
4. Keep `psc_override` accepted for one release as an alias that maps to
   `psc_search` (MCP tool callers), with a deprecation log line.
DoD: resolver tests cover NAICS-only, PSC-only, both, invalid PSC, alias.

### 2.2 Client and cache are dimension-aware
Steps:
1. `SamApiClient::fetch` accepts a term; sets `ncode` for NAICS, `ccode` for PSC.
   Never both in one request.
2. `SamOpportunitiesCache` keys gain the dimension; bump `PREFIX` to `sam_opp_v5`.
3. Logs include `dimension` and `code` (replace the bare `naics` context key,
   keeping `naics` for NAICS terms so existing log searches still work).
DoD: unit tests assert request params per dimension and distinct cache keys for
`naics:5305xx`-style collisions.

### 2.3 Fetcher iterates terms and reports per term
Steps:
1. `SamMultiNaicsFetcher::fetchAll` accepts terms (keep a NAICS-array overload
   for existing callers/tests).
2. Per-term results/errors/truncation; summary adds `successful_terms`,
   `failed_terms`, and per-dimension counts. Keep existing `*_naics_*` keys
   populated for NAICS terms (panel and alerts read them).
3. 429 on a term: back off and retry once, then record as a failed term
   (partial success), never abort the run.
DoD: tests for mixed success, one 429 term, PSC-only run.

### 2.4 Dedup keeps provenance
Steps:
1. `SamOpportunityDeduplicator` merges `matched_terms` (e.g. `naics:332919`,
   `psc:4820`) when the same notice ID appears more than once.
2. Response/legacy format carries `matched_terms` per opportunity and a summary
   `found_only_via_psc` count.
DoD: test with one notice found by both a NAICS and a PSC term → one record,
two matched terms; `found_only_via_psc` correct.

### 2.4b Job timeout
Steps:
1. Raise `FetchSamOpportunitiesJob::$timeout` (300 → 600 s): Phase 1 measured
   ~107 terms ≈ 2–2.5 min per combined fetch.
DoD: test asserts the timeout value.

### 2.5 Post-fetch PSC filter removed from the fetch path
Steps:
1. Stop applying `psc_codes` as a post-fetch filter in
   `FetchSamOpportunitiesTool` (discovery replaces it).
2. Update `FetchSamOpportunitiesToolFiltersTest` accordingly.
DoD: filter tests updated; no silent narrowing by PSC remains.

## Files

Modify: `app/Support/SamParameterResolver.php`, `app/Services/SamApiClient.php`,
`app/Support/SamOpportunitiesCache.php`, `app/Services/SamMultiNaicsFetcher.php`,
`app/Support/SamOpportunityDeduplicator.php`, `app/Support/SamResponseBuilder.php`,
`app/Mcp/Servers/Business/Tools/FetchSamOpportunitiesTool.php` (schema text +
legacy format).

## Tests

- New: `tests/Feature/Services/SamPscSearchTermsTest.php` (resolver),
  `tests/Feature/Services/SamApiClientPscTest.php` (params, cache keys),
  `tests/Feature/Integration/SamPscDiscoveryIntegrationTest.php` (Http::fake:
  NAICS + PSC overlap, PSC-only hit, 429 term).
- Update: filter tests, cache key tests (v5 prefix).
- Run `php artisan test` (full) and `vendor/bin/pint --dirty`.

## Success criteria

- A faked run returns a notice found only via PSC, with correct provenance.
- Existing NAICS-only behavior unchanged (all existing SAM tests green).

## Risks / blockers

- Many tests assert NAICS-shaped summaries; keep compatibility keys to limit churn.
- MCP tool schema change: document `psc_search`; keep `psc_override` alias.

STOP. Await phase approval.

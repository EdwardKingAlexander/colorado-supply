# SAM.gov PSC Discovery — Overview

Supersedes Phase 3 ("Search scope and defaults") of `ai/modules/sam-volume-fixes`
for the PSC-discovery decision. Keyword discovery and an "all industries" mode
stay out of scope here.

## Problem

The fetcher finds opportunities **only** through NAICS codes: one SAM.gov
request per code (`ncode`), then merge and dedup. The "PSC Codes" field on the
control panel only *filters* notices already fetched under those NAICS codes
(`SamParameterResolver::resolvePscCodes`, applied post-fetch). So any notice
whose buyer picked a NAICS we don't search is invisible, even when its product
code (PSC) is exactly what we sell.

Evidence (360-day survey, 2026-10-04, `ccode` queried per PSC): buyers attach
widely varying NAICS codes to the same product. For PSC 4820 (valves), 193
postings used 6+ different NAICS codes. Expanding NAICS (done the same day:
19 primary + 33 secondary) raised estimated coverage of our product postings
from ~11% to ~72%. Searching by PSC directly closes most of the remaining gap
without guessing more NAICS codes.

## Goal

Discover opportunities by **NAICS OR PSC**: run per-PSC (`ccode`) queries
alongside per-NAICS (`ncode`) queries, merge, and dedup by notice ID, in both
the control panel and the daily 6am scheduled fetch (user decision 2026-10-04).

## Constraints

- SAM.gov `ccode` behavior is undocumented beyond "Classification Code"
  (open.gsa.gov). Verified live: `ccode=5305` returns notices whose
  `classificationCode` is exactly `5305`. **Unverified:** whether `ccode` does
  prefix matching, and how DLA notices that carry a 2-character PSC (e.g. `65`,
  `53`; seen on stored records such as `65--THERMAL GUARD`) are matched.
  Phase 1 settles this before any code changes.
  **Settled 2026-10-04 (see `findings.md`):** exact match only; DLA tags its
  notices with 2-char groups (`53`: 2,372 notices / 90 days), other agencies
  use 4-char PSCs. Query both.
- Daily API quota for this key is unpublished. Today's usage pattern (several
  20-code fetches plus a 65-call survey) succeeded, but ~80 calls per fetch must
  be measured, and 429 handling must degrade gracefully.
- `postedFrom`/`postedTo` must be ≤ 1 year apart; SAM rejects 365 days as
  "more than 1 year". Not a concern at the panel's 90-day cap, but noted.
- Existing behavior to preserve: per-code caching (`SamOpportunitiesCache`,
  prefix `sam_opp_v4`), pagination/completeness (sam-volume-fixes Phase 1),
  dedup (`SamOpportunityDeduplicator`), failure alerting, state-file and DB
  persistence, the per-code match counts on the panel.

## Architecture (textual)

Today: `FetchSamOpportunitiesTool::fetch` → `SamParameterResolver::resolve`
→ `SamMultiNaicsFetcher::fetchAll(naicsCodes)` → `SamApiClient::fetch(naics)`
sets `ncode` → cache per NAICS → `SamOpportunityDeduplicator` → filters
(keywords, PSC, set-aside) → `SamResponseBuilder` → job persists.

Proposed: the unit of fetching becomes a **search term** = `{dimension, code}`
where dimension ∈ {`naics`, `psc`}.

- `SamParameterResolver` returns `search_terms` (NAICS terms + PSC terms) while
  keeping `naics_codes`/`psc_codes` for metadata and backward compatibility.
- `SamApiClient::fetch` takes a term and sets `ncode` or `ccode` accordingly.
- `SamOpportunitiesCache` keys include the dimension (`sam_opp_v5_{dim}_{code}_{hash}`)
  so a NAICS and a PSC that share digits never collide. Bump prefix to v5.
- `SamMultiNaicsFetcher` (rename deferred; see risks) iterates terms; results,
  errors, truncation and counts are reported per term.
- Dedup already keys on notice ID, so overlap between a NAICS hit and a PSC hit
  collapses naturally. Each kept notice records which terms found it
  (`matched_terms`) for the panel and for measuring PSC value.
- PSC post-filter semantics change: the panel field becomes **"Also search
  these PSCs"** (discovery). Filtering by PSC moves to the results table's
  filter bar, next to the existing Notice / NAICS / State / Set-Aside filters.

## Data flow

```
form / schedule ─► resolver ─► [naics terms] + [psc terms]
                                   │ one request each (paged), cached per term
                                   ▼
                    merge ─► dedup by noticeId (keep matched_terms)
                                   ▼
            keyword / set-aside filters ─► response ─► state file + DB
                                   ▼
        panel: per-term match counts, "found only via PSC" count
```

## Phases

| # | Phase | File | Depends on |
|---|-------|------|-----------|
| 1 | `ccode` contract verification (live, read-only) | `phase-1-ccode-contract.md` | — |
| 2 | Term-based fetcher (NAICS + PSC) | `phase-2-term-fetcher.md` | 1 |
| 3 | Control panel, schedule and measurement | `phase-3-panel-and-schedule.md` | 2 |

## Success criteria (module)

1. A fetch with PSC terms returns notices that no selected NAICS would return,
   proven with a live run and recorded counts ("found only via PSC: N").
2. Overlapping notices appear once, with every term that found them.
3. The 6am scheduled fetch searches configured NAICS + PSC and stays within
   quota; a 429 on one term never fails the whole fetch.
4. All SAM tests pass; pint clean.

## Risks

- **Quota exhaustion** (~80 calls/fetch). Mitigation: measure in Phase 1,
  per-term cache, 429 back-off, and a config cap on terms per fetch.
- **Relevance drop**: broad 2-character PSCs (e.g. `65` medical) could flood
  results. Mitigation: Phase 1 decides which PSC granularity to query.
- **Cache migration**: prefix bump invalidates warm caches once (15-min TTL,
  negligible).
- **Naming debt**: `SamMultiNaicsFetcher` will fetch PSCs too. Renaming touches
  many tests; defer to a follow-up unless Phase 2 scope allows.

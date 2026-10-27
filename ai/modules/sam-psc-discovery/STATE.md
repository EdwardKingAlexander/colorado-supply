# SAM.gov PSC Discovery — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-04 | Approved by user 2026-10-04. Supersedes sam-volume-fixes Phase 3 (PSC discovery part) |
| 1 | `ccode` contract verification | Complete | 2026-10-04 | Exact match; DLA uses 2-char groups. See findings.md. Decision approved |
| 2 | Term-based fetcher (NAICS + PSC) | Complete | 2026-10-04 | Full suite 750 passed / 5 skipped; live smoke OK. Approved |
| 3 | Control panel, schedule and measurement | Awaiting Validation | 2026-10-04 | 756 passed / 5 skipped; live run 151 -> 2,651 matches |

## Decisions

- 2026-10-04 — User: the 6am scheduled fetch searches PSC as well as NAICS.
- 2026-10-04 — PSC field changes from post-fetch filter to discovery; PSC
  filtering moves to the results table.

- 2026-10-04 — User approved (Phase 1): query both 2-char DLA groups (25 default,
  43/25 opt-in) and 4-char PSCs.

## Log

- 2026-10-04 — Plan written. Context: NAICS list expanded same day (19 primary
  + 33 secondary) from a 360-day PSC→NAICS survey; Sources Sought `ptype` fixed
  (`s` → `r`). Survey evidence: `ccode=5305` returns exact-match PSC notices.
- 2026-10-04 — User approved the plan and started Phase 1.
- 2026-10-04 — Phase 1 done: ccode exact match; 2-char DLA groups add ~2,378 notices/90 days across 6 groups beyond our NAICS (~26/day); no rate-limit headers, no 429s; ~1 s/call; combined fetch ≈107 calls ≈2–2.5 min.
- 2026-10-04 — User approved Phase 1 findings and Phase 2 start.
- 2026-10-04 — Phase 2 implemented. Full suite: 750 passed, 5 skipped, 0 failed; pint clean.
  Live smoke (332722 + psc 53, 3 days, nationwide, applyLimit=false so the
  panel state file was not touched): 23 notices, 16 only via PSC, 7 via both
  (merged, both terms recorded).
  Deviations from the written plan (simpler, same outcome):
  - Resolver keeps `psc_codes` (now = PSC search list) instead of adding a
    separate `search_terms` array; the fetcher builds terms from naics_codes +
    psc_codes via a new optional `fetchAll(..., $pscCodes)` argument.
  - Metrics key `per_naics` kept (now per term) to avoid test churn; class not
    renamed (as anticipated in the overview risks).
  Found along the way: `SamResponseBuilder::formatMetadata` whitelists keys
  (new psc_* keys added), and `build()` judged success from NAICS counts only
  (now whole-run counts, so a PSC-only hit is not a failure).
  Observation (pre-existing, not changed): when a caller omits `place`, the
  resolver defaults to config `place_of_performance` = CO. The panel always
  sends `place`; MCP callers get Colorado-only unless they pass null.
- 2026-10-04 — User approved Phase 2 and started Phase 3.
- 2026-10-04 — Phase 3 implemented: panel field `psc_search` ("Also search these
  PSC codes", default = 25 DLA groups + 27 4-char PSCs, 43/25 opt-in, labelled),
  session restore keeps an emptied PSC list (deliberate NAICS-only); per-term
  match counts (NAICS + PSC, falling back to naics_code for older results);
  "Found only via PSC" card; PSC filter + column in results table and Excel
  export; failed-term labels show PSC; 6am schedule passes defaultPscSearch();
  `max_terms_per_fetch` (150) caps PSC terms, never NAICS. Config: dropped
  zero-yield PSCs 3030, 4245, 7935; added psc_descriptions.
  Tests: full suite 756 passed, 5 skipped, 0 failed; pint clean.
- 2026-10-04 — Live validation (job run synchronously; real fetch, saved to DB
  and panel state): same 20 NAICS as the morning baseline, 14 days,
  nationwide, small-business toggle off, + 52 PSC terms.
  Baseline NAICS-only: 151 fetched. Now: 2,725 fetched, 2,651 unique, 74
  duplicates merged, **2,490 found only via PSC**, 1,034 SBA set-asides, 72
  terms all succeeded, 108 s. Top PSC terms: 59 (651), 53 (490), 61 (297),
  48 (288), 47 (156), 30 (148). Caveat: that baseline NAICS list predates the
  manufacturing-code expansion, so "only via PSC" overstates what PSC adds on
  top of today's 19-code primary NAICS list (Phase 1 estimate: ~26/day extra).
  Operator action: restart the queue worker to pick up the new code.

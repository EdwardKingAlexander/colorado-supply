---
plan: sam-psc-discovery
phase: 1
title: ccode contract verification
status: completed
last_updated: 2026-10-04
depends_on: []
checkpoint: 1.3-quota-latency
---

# Phase 1 — `ccode` contract verification (live, read-only)

## Goal

Establish, with live evidence, exactly how SAM.gov's `ccode` parameter matches
PSC codes, so Phase 2 queries the right codes at the right granularity. No
production code changes in this phase.

## Background

Known: `ccode=5305` (90 days) returned 15 notices, all `classificationCode=5305`.
Unknown: prefix matching; 2-character PSCs used by DLA (`65`, `53`, `47`...);
service PSCs (letters, e.g. `J053`); quota cost.

## Sub-phases

### 1.1 Probe matching semantics
Steps:
1. Extend the scratch probe (`ai/modules/sam-psc-discovery/probe.php`, copied
   from the 2026-10-04 survey script) to record `classificationCode` per result.
2. Query over the same 90-day window: `ccode=53` vs `ccode=5305`; `ccode=65`
   vs `ccode=6515`; `ccode=530` (3-char); `ccode=J053`.
3. Record for each: totalRecords, distinct `classificationCode` values returned.
Definition of done: a table in `findings.md` answering "exact or prefix?" and
"how are 2-char DLA PSCs matched?", with raw counts.

### 1.2 Decide query granularity
Steps:
1. Compare overlap: notices found by `ccode=65` that `ccode=6515/6532/...`
   miss, and vice versa.
2. Estimate relevance of 2-char group queries (sample 20 titles from `65`).
3. Write the decision: which configured PSCs to query as 4-char, which FSG
   groups (if any) to add as 2-char terms.
Definition of done: decision recorded in `findings.md` with rationale.

### 1.3 Measure quota and latency
Steps:
1. Record response headers for rate-limit hints (`X-RateLimit-*` or similar).
2. Time a full simulated fetch: 52 NAICS + chosen PSC terms, 14 days.
3. Note any 429 responses and their bodies.
Definition of done: calls per fetch, wall time, and observed limits recorded.

## Files

- Create: `ai/modules/sam-psc-discovery/probe.php`, `findings.md`
- No app/test changes.

## Tests

None (research phase). Evidence is the recorded probe output.

## Success criteria

- `ccode` matching semantics documented with live counts.
- PSC term list for Phase 2 decided (4-char and/or 2-char).
- Quota/latency budget for a combined fetch documented.

## Risks / blockers

- Probing consumes quota (~15–80 calls). Run once; reuse saved JSON.
- SAM gateway faults (seen in sam-fetch-repair-complete) could confuse results; retry
  failures once before recording.

STOP. Await phase approval.

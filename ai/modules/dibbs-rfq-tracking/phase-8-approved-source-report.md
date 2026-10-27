---
plan: dibbs-rfq-tracking
phase: 8
title: Approved-source report and CAGE lookup
status: in_progress
last_updated: 2026-10-04
depends_on: [phase-4]
checkpoint: 8.2-report-page
---

# Phase 8 — Approved-source report and CAGE lookup

Added 2026-10-04 at the user's request (item 5 of the Phase 7 wrap-up). The
user has no catalog yet; this is the fastest path to approved-source matches.

## Goal

Show which manufacturers DLA approves most often on open RFQs in our product
codes, with real company names, and let the operator add one as a source in
one click (which turns on approved-source matching for its RFQs).

## Verified facts (2026-10-04, live)

- SAM.gov Entity Management API v3 answers on
  `https://sam.gov/api/prod/entity-information/v3/entities`
  (`api.sam.gov` still 404s, same gateway issue as the opportunities API).
- `cageCode` accepts up to 100 codes per request, separated by `~`
  (commas return 0 records). Results are paged 10 per request (`page`,
  `size=10`), so 100 codes cost up to 10 requests. One call named the top 5 CAGEs: Cardinal Health
  (07TA6), Oshkosh Defense (75Q65), Medline (0PMN3), SPS Technologies (56878),
  Hi-Shear (73197).
- Rate limit: 10 entity requests/day for a personal key without a SAM role,
  1,000 with one (open.gsa.gov). Hence batching and a local cache.

## Sub-phases

### 8.1 CAGE directory
- `cage_entities` table (CAGE, legal name, city/state/country, UEI,
  registration status, found flag, fetched_at); `CageDirectory::lookup()`
  fetches only unknown or >30-day-old codes, 100 per request, caches not-found.
- DoD: tests with Http::fake (batching, `~` separator, cache, not found).

### 8.2 Approved Sources page (Automation)
- Top approved CAGEs by open RFQs in our product codes (and NSN count), name
  from the directory (fallback: DLA file name), location, status, "You buy from".
- "Look up company names" (one SAM.gov request for up to 100 codes).
- "Add as manufacturer" → Manufacturer with name + CAGE → match rebuild →
  "N RFQs now match".
- DoD: page tests for ranking, lookup action, add action and resulting matches.

## Risks

- Daily entity quota (10/day without a SAM role): lookups are batched and
  cached, and only on demand.
- Adding a manufacturer means "we can buy from them": the action says so.

STOP. Await phase approval. (Approved in the same request: "move forward with #5".)

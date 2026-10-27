---
plan: dibbs-rfq-tracking
phase: 1
title: Discovery spike — layouts, access, fixtures
status: completed
last_updated: 2026-10-04
depends_on: []
checkpoint: 1.5-compliance-note
---

# Phase 1 — Discovery spike: layouts, access, fixtures

## Goal

Replace every planning assumption about DIBBS files with verified facts:
exact layouts, how automated access behaves with an honest client, file
timing/retention, and real fixture files for tests. No app code.

## Sub-phases

### 1.1 Capture official layouts
Steps:
1. Operator (in a browser, after accepting the banner) saves:
   `/Rfq/RfqFileDefs.aspx` (RFQ file definitions) and
   `/Refs/help/Quoting/Batch/Default.aspx` (batch quote help) as HTML/PDF into
   `ai/modules/dibbs-rfq-tracking/reference/`.
2. Transcribe into `findings.md`: IN record field positions/lengths; BQ CSV
   columns (which are pre-filled vs vendor-entered, required vs optional);
   AS file columns.
DoD: `findings.md` has a field table per file type with source citation.

### 1.2 Collect fixtures
Steps:
1. Download one recent `inYYMMDD.txt` and `bqYYMMDD.zip` manually.
2. Store trimmed fixtures (≈50 records each, real data; public information) in
   `tests/Fixtures/dibbs/`.
3. Note record counts of the full files (sizing for indexes/retention).
DoD: fixtures committed; full-file sizes recorded.

### 1.3 Verify automated access (honest client)
Steps:
1. Scratch script (Laravel HTTP client) with UA
   `ColoradoSupplyRfqTracker/1.0 (+contact email)`, cookie jar, 2 s interval:
   GET listing → detect consent form → POST acceptance → GET listing → GET one
   `in` file.
2. Record: does it work? Required form fields? Cookie names/lifetime? Does the
   F5 firewall block a non-browser UA? Response for a missing date (302→HTML?).
3. **If blocked:** stop. Do not switch to a spoofed UA. Record the block and
   mark automated download "fallback-only" in `STATE.md`; Phase 2 then ships
   upload-first.
DoD: access verdict (works / blocked) with evidence in `findings.md`.

### 1.4 Timing, retention, identity
Steps:
1. When are daily files posted (time of day, business days only)? How many days
   does the listing retain?
2. Confirm the unique key of an RFQ line (solicitation + NSN + PR?) and whether
   amendments reissue the same solicitation in later files.
3. Confirm AMSC values present and their meaning (DLA AMSC code table) for the
   approved-source filter.
DoD: schedule time, retention window, unique key and AMSC table recorded.

### 1.5 Compliance check
Steps:
1. Record the consent banner text (verbatim) in `reference/`.
2. Note any DIBBS terms on automated retrieval found during 1.1–1.3; if any
   prohibit it, Phase 2 goes upload-only and the user is told.
DoD: compliance note in `findings.md`, reviewed by the user.

## Files

Create: `ai/modules/dibbs-rfq-tracking/findings.md`, `reference/*`,
`tests/Fixtures/dibbs/*`, scratch probe script (module folder).

## Tests

None (research). Fixtures produced here drive Phase 2 tests.

## Success criteria

- Field layouts for IN, BQ, AS documented from DLA's own pages.
- Automated access verdict recorded with evidence.
- Fixtures available; unique key, timing, retention, AMSC semantics known.

## Risks / blockers

- Layout pages require the banner; operator assistance may be needed for 1.1.
- If DLA blocks automation, scope of Phase 2 shifts to upload-first (still
  delivers all features, minus the daily auto-download).

STOP. Await phase approval.

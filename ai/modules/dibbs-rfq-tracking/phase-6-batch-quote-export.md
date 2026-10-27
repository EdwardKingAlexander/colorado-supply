---
plan: dibbs-rfq-tracking
phase: 6
title: Batch quote export
status: in_progress
last_updated: 2026-10-04
depends_on: [phase-3]
checkpoint: 6.2-export
---

# Phase 6 — Batch quote export

## Goal

Let the operator price many RFQs in the app and export one DIBBS batch quote
file to upload in DIBBS, instead of keying each quote by hand.

## Principles

- **The app never submits quotes and never stores DIBBS credentials.** The
  operator downloads the file, reviews it, and uploads it in DIBBS.
- Start from DLA's own pre-filled template row (`bq*.txt`, preserved verbatim
  in Phase 2) and fill only vendor-entered fields. Never reconstruct DLA's
  pre-filled fields from our parsed data.
- Exact column rules come from the Phase 1 batch-quote help capture.

## Sub-phases

### 6.1 Quote worksheet
Steps:
1. From the RFQ list: bulk action "Add to quote worksheet" (status → `quoting`).
2. Worksheet page: one row per RFQ with vendor fields from the Phase 1 spec
   (e.g. unit price, delivery days, quantity quoted, part number/CAGE offered,
   any required representations), prefilled from catalog/procurement history
   where possible. Validation mirrors DLA's rules.
DoD: worksheet tests for validation and prefill.

### 6.2 Export
Steps:
1. "Export batch quote file": merges worksheet values into the preserved
   template rows, writes DLA's format (delimiter, quoting, line endings,
   filename convention per spec), downloads it.
2. Pre-export review screen listing every quote and the representations being
   made; explicit confirmation checkbox.
3. Mark exported RFQs `quoted` with exported values snapshot (activity-logged).
DoD: golden-file test comparing export output with a hand-verified expected file.

### 6.3 Real-world verification
Steps:
1. Operator uploads one exported file (1–2 low-value quotes) to DIBBS and
   confirms acceptance; record the result in `STATE.md`.
DoD: one accepted upload recorded.

## Files

Create: worksheet page, `App\Services\Dibbs\BatchQuoteExporter`, tests, golden
fixture. Modify: DIBBS resource (bulk action), tracking model (quote fields).

## Tests

`tests/Feature/Dibbs/BatchQuoteExporterTest.php` (golden file),
worksheet tests. Full suite + pint.

## Success criteria

- DIBBS accepts an exported file on a real upload.

## Risks / blockers

- Format strictness: any deviation rejects the whole upload; golden tests and
  the 6.3 real upload guard this.
- Legal: quotes include certifications; the review screen states this plainly.

STOP. Await phase approval.

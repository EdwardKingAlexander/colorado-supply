---
plan: dibbs-rfq-tracking
phase: 3
title: RFQ tracking UI and pipeline
status: completed
last_updated: 2026-10-04
depends_on: [phase-2]
checkpoint: 3.4-dashboard-widget
---

# Phase 3 — RFQ tracking UI and pipeline

## Goal

An admin page where an operator triages the day's RFQs quickly (desktop and
phone), and tracks each pursued RFQ from review to award.

## Sub-phases

### 3.1 DIBBS RFQs resource (list)
Steps:
1. Filament resource `DibbsRfqResource` (navigation group "Automation", next to
   the SAM control panel).
2. Columns: return-by date (with "closes in N days"), solicitation, NSN,
   nomenclature, qty + unit, FSC, set-aside, AMSC, status.
3. Mobile layout per `mobile-ui-reformat` conventions (condensed summary column
   below `md`, as done for CRM Opportunities on 2026-10-04).
4. Default sort: return-by ascending; hide closed RFQs by default.
DoD: list renders fixture data; mobile column test.

### 3.2 Filters
Steps:
1. FSC (multi-select; default = `config('sam_opportunities.psc_codes')` 4-digit
   FSCs, so DIBBS and SAM share one product-code list).
2. Closing within (days), set-aside, AMSC (with "approved-source restricted"
   grouping from Phase 1 AMSC table), quantity range, status, "new today".
3. Saved default filter state per user.
DoD: filter tests for each.

### 3.3 Tracking pipeline
Steps:
1. `dibbs_rfq_trackings` migration/model (company-scoped, activity-logged).
2. Row/record actions: set status (`reviewing`, `quoting`, `quoted`, `no_bid`,
   `won`, `lost`), assign to admin, add note; bulk "mark reviewing / no-bid".
3. View page: RFQ details, link to the DIBBS RFQ record, approved sources
   (from AS file), tracking history (activity log), notes.
DoD: action tests; activity log entries asserted.

### 3.4 Dashboard widget
Steps:
1. Small stats widget on the DIBBS page: new today in our FSCs, closing in 3
   days, in quoting, quoted awaiting award.
DoD: widget test with fixture counts.

## Files

Create: `app/Filament/Resources/DibbsRfqResource*`, `app/Models/DibbsRfqTracking.php`,
migration, widget.

## Tests

`tests/Feature/Dibbs/DibbsRfqResourceTest.php`, tracking action tests. Full
suite + pint.

## Success criteria

- Operator can find today's in-FSC RFQs closing soonest and set a status on
  phone and desktop.
- Every status change is logged with who/when.

## Risks / blockers

- Volume: verify list query performance on a full day's file (indexes from 2.1).

STOP. Await phase approval.

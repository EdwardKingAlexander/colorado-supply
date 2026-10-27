---
plan: dibbs-rfq-tracking
phase: 4
title: NSN catalog and approved-source matching
status: completed
last_updated: 2026-10-04
depends_on: [phase-2, phase-3]
checkpoint: 4.3-ui
---

# Phase 4 — NSN catalog and approved-source matching

## Goal

Surface the RFQs we can actually win: NSNs we already source, and NSNs where a
manufacturer we buy from is a DLA-approved source.

## Background

`MilSpecPart` (nsn, manufacturer_part_number, manufacturer), `Manufacturer`
(cage_code), `Supplier` (cage_code), `mil_spec_part_supplier`, and
`ProcurementHistory` exist but are **empty** (2026-10-04). Matching is only as
good as this catalog, so an import comes first.

## Sub-phases

### 4.1 Catalog import
Steps:
1. CSV import for MilSpecParts (nsn, description, manufacturer name + CAGE,
   manufacturer part number, supplier, supplier part number), following the
   `category-csv-import-complete` module pattern (preview, validation, row errors).
2. Manufacturers/suppliers created or matched by CAGE.
DoD: import tests (valid, duplicate NSN, bad NSN, unknown manufacturer).

### 4.2 Matcher
Steps:
1. `DibbsRfqMatcher` runs after each import and after catalog changes:
   - `nsn_catalog`: RFQ NSN ∈ MilSpecPart NSNs.
   - `approved_source_cage`: RFQ NSN has an approved source whose CAGE belongs
     to a Manufacturer/Supplier we have.
   - `fsc`: RFQ FSC ∈ our FSC list (weakest signal).
2. Writes `dibbs_rfq_matches` (company-scoped) with reason and reference;
   simple score (catalog 100, approved source 70, FSC 10).
3. Idempotent: rerunning produces the same rows.
DoD: matcher tests per reason, combined reasons, rerun idempotency.

### 4.3 UI
Steps:
1. "Match" badges and a "Matched only" filter on the RFQ list; sort by score.
2. RFQ view: matched MilSpecPart, its suppliers and last procurement history
   price (from `ProcurementHistory`) as a pricing reference.
3. From an RFQ: "Add NSN to catalog" quick action.
DoD: resource tests for badges/filter; view shows procurement history.

## Files

Create: `app/Services/Dibbs/DibbsRfqMatcher.php`, `dibbs_rfq_matches` migration
+ model, MilSpecPart import page/importer. Modify: DIBBS resource, MilSpecPart
resource (import action).

## Tests

`tests/Feature/Dibbs/DibbsRfqMatcherTest.php`, catalog import tests. Full suite + pint.

## Success criteria

- With a small real catalog loaded, matched RFQs appear first, with reasons.

## Risks / blockers

- Catalog data entry is the user's effort; provide a template CSV.
- AMSC restrictions: an approved-source match on a restricted AMSC still needs
  the exact approved part; show the approved part number prominently.

STOP. Await phase approval.

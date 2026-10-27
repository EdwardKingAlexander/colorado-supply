# NSN sourcing workflow

Replace the unreliable scraper with: **PUB LOG cross-reference -> CAGE company verification -> sales contact -> recorded production/distributor confirmation**.

Plan requested and approved 2026-10-04; phase 1 is validated and phase 2 implementation is authorized. No supplier outreach has occurred. Execution authority follows PROJECT_CANNON.md and CLAUDE.md. [State](STATE.md) | [Review evidence](review.md) | [Verified source contract](source-contract.md)

## Architecture and boundaries

```text
Official PUB LOG releases / operator-supplied official ZIP files
  -> validated streaming import -> staged catalog snapshot -> atomic activation
NSN / NIIN / part number / CAGE query
  -> indexed local catalog -> all matching item/reference/CAGE relationships
  -> PUB LOG company snapshot -> Open CAGE Search + recorded verification
  -> company sales contact -> inquiry draft/call -> response evidence
  -> confirmed production status and/or distributor referrals
```

The importer is Laravel-native; ordinary lookups do not depend on Python, crawling, or DLA being online. Reference records describe catalog relationships, not proof of current production, stock, manufacturer status, or distributor authorization. Show source release dates and verification dates separately. No-match means absent from the loaded public dataset, not a nonexistent NSN.

Use Identification, Reference and CAGE CSV products first. History is a later optional dataset; never infer discontinued production from catalog absence. Additional management/characteristics datasets and procurement history are outside initial scope.

Catalog records are shared public reference data. Private sourcing cases, contact notes, inquiry drafts and responses belong to the requesting company and authorized operators. Resolve both admin and user guards during phase 1. Existing MilSpecPart, Manufacturer and Supplier models have no visible company scope: do not attach private workflow notes to their shared records.

Proposed model boundaries (final columns and source keys are confirmed in phase 1):
- PubLogImport / catalog version: source URL, published version/date, fetched/imported timestamps, hashes, schema signature, row/error counts, status, activation record.
- NsnCatalogItem: version, NSN/NIIN as strings, item name and source attributes.
- NsnReference: version, item, original part/reference number, conservative search key, CAGE, relationship/qualification codes and raw provenance. Preserve every distinct source relationship.
- CageEntity: versioned public company name, address and status; code stored as text.
- NsnSourcingCase + CageVerification + SalesContact + SourcingInquiry + SourcingConfirmation: tenant/actor, selected catalog references, contact origin, verification evidence, dated responses and distributor referrals. Retain the reference version used by a case.
- Existing MilSpecPart remains a curated record. Explicit user selection can link a catalog reference; import never silently overwrites a preferred manufacturer, manually edited data, or suppliers. Historical scraper records remain visibly unverified.

Part numbers are not globally unique. Exact part-number searches can return several NSNs and CAGEs; show all with pagination. Do not destructively strip punctuation or leading zeroes. Reference codes must be retained and interpreted from official documentation, with unknown codes displayed explicitly.

CAGE automation is not assumed. Initial supported path is imported CAGE identity plus an operator opening https://cage.dla.mil/Search, copying the selected code, and recording the result/date. Add direct automation only if phase 1 verifies a documented permitted interface. A PUB LOG identity must remain labeled as a snapshot until checked.

Sales workflow provides sourced contact details, an editable inquiry and manual email/call handoff, response capture and follow-up dates. Ask whether the exact part is still made, any replacement, MOQ/lead time, direct sales availability, and authorized distributors. This plan includes no automatic sending or inbox integration. A confirmation requires a dated response/source and applies to a particular part/CAGE pair; no response stays unknown.

## Sequential phases

| Phase | Objective | Lifecycle |
|---|---|---|
| [1](phase-1-source-contract.md) | Verify source files, access and data contract | COMPLETE |
| [2](phase-2-catalog-import.md) | Build versioned catalog and reliable refresh | VERIFYING (production gates deferred) |
| [3](phase-3-lookup-experience.md) | Replace lookup across UI, service, job and MCP | BLOCKED (verification) |
| [4](phase-4-company-sales-workflow.md) | Verify companies and record sales confirmations | NOT_STARTED |
| [5](phase-5-validation-rollout.md) | Validate, document operations and prepare rollout | NOT_STARTED |

Only one phase executes at a time. Plan approval precedes phase approval; completed phases require recorded verification and human validation.

## Risks and unresolved decisions

Phase 1 verified actual headers, code definitions, joins and full archive reads. Browser downloads succeeded; direct HTTP requests return 403, so support local official-file import. P_HELP requires Windows-1252 for this release. Include one-to-many standardization/cancellation context to distinguish inactive catalog records. No public CAGE API was verified; browser handoff is the baseline.

The user upgraded the Forge server to 2 GB RAM / 50 GB disk with MySQL 8.0. Initial inspection was of the earlier 1 GB / 25 GB server; its utilization is historical. The original uncompressed MySQL sample projected 29.5 GB per catalog release. The user approved schema optimization first; lossless columns and binary hashes reduce that estimate to 21.04 GB without requiring compression. A full optimized release is being measured locally. Production activation requires measured capacity and refresh headroom. No further paid infrastructure change is authorized. See schema-optimization.md and phase-2-verification.md for evidence and limits.

Release dates can lag advertised cadence. Refresh should inspect release metadata, use bounded retries and retain the last valid snapshot. Failed or partial imports must never replace a working catalog. Snapshot retention must preserve case evidence.

Initial UI remains in the existing admin NSN area. Customer-portal exposure, automatic email, paid datasets, restricted WebFLIS access, stock/pricing guarantees, bulk marketing and unrelated SAM work are out of scope.


User update 2026-10-04: server upgraded to 2 GB RAM / 50 GB disk. This supersedes the earlier 1 GB / 25 GB observation. Full MySQL capacity and refresh headroom still require measurement before production loading.




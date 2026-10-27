# Phase 1 - Verify source contracts and feasibility

- Lifecycle: COMPLETE - user validated phase 1 and approved phase 2 on 2026-10-04.
- Objective: Verify source contracts and feasibility.
- Dependencies: None beyond plan and phase approval.
- Entry criteria: Overall plan and phase-1 scope approved; official source access available or operator-supplied files can be obtained.

## Scoped tasks

- [x] Obtain the official current Identification, Reference and CAGE layout documents and representative real CSV records. Record URLs, dates, header/encoding/delimiter rules, join keys, leading-zero handling and relationship-code definitions. Reconcile removed 2026 fields.
- [x] Measure archive/extracted sizes, row counts and projected database/index space on the actual hosting budget. Choose streaming/batching strategy and measurable import/lookup budgets.
- [x] Verify permitted download mechanism and CAGE access. Document the browser handoff if no documented API exists; do not infer undocumented endpoints.
- [x] Map existing admin/user guards, company ownership, policies, VendorContact reuse and legacy manufacturer constraints. Finalize new tables and explicit curated-record linking rules.
- [x] Select known records covering NSN, NIIN, repeated part numbers, multiple CAGEs, missing company, no-match and inactive references. Save small attributable fixtures and a decision record.

## Files to create or modify

Create source-contract.md and fixtures/ in this module; inspect config/database.php, config/queue.php, app/Models/Admin.php, app/Models/User.php and existing policies without changing environment files.

## Verification

Compare sampled joins and field meanings to official layouts; record measured sizes and access results. No implementation tests until code is approved.

## Exit criteria

Actual file contracts, sample joins, hosting budgets, ownership mapping and CAGE fallback are documented; unresolved blocking assumptions are resolved or explicitly brought back for a scoped plan revision.

## Risks

403 responses, inaccessible files, ambiguous reference codes, and insufficient disk may change scope.

## Out of scope

Production imports, schema migrations, application changes and outreach.

## Verification evidence - 2026-10-04

- [Source contract](source-contract.md), [full-file measurements](source-measurements.json), [benchmark](sample-benchmark.json), [verification summary](verification-summary.json), [fixtures](fixtures/README.md).
- All 9 CSV members fully parsed, 77,370,292 records total, no malformed rows, ZIP CRC checks passed. P_HELP Windows-1252 requirement recorded.
- Four independent 100,000-row native PHP/SQLite samples used indexes and 2 MiB PHP-reported peak memory; limitations documented.
- Ten fixture assertions passed. Production Forge server inspected read-only: 1 GB RAM, 25 GB SSD, MySQL 8.0. Full safe refresh will require added capacity. No infrastructure changes made.
- Current CAGE access requires an I Agree interaction; no agreement accepted or live identity asserted. Browser handoff remains the supported contract.
- Production importer remains unimplemented. Full application suite is not run for this research-only phase; no application code or schema was changed.
- Scope clarification for review: build phase 2 locally; validate MySQL performance and approve capacity before production import. Include standardization/cancellation status and P_HELP in the first importer because actual files contain inactive records.

Phase 1 validated by user. Phase 2 authorized.



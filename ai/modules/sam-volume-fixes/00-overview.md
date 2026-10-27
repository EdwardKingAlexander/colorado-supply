# SAM result-volume fixes

The user approved fixing the investigated issues one by one on 2026-10-04. Phase 1 is authorized now; later phases follow validation. Preserve unrelated category work.

Data flow: API pages -> per-NAICS cache -> merge/filter -> response/state -> panel and persistence.

1. [Pagination and completeness](phase-1-pagination.md): COMPLETE
2. [Ingestion and display limits](phase-2-limits.md): VERIFYING (automated checks passed; awaiting user validation)
3. [Search scope and defaults](phase-3-scope.md): NOT_STARTED

State: [project tracker](../../../STATE.md). Evidence: [investigation](../sam-result-volume-complete/findings.md).

Risks: API totals can change during paging; fetching all pages increases traffic; tests encode the wrong contract. No environment changes or live jobs.



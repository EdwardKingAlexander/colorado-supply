# Report Builder — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Awaiting Plan Approval | 2026-10-23 | Design approved in chat; written plan awaiting approval |
| 1 | Engine foundation: datasets, tenant scope, compiler, filters | Not Started | 2026-10-23 | |
| 2 | Aggregation, formulas, post-processing, pivots | Not Started | 2026-10-23 | |
| 3 | Saved reports, sharing, preview API, templates | Not Started | 2026-10-23 | |
| 4 | Builder UI | Not Started | 2026-10-23 | |
| 5 | Charts and dashboard pins | Not Started | 2026-10-23 | |
| 6 | Exports: CSV, XLSX, PDF | Not Started | 2026-10-23 | |
| 7 | Scheduled email delivery | Not Started | 2026-10-23 | |
| 8 | Hardening, performance, legacy retirement | Not Started | 2026-10-23 | |

## Log

- 2026-10-23: Plan written. Owner decisions:
  - **Data:** orders and lines, payments and shipments, quotes and quote requests.
  - **Features:** all four extras: saved reports and sharing, charts and dashboard pins, scheduled email, pivots, formulas and conditional formatting.
  - **Access:** company-wide with no roles.
  - **Architecture:** Approach A (server-side semantic layer).
  - **Defaults accepted with the design:** company-only schedule recipients, no chart images in PDF v1, product vendor not exposed, `America/Denver` default schedule timezone.
  - Found during exploration: the legacy `monthRows()` uses SQLite-only `strftime()` (fixed via `SqlDialect` in phase 2.6).
  - Awaiting plan approval.

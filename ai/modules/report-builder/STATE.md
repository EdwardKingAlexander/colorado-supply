# Report Builder — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-23 | Owner approved ("commit the plan and then start on phase 1"); committed beed60c8 |
| 1 | Engine foundation: datasets, tenant scope, compiler, filters | Complete | 2026-10-23 | Owner approved ("commit and move forward"). 128 new tests pass (SQLite). Full suite: 1328 passed / 5 skipped / 1 failed; the failing test is unrelated and different each run (see log). MySQL execution is checked in phase 8.1 |
| 2 | Aggregation, formulas, post-processing, pivots | In Progress | 2026-10-23 | Owner approved start |
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
- 2026-10-23: Plan approved and committed (beed60c8). Phase 1 started.
- 2026-10-23: Phase 1 built (uncommitted):
  - `app/Reporting/`:
    - `FieldType`, `Operator`, `DateBucket`, `RelativeDate`, `Limits`, `TenantScope`, `DatasetRegistry`, `ReportRunner`, `ReportResult`
    - `Fields/` (`Field`, `Join`, shared `OrderFields`, `QuoteFields`, `ProductFields`, `LocationFields`)
    - `Datasets/` (the six datasets on an abstract `Dataset`)
    - `Sql/` (`SqlDialect`, `MySqlDialect`, `SqliteDialect`)
    - `Definition/` (`ReportDefinition`, `ColumnSpec`, `FilterGroup`, `FilterRule`, `SortSpec`, `DateValue`, `ReportDefinitionValidator`, `InvalidReportDefinition`)
    - `Query/` (`QueryCompiler`, `CompiledReport`)
  - `UserDashboardDataService` now uses `TenantScope` for both order queries (`baseOrdersQuery`, `recentOrders`). The dashboard tests pass 18/18.
  - Tests:
    - `tests/Unit/Reporting/{DatasetRegistryTest,SqlDialectTest,ReportDefinitionValidatorTest}.php`
    - `tests/Feature/Reporting/{ReportRunnerTableModeTest,TenantIsolationMatrixTest}.php` + `ReportingFixtures`
    - The tenant matrix runs every field of every dataset, as a column and as a match-everything OR filter, for two companies and a no-company user. A deliberate mutation (removing the order scope) made it fail, then it was restored.
  - Differences from the phase file:
    - The registry is a container singleton through `#[Singleton]`, not an `AppServiceProvider` binding. `AppServiceProvider` has another session's uncommitted edits.
    - Columns are referenced by **key**, not label (sorts now, and post-processors, formatting and charts in later phases). Keys are explicit or derived from field + bucket (`order_created_at_month`).
    - Week buckets are keyed by the Monday that starts the ISO week (`2025-12-29`), which avoids ISO week-year edge cases.
    - Added `SqlDialect::numericPlaceholder()`. PDO binds floats as strings, and SQLite won't coerce a string compared with a computed expression such as a subquery or `days_to_deliver`. `concat()` was left out because nothing needs it yet.
    - "Is not" / "not in" filters keep blank values.
    - Relative dates use the app timezone (UTC). Per-user timezones aren't modelled yet.
    - Products: no vendor, cost or price fields. Line-level `unit_price` is what the customer paid, so it is exposed.
  - **Full suite:** three runs (two parallel, one serial) each had 1–2 failures in unrelated tests: SAM fetch job persistence, SAM PSC discovery and Google Places customer update. Each run failed a different test, and each passed alone, so they look flaky and not like regressions from this phase. Several SAM files have another session's uncommitted edits.
- 2026-10-23: Owner approved phase 1 ("commit and move forward"). Phase 2 started.

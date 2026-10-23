# Report Builder — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-23 | Owner approved ("commit the plan and then start on phase 1"); committed beed60c8 |
| 1 | Engine foundation: datasets, tenant scope, compiler, filters | Complete | 2026-10-23 | Owner approved ("commit and move forward"); committed 8f68d1f8. 128 new tests pass (SQLite). Full suite: 1328 passed / 5 skipped / 1 failed; the failing test is unrelated and different each run (see log). MySQL execution is checked in phase 8.1 |
| 2 | Aggregation, formulas, post-processing, pivots | Complete | 2026-10-23 | Owner approved ("commit and move forward"). 256 reporting tests pass on SQLite. Engine tests also pass on **MySQL 8.0** (local, ONLY_FULL_GROUP_BY); the only skips are the 4 legacy month parity cases, because the *old* service can't run on MySQL. Full suite: 1472 passed / 5 failed, all unrelated (see log) |
| 3 | Saved reports, sharing, preview API, templates | Complete | 2026-10-23 | Owner approved ("Commit"). 44 new tests; 317 report-related tests pass. Full suite (parallel): 1517 passed / 5 skipped / 3 failed, all unrelated and passing alone (DIBBS batch export ×2, ActivityLog: other sessions' untracked work). API under /account/reports (see log) |
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
- 2026-10-23: Phase 2 built (uncommitted):
  - **Engine:**
    - `Aggregate` enum (sum, avg, min, max, count, count_distinct, count_true), allowed per field type.
    - New `FieldType::Id`, with `order.id`, `line.id`, `payment.id`, `shipment.id` and `quote.id` fields (count only, not filterable).
    - Columns take `aggregate`. Modes are `table`, `summary` and `pivot`.
  - **Formulas:** `app/Reporting/Formula/` holds an in-house lexer, a recursive-descent parser (500 chars / 100 nodes), one node class per file, and an evaluator.
    - Formulas can use aggregates `SUM(field)`, column keys, other formulas, and raw fields (table mode only).
    - Functions: ROUND, ABS, MIN, MAX, IF, COALESCE, NULLIF, plus comparisons.
    - Blanks carry through and dividing by zero gives a blank. Errors give the character position. A 3000-input fuzz test passes.
  - **Processing** (`app/Reporting/Processing/`):
    - `FormulaCalculator` runs formulas in dependency order; the validator rejects loops.
    - `RowSorter` does PHP sorts that match SQL ordering, used when sorting by a calculated column.
    - `PostProcessor` handles % of total, running total, change, % change and rank.
    - `ConditionalFormatter` turns rules into per-cell semantic styles; top/bottom N is computed across every row of the report.
    - `PivotBuilder`, described below.
  - **Query:**
    - `CompiledReport` can re-aggregate at any subset of dimensions, for totals, subtotals and pivots.
    - Formula inputs are hidden selects (`h…`).
    - Groups are formed by select alias, so MySQL ONLY_FULL_GROUP_BY can't reject the pivot "Other" CASE with bindings.
    - `RowFetcher` handles casting. `SqlDialect::castText()` was added.
  - **Totals:** grand totals come from their own SQL query over *all* matching data, ignoring the row limit, so averages and unique counts are exact and % of total uses the true total. Table-mode totals sum the number columns, and formulas there have no total. Subtotals group by the first grouping column.
  - **Pivot:**
    - Any number of row fields (up to 3) and **one** column field.
    - Every level comes from its own grouped query: cells, row totals, column totals and the grand total.
    - Past 50 column values, the top 49 are kept by the first value and the rest roll into an exact SQL "Other" column. Blanks get their own column.
    - Enum and yes/no values are labelled.
  - **Templates:** `Templates/LegacyPresets` holds the five old groupings as definitions with the legacy column keys. Location filters now keep only the lines at that location; the old report kept whole orders with any line there. This is documented in the class.
  - **Other choices:**
    - Bucketed and aggregated columns get default labels such as "Order date (Month)" and "Line total (Total)".
    - Result columns carry a `role`: dimension, measure, value, calculated, post, pivot_cell or pivot_total.
    - `ReportResult` gains `totals`, `subtotals` and `styles`. `styles` is always a JSON object.
  - **Tests:**
    - Unit: `tests/Unit/Reporting/Formula/FormulaTest`, `Processing/ProcessingTest`, and validator cases for every new section.
    - Feature: `SummaryModeTest`, `PivotModeTest` (including the exact Other roll-up past 50 values) and `LegacyParityTest`.
    - The tenant matrix was extended: group by every field, every aggregate and a pivot per dataset, for all 3 users.
  - **Legacy parity:** all five presets match `UserDashboardReportService` on SQLite. Filters covered: dates, status, payment status. On MySQL the old service fails with `FUNCTION strftime does not exist`, which confirms the production month-report bug.
  - **MySQL check:** a scratch database `report_engine_test` on the local MySQL (port 13316) ran `tests/Feature/Reporting` and the dashboard tests.
    - Result: everything passes except the 4 legacy month skips. Ran with `DB_CONNECTION=mysql DB_PORT=13316 DB_DATABASE=report_engine_test vendor/bin/pest …`.
    - A fresh migrate on MySQL fails in the app's own migrations: `2026_01_31_214523` (`mil_spec_parts`) adds a foreign key to `manufacturers` before `2026_01_31_214527` creates that table. To get past it, global `foreign_key_checks` was set to 0 for the run and restored to 1 afterwards.
    - That migration order would also break a brand-new MySQL install. It isn't part of this module, and the owner was told.
  - **Full suite** (parallel): 1472 passed / 5 skipped / 5 failed. The failures are flaky SAM job and Google Places tests, and `ActivityLogResourceTest`, which belongs to another session's untracked, in-progress audit-logging files and gives a different result each run. Pint was run on the reporting files only.
- 2026-10-23: Owner approved phase 2 ("commit and move forward"). Phase 3 started.
- 2026-10-23: Phase 3 built (uncommitted):
  - **Schema:** `reports` table (migration `2026_10_23_000000`), the `Report` model (soft deletes, a `visibleTo` scope) and `ReportFactory`.
    - Definitions are stored normalized: keys filled in and empty sections dropped. Running a report always uses the *viewer's* tenant scope, so sharing a report shares its layout, never its data.
  - **`ReportPolicy`** (picked up by auto-discovery; `AppServiceProvider` has someone else's edits):
    - Owners can do everything.
    - Colleagues can view and duplicate reports shared with the company, and update them only when `company_can_edit` is set.
    - Only the owner can share or delete.
    - Admins can view only.
    - Users without a company can't share.
    - If an owner leaves, the company keeps the report (read-only).
  - **`ReportService`:** create (at most 200 per user), update (changing sharing needs the owner, and re-stamps the owner's current company), duplicate (private copy), run (updates `last_run_at`), and the library summary and detail.
    - The detail includes `definition_errors` for reports saved against fields that changed.
    - Previews are cached per user for 60 s.
    - Definition errors come back as Laravel 422s keyed `definition.<path>`.
  - **`Catalog`:** datasets and fields with their types, operators, aggregates and options, plus the shared vocabulary (modes, aggregates, buckets, relative dates, formats, post-processors, formula functions, format rules, limits) and the user's own company locations, including archived ones.
  - **`TemplateCatalog`:** the 5 legacy groupings (last 30 days) plus 6 starters: spend by month × location (pivot), top 25 products (with share), open orders, payments by status, delivery times by carrier, quote conversion by month (with a conversion formula). `LegacyPresets::shape()` is now public.
  - **JSON API:** `routes/reports.php`, loaded from `web.php` with one `require` line. Middleware `auth:web` + `verified.enabled`; route names `dashboard.reports.{catalog,templates,preview,saved.*}`. Throttles: preview 60/min, save and duplicate 30/min.
    - Reports the viewer can't see answer **404** on every route, so ids can't be probed.
  - **Change from the phase file:** phase 3 ships only the JSON API. The Inertia library and builder page routes move to phase 4 with their pages, so the current `/…/reports` page keeps working meanwhile.
  - **Change from the plan (other session's work):** `customer-portal-ui-rebrand` phase 7 (uncommitted, but marked Complete in its STATE) moves every `/dashboard/*` URL to `/account/*` and keeps the route names. The report API therefore lives under **`/account/reports/...`** with `dashboard.reports.*` names, and tests use `route()` names only.
    - **Phase 4 must build on that module's new UI primitives and layout** (its `phase-1-foundation-primitives.md`). Its uncommitted Vue changes to the dashboard components are still in the working tree.
  - **Filament:** a read-only `ReportResource` ("Customer Reports" in the Admin group), with search and dataset/visibility filters.
  - **Tests:** `tests/Feature/Reports/{ReportPolicyTest,SavedReportApiTest,ReportPreviewApiTest}` and `tests/Feature/Filament/ReportResourceTest` (44 tests).
    - They cover the policy matrix and the `visibleTo` scope matching the policy, guests and admins being refused, CRUD, 404 for hidden reports on all 5 routes, sharing and editing rules, duplicates, viewer-scoped runs, stale definitions, the save cap, preview caching (per user, 60 s), the 60/min throttle, the catalog (own locations only), and every template validating and running.
  - **Fix found by the tests:** with only `auth`, an admin-guard session passed and the controller got a null web user (500). Routes now say `auth:web`.
  - Full suite (parallel): 1517 passed / 5 skipped / 3 failed. `DibbsBatchQuoteExportTest` ×2 passes alone (26/26) and is untracked DIBBS work. `ActivityLogResourceTest` is the in-progress audit-logging work.
- 2026-10-23: Owner approved phase 3 ("Commit"). The rebrand commit 7301955d had already included the `require reports.php` line in `web.php` without `routes/reports.php`, which left HEAD broken until this commit. Phase 4 not started; waiting for the go-ahead.

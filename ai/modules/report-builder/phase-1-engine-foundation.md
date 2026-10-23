---
plan: report-builder
phase: 1
title: Engine foundation — datasets, tenant scope, query compiler, filters
status: not_started
last_updated: 2026-10-23
depends_on: []
checkpoint: none
---

# Phase 1 — Engine Foundation

## Goal

A backend-only engine that takes a validated **table-mode** report definition
(columns + filters + sort + limit) for any of the six datasets and returns
correct, tenant-scoped rows on both MySQL and SQLite. There is no UI and no
persistence in this phase.

## Success criteria

- All six datasets are registered with their field catalogs. No hidden column
  can be reached.
- Every query is tenant-scoped by `TenantScope`, and the definition can't
  turn it off.
- Filters support nested AND/OR groups (max depth 3) with type-appropriate
  operators, including relative dates.
- Date buckets work on SQLite and MySQL.
- Invalid definitions are rejected with errors tied to the JSON path.

## Sub-phases

### 1.1 — Value objects and registry — `checkpoint: registry`
- `app/Reporting/Definition/ReportDefinition.php` (readonly DTO, `fromArray()`/`toArray()`), plus `ColumnSpec`, `FilterGroup`, `FilterRule`, `SortSpec`.
- `app/Reporting/Fields/Field.php`: key, label, group, `FieldType` enum, SQL expression, required joins, allowed aggregates, allowed operators, enum options source, `format` hint.
- `app/Reporting/Datasets/Dataset.php` (abstract): `key()`, `label()`, `baseQuery()`, `fields()`, `joins()`, `tenantColumns()`.
- `app/Reporting/DatasetRegistry.php`, bound as a singleton in `AppServiceProvider`.
- **Done when:** the registry lists the datasets and resolves fields by key. Unit tests cover lookup and unknown keys.

### 1.2 — Tenant scope — `checkpoint: tenant-scope`
- `app/Reporting/TenantScope.php`: applies `company_id = ? OR portal_user_id = ?` (or only portal user when there is no company) to the dataset's root entity. For Payments, Shipments, Order Lines and Quote Lines the scope sits on the parent order/quote join, which is always joined.
- Pull the rule out of `UserDashboardDataService::baseOrdersQuery()` into a shared helper that both use, so the dashboard and reports can't drift apart.
- Always `withoutGlobalScopes()` + explicit `whereNull(deleted_at)`, the same as today.
- **Done when:** existing dashboard tests still pass, and the leak tests in 1.6 pass.

### 1.3 — Datasets — `checkpoint: datasets`
- `OrdersDataset`, `OrderLinesDataset`, `PaymentsDataset`, `ShipmentsDataset`, `QuotesDataset`, `QuoteLinesDataset` in `app/Reporting/Datasets/`.
- Fields follow the overview's dataset table. Location fields expose `parent_name` and `sublocation_name` using the same COALESCE/CASE logic as `UserDashboardReportService`.
- Enum fields take their options from `OrderStatus`, `PaymentStatus`, `FulfillmentStatus`, `PaymentMethod`, `FulfillmentMethod`, `PickupStatus` and quote status.
- Deny-list assertion: one test checks that no field expression mentions `cost`, `internal_notes`, `meta`, `gateway_`, `failure_`, `created_by`, `updated_by`, `sales_rep_id`, `walk_in_`, `pickup_code` or `products.vendor_id`.
- **Done when:** every field selects without SQL errors on SQLite.

### 1.4 — SQL dialect — `checkpoint: dialect`
- `app/Reporting/Sql/SqlDialect.php` interface + `MySqlDialect`, `SqliteDialect`; chosen from `DB::connection()->getDriverName()`.
- Methods: `dateBucket(expr, bucket)` (day, week (ISO), month, quarter, year), `concat()`, `dateDiffDays()`.
- **Done when:** unit tests check the generated SQL for both dialects, and SQLite results are checked against seeded data.

### 1.5 — Validator and compiler — `checkpoint: compiler`
- `ReportDefinitionValidator`: dataset exists; fields exist; operator allowed for the type; values match the type (dates parse, enums are valid options, `in` ≤ 200 values); depth ≤ 3; columns ≤ 40; limit ≤ cap. Returns `[path => message]`.
- `QueryCompiler`: adds the joins that the chosen fields and filters need, de-duplicated; select expressions aliased `c0..cN`; where-tree with bound values; operators `eq, neq, in, not_in, gt, gte, lt, lte, between, contains, starts_with, is_null, not_null, relative, is_true, is_false`; relative date presets `today, yesterday, last_7_days, last_30_days, last_90_days, this_month, last_month, this_quarter, last_quarter, this_year, last_year, year_to_date`.
- `ReportRunner::run(User, ReportDefinition): ReportResult` (table mode only in this phase). `ReportResult` holds the column metadata (label, type, format), rows, `truncated` and `row_count`.
- **Done when:** the compiler unit tests and runner feature tests pass.

### 1.6 — Security test matrix — `checkpoint: security-tests`
- Two companies plus one no-company user, with seeded orders, quotes, payments and shipments on each.
- For every dataset × every field (as a column and as a filter): the user from company A only ever sees company A's rows.
- Injection attempts in field keys, operators, values and sort columns are rejected or bound.
- **Done when:** the matrix passes.

## Files to create

- `app/Reporting/**` (as above)
- `tests/Unit/Reporting/{DatasetRegistryTest,SqlDialectTest,QueryCompilerTest,ReportDefinitionValidatorTest}.php`
- `tests/Feature/Reporting/{TenantIsolationMatrixTest,ReportRunnerTableModeTest}.php`

## Files to modify

- `app/Services/Dashboard/UserDashboardDataService.php` (use the shared tenant rule)
- `app/Providers/AppServiceProvider.php` (registry binding)

## Risks

- Joining *up* from a child to a parent that isn't scoped could leak data. The rule is that scope is always applied on the order/quote, whichever dataset is the root.
- Week bucketing differs between engines. Use ISO weeks and test the boundaries (year rollover).

## Definition of done

Phase tests pass, `php artisan test` passes in full, and `vendor/bin/pint` has
been run on the edited files. Set STATE to `Awaiting Validation`.

**STOP. Await phase approval.**

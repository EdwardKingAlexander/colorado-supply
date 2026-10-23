# Report Builder — Overview

## Problem

`/dashboard/reports` (built in `normal-user-dashboard-panel-complete`, phase 5)
is a fixed purchasing report. Users pick a date range, location, sublocation,
order status and payment status, then one of five hard-coded groupings (month,
location, sublocation, product, order). Columns are hard-coded per grouping in
`UserDashboardReportService`, there is a 100-row preview, and the only export
is CSV. Reports can't be saved, shared, charted or scheduled.
`monthRows()` also uses SQLite's `strftime()`, so it breaks on MySQL.

The owner wants a very customizable report builder for non-admin (web guard)
users. Users choose the data source, columns, filters, grouping, totals,
formulas, pivots, formatting and charts. They can save and share reports, pin
them to the dashboard and have them emailed on a schedule.

## Owner decisions (2026-10-23)

| Decision | Choice |
|---|---|
| Data domains | Orders and order lines; payments and shipments; quotes and quote requests |
| Features | Saved reports and sharing; charts and dashboard pins; scheduled email delivery; pivots, calculated fields and conditional formatting |
| Access inside a company | Company-wide, no roles. Matches today's dashboard: any user in the company can build, save, share, schedule and see reports on all company data |
| Architecture | Approach A: server-side field catalog, JSON report definitions, query compiler, Vue builder UI |

Defaults chosen during design (accepted with the design, but easy to revisit):

- Schedule recipients can only be **users in the same company**. No outside
  email addresses, so the feature can't be used to send company data outside
  the company.
- PDF export has the table, totals and KPI tiles but **no chart images** in v1.
  Charts are drawn in the browser by Chart.js and dompdf can't run that code.
- Product `vendor` (our supplier) is **not exposed**. Supplier relationships
  are commercially sensitive. Product category is exposed.
- Schedules default to the `America/Denver` timezone. Users have no timezone
  column today. Each schedule stores its own.

## Constraints

- **Tenant isolation is the top requirement.** A user only ever sees rows
  where `company_id = user.company_id` or `portal_user_id = user.id` (or only
  the second when the user has no company). This is the rule already used by
  `UserDashboardDataService::baseOrdersQuery()`. It is applied by the engine,
  never by the report definition.
- **No raw SQL from the client.** Every field, operator, aggregate and
  function comes from a server-side whitelist. Every literal is bound as a
  parameter.
- **Hidden columns:** product `cost`, `orders.internal_notes`, `meta` columns,
  payment gateway ids and failure messages, `created_by`/`updated_by`,
  `sales_rep_id`, walk-in contact columns and `pickup_code`.
- The database is MySQL in production and SQLite locally and in tests. Every
  SQL expression (date grouping, string concatenation) is written for each
  driver, and both are tested.
- The stack is Laravel 13, Inertia v2/Vue 3, Tailwind 4 and Pest. Libraries
  already installed: `maatwebsite/excel` (XLSX), `barryvdh/laravel-dompdf`
  (PDF). The one new frontend dependency is `chart.js` + `vue-chartjs`,
  lazy-loaded.
- Admins reaching `/dashboard/reports` keep being redirected to Filament, as
  `DashboardReportController` does today.
- The current five presets and `GET /dashboard/reports/export` keep working
  until phase 8 retires them in favor of templates.

## Architecture

```
Vue builder (Pages/Reports/*)                       Scheduler (every 15 min)
   │  ReportDefinition JSON                              │
   ▼                                                     ▼
ReportController / ReportPreviewController      DispatchDueReportSchedules
   │                                                     │ RunReportScheduleJob (as owner)
   ▼                                                     ▼
ReportDefinitionValidator ──► DatasetRegistry ◄── Dataset classes (Orders, OrderLines,
   │                              (field catalog)        Payments, Shipments, Quotes, QuoteLines)
   ▼
ReportRunner
   ├─ TenantScope            (company / portal-user rule, always applied)
   ├─ QueryCompiler          (select / join / where / group / order from whitelisted fields)
   │    └─ SqlDialect        (MySQL vs SQLite date buckets, concat)
   ├─ FormulaEngine          (in-house parser → AST → evaluate over aggregated rows)
   ├─ PostProcessors         (% of total, running total, change vs prior period)
   ├─ PivotTransformer       (grouped rows → cross-tab)
   └─ ReportResult           (columns meta, rows, totals, truncated flag)
        │
        ├─ Inertia/JSON preview (500 rows)
        ├─ Exporters: CSV (streamed), XLSX (maatwebsite, queued when large), PDF (dompdf)
        └─ Dashboard pin widgets
```

### Datasets (field catalog)

Each dataset class declares its base table, its allowed joins (whitelisted and
added only when a chosen field needs them), its tenant scope and its fields.
Each field has a key, a label, a group (entity), a type (string, enum,
number, money, date, datetime, boolean, location), a SQL expression and the
aggregates and operators allowed for it.

| Dataset | Root | Joinable entities |
|---|---|---|
| Orders | `orders` | pickup location |
| Order Lines | `order_items` | order, product, product category, line location (leaf and parent) |
| Payments | `payments` | order |
| Shipments | `shipments` | order (+ derived `days_to_deliver`) |
| Quotes | `quotes` | converted order (`orders.quote_id`) for quote-to-order conversion |
| Quote Lines | `quote_items` | quote, product, category, location |

Soft-deleted orders, quotes and locations are excluded the same way they are
today.

### Report definition (schema v1, stored as JSON)

```json
{
  "version": 1,
  "dataset": "order_lines",
  "mode": "summary",
  "columns": [
    {"field": "order.created_at", "bucket": "month", "label": "Month"},
    {"field": "location.parent_name"},
    {"field": "line.line_total", "aggregate": "sum", "label": "Spend", "format": "currency"}
  ],
  "calculated": [
    {"key": "avg_unit", "label": "Avg unit price", "formula": "SUM(line.line_total) / SUM(line.quantity)", "format": "currency"}
  ],
  "post": [{"column": "Spend", "op": "percent_of_total"}],
  "filters": {"logic": "and", "rules": [
    {"field": "order.created_at", "op": "relative", "value": "last_90_days"},
    {"logic": "or", "rules": [
      {"field": "order.status", "op": "in", "value": ["confirmed", "fulfilled"]},
      {"field": "order.payment_status", "op": "eq", "value": "paid"}
    ]}
  ]},
  "pivot": null,
  "sort": [{"column": "Spend", "dir": "desc"}],
  "limit": 1000,
  "totals": {"grand": true, "subtotals": false},
  "formatting": [{"column": "Spend", "when": {"op": "gt", "value": 10000}, "style": "positive"}],
  "chart": {"type": "bar", "x": "Month", "series": ["Spend"], "stacked": false}
}
```

Modes:
- **table** has no aggregates and returns raw rows.
- **summary** groups by every non-aggregated column.
- **pivot** takes `pivot.rows[]`, `pivot.columns[]` and `pivot.values[]`.

### Hard limits

| Limit | Value |
|---|---|
| Preview rows | 500 |
| Grouped rows before truncation | 10,000 |
| Pivot column values | 50 (the rest roll into "Other") |
| Filter nesting depth | 3 |
| Columns per report | 40 |
| Formula length / AST nodes | 500 chars / 100 nodes |
| Query time limit | 15 s (MySQL `MAX_EXECUTION_TIME` hint; SQLite has no equivalent, so this isn't tested there) |
| PDF rows | 2,000 |
| Synchronous XLSX/CSV rows | 50,000 (bigger exports are queued and emailed) |

## Data flow

1. The builder edits a definition in local state. A debounced
   `POST /dashboard/reports/preview` sends it.
2. `ReportDefinitionValidator` checks it against the dataset catalog. Unknown
   fields, operators, aggregates or functions give a 422 with errors tied to
   the JSON path.
3. `ReportRunner` applies `TenantScope`, compiles and runs the query, applies
   formulas, post-processors and the pivot, then returns a `ReportResult`.
4. Save stores the definition plus `schema_version`. Opening a saved report
   re-validates it, because a field may have been retired.
5. Exports and schedules re-run the stored definition as the owner, at the
   moment they run.

## Risks

| Risk | Mitigation |
|---|---|
| Cross-tenant leak through a join or a filter on a joined entity | Scope is applied at the dataset root. Joins only go *down* from the scoped root. A tenant-leak test matrix covers every dataset × every field in phase 1 |
| SQL injection through field keys or values | Field keys map to fixed expressions. Values are always bound. The formula engine never emits SQL |
| Slow queries on big companies | Joins are added only when needed, row and time caps apply, indexes are added in phase 8, previews are cached briefly |
| MySQL and SQLite behaving differently | A `SqlDialect` abstraction. Phase 8 runs the engine tests against MySQL as well (local MySQL from `nsn-sourcing-workflow-complete`) |
| Definitions break when a field is renamed | `schema_version` plus field aliases in the registry. Reports with invalid fields open with a warning instead of a 500 |
| Scheduled email sends data to someone who has left the company | Recipients are re-checked against the owner's company on every run. A schedule pauses if its owner leaves |
| Scope creep ("extremely customizable") | v1 stops at the six datasets and the features listed. New datasets later are one class plus tests |

## Phases

| # | Phase | Depends on |
|---|---|---|
| 1 | [Engine foundation: datasets, tenant scope, compiler, filters](phase-1-engine-foundation.md) | — |
| 2 | [Aggregation, formulas, post-processing, pivots](phase-2-aggregation-formulas-pivots.md) | 1 |
| 3 | [Saved reports, sharing, preview API, templates](phase-3-persistence-sharing-api.md) | 2 |
| 4 | [Builder UI](phase-4-builder-ui.md) | 3 |
| 5 | [Charts and dashboard pins](phase-5-charts-dashboard-pins.md) | 4 |
| 6 | [Exports: CSV, XLSX, PDF](phase-6-exports.md) | 3 (UI hooks need 4) |
| 7 | [Scheduled email delivery](phase-7-scheduled-delivery.md) | 6 |
| 8 | [Hardening, performance, legacy retirement](phase-8-hardening.md) | 1–7 |

## Out of scope (v1)

Admin-panel report building, intra-company roles, location-restricted
visibility, external email recipients, chart images in PDF, joining across
datasets beyond the listed entities, SQL/custom datasets, and reporting on SAM,
repair services or activity history. Each of these can be its own later module.

## Research notes

- Chart library: Chart.js through `vue-chartjs` (supports Chart.js v4), lazy-loaded.
  ECharts (`vue-echarts`) can do more but has a larger bundle and a steeper
  learning curve, and bar, line, area, pie and KPI cover what we need.
  ([LogRocket](https://blog.logrocket.com/8-best-chart-libraries-vue),
  [FusionCharts 2026](https://www.fusioncharts.com/blog/?p=24945),
  [vue-chartjs](https://best-of-web.builder.io/library/apertureless/vue-chartjs)).
  Check the current versions on npm when phase 5 starts.
- Formula parsing: the PHP options are small community packages.
  `mossadal/math-parser`'s last release was 2018, and
  `arokettu/arithmetic-parser` is maintained but niche
  ([packagist](https://packagist.org/packages/mossadal/math-parser),
  [arithmetic-parser](https://root.packagist.org/packages/arokettu/arithmetic-parser)).
  Decision: an in-house recursive-descent parser over a tiny grammar, so the
  whitelist is fully under our control.
- XLSX: `maatwebsite/excel` 3.1 `FromQuery` + `->queue()` splits the query into
  chained jobs ([docs](https://docs.laravel-excel.com/3.1/exports/queued.html)).
  It's already installed and runs under Laravel 13 in this repo.

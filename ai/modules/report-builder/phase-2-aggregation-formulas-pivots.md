---
plan: report-builder
phase: 2
title: Aggregation, calculated fields, post-processing, pivots
status: in_progress
last_updated: 2026-10-23
depends_on: [phase-1]
checkpoint: none
---

# Phase 2 — Aggregation, Formulas, Pivots

## Goal

Add **summary** and **pivot** modes to the engine: grouped aggregates,
user-written formulas, built-in post-processors, grand totals and subtotals,
and conditional formatting metadata.

## Success criteria

- Summary mode groups by every non-aggregated column (with date buckets) and
  computes sum, avg, min, max, count and count_distinct.
- Formulas are parsed safely and evaluated over the aggregated rows. A bad
  formula gives a clear validation error, never a 500.
- Pivot mode produces a cross-tab with row and column totals, capped at 50
  pivot columns.
- Totals, sorting (including on calculated columns) and conditional
  formatting rules work.
- The five legacy presets can each be written as a definition and give
  results identical to `UserDashboardReportService` (a parity test).

## Sub-phases

### 2.1 — Summary aggregation — `checkpoint: summary`
- Extend `QueryCompiler` with GROUP BY/aggregate selects. Aggregates are
  allowed per field type (money and number: all; others: count and
  count_distinct only).
- Sorting on aggregated aliases is done in SQL. The group cap is 10,000;
  `truncated=true` when it's hit.
- **Done when:** grouped totals match hand-computed fixtures.

### 2.2 — Formula engine — `checkpoint: formula`
- `app/Reporting/Formula/{Lexer,Parser,Node/*,Evaluator,FormulaException}.php`.
- Grammar: numbers, `+ - * /`, unary minus, parentheses, aggregate references
  `SUM(field.key)` / `AVG(...)` / `COUNT(...)` (which must also exist, or are
  auto-added as hidden columns), references to other calculated keys (no
  cycles), and the functions `ROUND(x, n)`, `ABS`, `MIN`, `MAX`, `IF(cond, a, b)`
  with `< <= > >= = !=`, `COALESCE`, `NULLIF`.
- Division by zero gives null. There's a limit of 500 chars / 100 nodes. It
  is evaluated in PHP per aggregated row and never compiled into SQL.
- Validation reports errors with a character position.
- **Done when:** unit tests cover precedence, functions, errors, cycles and
  limits, and fuzz tests (random tokens) never throw anything except
  `FormulaException`.

### 2.3 — Post-processors — `checkpoint: post`
- `percent_of_total`, `running_total`, `change_vs_previous` (absolute and %,
  needs a date-bucket column sorted ascending) and `rank`.
- These run after formulas and before the final sort.
- **Done when:** unit tests pass on fixture rows.

### 2.4 — Pivot transformer — `checkpoint: pivot`
- `PivotTransformer`: input is the summary result grouped by
  rows ∪ columns; output is column metadata with a nested header (value ×
  column value), rows, row totals and column totals.
- More than 50 distinct column values: keep the top 49 by first value's total
  and roll the rest into "Other".
- **Done when:** pivot tests for 1×1, 2 row dims × 1 col dim and multiple
  values pass.

### 2.5 — Totals and formatting metadata — `checkpoint: totals-format`
- Grand totals (for aggregated and calculated columns, with formulas
  recomputed on the totals rather than summed) and optional subtotals on the
  first grouping column.
- Conditional formatting: rules (`gt, lt, between, eq, top_n, bottom_n,
  negative`) → semantic styles (`positive, negative, warning, muted, bold`)
  that are evaluated on the server and returned per cell, so the UI and PDF
  render them the same way.
- **Done when:** totals and formatting tests pass.

### 2.6 — Legacy parity — `checkpoint: parity`
- `app/Reporting/Templates/LegacyPresets.php`: the month, location,
  sublocation, product and order presets as definitions.
- A parity test runs each preset through the old service and the new engine
  against the same seed, and the results must match. This retires the
  `strftime` bug: the new engine's month bucket goes through `SqlDialect`.
- **Done when:** parity holds.

## Files to create

- `app/Reporting/Formula/**`, `app/Reporting/Processing/{PostProcessor,PercentOfTotal,RunningTotal,ChangeVsPrevious,Rank,PivotTransformer,TotalsCalculator,ConditionalFormatter}.php`, `app/Reporting/Templates/LegacyPresets.php`
- `tests/Unit/Reporting/Formula/*`, `tests/Unit/Reporting/Processing/*`, `tests/Feature/Reporting/{SummaryModeTest,PivotModeTest,LegacyParityTest}.php`

## Files to modify

- `QueryCompiler`, `ReportRunner`, `ReportDefinitionValidator`, `ReportDefinition` (calculated, post, pivot, totals, formatting)

## Risks

- Floating-point drift on money. Aggregate with SQL DECIMAL and round at
  output, and use `bcmath`-free rounding rules that are documented in tests.
- Pivots on high-cardinality fields (order number). The validator warns and
  the column cap protects the result.

## Definition of done

Phase tests pass, the full suite passes, and pint has been run on the edited
files. Set STATE to `Awaiting Validation`.

**STOP. Await phase approval.**

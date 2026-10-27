# Phase 3 — Dashboard & Dashboard Widgets

## Goal

Rebuild the dashboard body on `AccountLayout` and `st-*` tokens so it reads
as a purchasing ledger in the store's catalog voice, in light and dark.

## Design
- Title row: "Purchasing overview" in `.st-display`, period + location scope
  as plain sentence-case text (no all-caps eyebrows).
- Filters: a compact bar (range, start, end, location, sublocation, Apply).
- KPIs: one **ledger strip** with dividers. Total spend is the large figure;
  orders, average order, open/unpaid and top location sit beside it. All
  figures use `.st-num` tabular numerals. This replaces five identical cards
  and is the page's one bold element.
- Charts: single-hue brand-blue bars (`st-fill`) on an `st-sunk` track,
  value labels right-aligned with tabular numerals, legible in both themes
  (follow the `dataviz` skill).
- Tables: `st-line` dividers, `st-muted` headers in sentence case, and status
  shown as text plus a dot, not color alone.

## Sub-phases
- 3a. `Dashboard.vue` → `AccountLayout`, title/actions.
- 3b. `DashboardKpiGrid.vue` → ledger strip.
- 3c. `DateRangeControl.vue`, `SpendBarChart.vue`.
- 3d. `RecentOrdersTable.vue`, `TopItemsTable.vue`, `AccountSummaryPanel.vue`,
  `DashboardEmptyState.vue`.
- 3e. `Dashboard/Reports.vue`, `ReportBuilderForm.vue`, `ReportPreviewTable.vue`.
- 3f. `resources/css/app.css` `.responsive-data-table` mobile card colors →
  `st-*` tokens inside `.store-shell`.

Each sub-phase is done when its component carries no `gray-*`/`indigo-*`/
`blue-*` hard-codes and renders correctly in both themes.

## Out of Scope
- Data, KPIs, queries, props.

## Tests
- Existing `DashboardController` / reports feature tests green; `npm run build`.

## Success Criteria
- The dashboard and reports are on-brand in light and dark, with no
  Breeze styling left.

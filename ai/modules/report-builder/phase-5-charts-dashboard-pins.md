---
plan: report-builder
phase: 5
title: Charts and dashboard pins
status: not_started
last_updated: 2026-10-23
depends_on: [phase-4]
checkpoint: none
---

# Phase 5 — Charts and Dashboard Pins

## Goal

Any summary or pivot report can be shown as a chart and pinned to the
dashboard home as a widget, which shows its latest data using the viewer's
tenant scope.

## Success criteria

- Chart types: bar (vertical, horizontal, stacked), line, area, pie and donut,
  plus a KPI tile (single value with an optional comparison to the previous
  period).
- The chart config is part of the definition, validated on the server, and
  chosen in the builder's Chart tab.
- Chart.js is lazy-loaded: it's absent from the initial bundle of pages
  without charts.
- Users can pin up to 12 reports, reorder them and choose a widget size
  (1/3, 1/2 or full width). Pins are per user.
- The legacy `SpendBarChart.vue` keeps working, or is replaced by the same
  chart component.

## Sub-phases

### 5.1 — Dependency and research check — `checkpoint: deps`
- Check the current versions and license of `chart.js` and `vue-chartjs` on npm (Vue 3.5 and SSR compatibility: the app runs Inertia SSR, so charts need to be client-only through `defineAsyncComponent` + an `onMounted` guard).
- `npm install chart.js vue-chartjs`.
- **Done when:** the build passes and SSR rendering of the Builder page doesn't crash.

### 5.2 — Chart config in the engine — `checkpoint: chart-config`
- `ReportDefinition.chart`: `{type, x, series[], stacked, show_legend, show_values, kpi: {value, compare: previous_period|none}}`.
- Validation: `x` must be a dimension column, `series` must be measure or calculated columns, pie needs exactly one series and ≤ 20 slices (the rest go to "Other" through the same roll-up as the pivot), KPI needs exactly one measure.
- `ChartDataBuilder` (PHP): turns `ReportResult` into `{labels, datasets}`, so the browser and future server-side renderers share one shape.
- **Done when:** unit tests per chart type pass.

### 5.3 — Chart components — `checkpoint: components`
- `Components/Reports/ReportChart.vue` (async wrapper, picks the Chart.js type), `KpiTile.vue` and `ChartConfigPanel.vue`.
- Colors come from a fixed accessible palette in `resources/js/Reporting/palette.js` (checked for contrast). Tooltips use the column format. There's a `prefers-reduced-motion` check, and a hidden data table for screen readers.
- **Done when:** every type renders in the builder preview.

### 5.4 — Dashboard pins — `checkpoint: pins`
- Migration `create_report_dashboard_pins_table`: `user_id`, `report_id`, `position`, `size` enum, timestamps; unique (`user_id`, `report_id`).
- Endpoints: `POST /dashboard/reports/{report}/pin`, `DELETE .../pin`, `PATCH /dashboard/report-pins/order`.
- `DashboardController`: loads the pins (only reports the user can still view; pins pointing at reports they lost access to are dropped quietly) and lazy-loads widget data through Inertia v2 deferred props (`Inertia::defer`) so the dashboard doesn't wait on the reports.
- `Components/Dashboard/PinnedReportWidget.vue` with loading, error and empty states and "Open in builder".
- **Done when:** feature tests (pin auth, ordering, lost-access cleanup) and a Playwright pin flow pass.

## Files to create

- `app/Reporting/Charts/ChartDataBuilder.php`, the migration, a `ReportDashboardPin` model, `ReportPinController`, the Vue components above, `palette.js`, tests.

## Files to modify

- `package.json`, `ReportDefinition`/validator, `Builder.vue`, `DashboardController`, `Pages/Dashboard.vue`.

## Risks

- SSR and Chart.js `window` access. Use client-only async components.
- Many pins slowing the dashboard. Deferred props, the 60 s preview cache, and
  the 12-pin cap.

## Definition of done

Phase tests pass, `npm run build` passes, Playwright passes, the full suite
passes, and pint has been run on the edited files. Set STATE to
`Awaiting Validation`.

**STOP. Await phase approval.**

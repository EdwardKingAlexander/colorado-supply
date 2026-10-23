---
plan: report-builder
phase: 4
title: Builder UI — library, field picker, filters, preview, pivot, formatting
status: not_started
last_updated: 2026-10-23
depends_on: [phase-3]
checkpoint: none
---

# Phase 4 — Builder UI

## Goal

A Vue/Inertia builder where a non-technical buyer can make any report the
engine supports, see a live preview, and save or share it. It works on
desktop and phones and is keyboard accessible.

## Success criteria

- The library page lists My reports, Shared with company and Templates, with
  search, sort, duplicate, delete and share actions.
- The builder:
  - picks a dataset, then searches and adds fields grouped by entity
  - reorders, renames and removes columns
  - chooses aggregate, bucket and format per column
  - builds nested AND/OR filters
  - sets sort, limit and totals
  - adds formulas and post-processors with inline validation
  - switches between Table, Summary and Pivot
  - edits conditional formatting rules
- The live preview is debounced at 400 ms, with in-flight cancellation and
  loading, empty, error and truncated states.
- Unsaved-changes guard. View-only mode for reports the user can't edit.
- Matches existing portal styling (the `AuthenticatedLayout` patterns, the
  `mobile-page-gutter` class and the `min-h-12` touch targets used in
  `Dashboard/Reports.vue`).

## Sub-phases

### 4.1 — State and API layer — `checkpoint: store`
- `resources/js/Stores/useReportBuilder.js`: a composable store (follow the existing `Stores/` pattern) holding the definition, the dirty flag, undo/redo (last 50 states) and preview state; `axios` + `AbortController` for preview.
- `resources/js/Reporting/catalog.js`: helpers for field lookup and the operators allowed per type.
- **Done when:** composable unit behavior is verified via a Pest browser/Playwright smoke test in 4.7.

### 4.2 — Library page — `checkpoint: library`
- `Pages/Reports/Index.vue` with tabs, a cards/table list, empty states, and "New report", which opens a dataset chooser modal (`@headlessui/vue` Dialog) or "Start from template".
- **Done when:** the page renders the seeded reports and every action works.

### 4.3 — Builder shell and field picker — `checkpoint: shell`
- `Pages/Reports/Builder.vue`: header (name inline-editable, Save, Save as, Share, Export menu placeholder until phase 6, mode switch), with three panels on ≥lg and tabs (Fields / Preview / Settings) on mobile.
- `Components/Reports/FieldPicker.vue`: search, entity groups, type icons (`@heroicons/vue`), add with click or Enter.
- `Components/Reports/ColumnList.vue`: reorder with up and down buttons plus native drag; per-column popover for label, aggregate, bucket and format.
- **Done when:** a report can be built by keyboard only.

### 4.4 — Filter builder — `checkpoint: filters`
- `Components/Reports/FilterGroup.vue` (recursive, depth ≤ 3), `FilterRule.vue` with a value input per type: text; number; money; a date or date range with a relative presets dropdown; an enum multiselect; a location tree picker (parent → sublocation, using the user's company locations); and a boolean toggle.
- **Done when:** nested groups are saved and come back intact when reopened.

### 4.5 — Formulas, post-processors, pivot config — `checkpoint: advanced`
- `FormulaEditor.vue`: a textarea with field and function autocomplete; server validation errors point at the character position; a "Test" button previews.
- `PostProcessorList.vue`, `PivotConfig.vue` (drag fields between Rows, Columns and Values), `TotalsToggle.vue`, `ConditionalFormatEditor.vue`.
- **Done when:** each option round-trips through save and changes the preview.

### 4.6 — Preview renderers — `checkpoint: preview`
- `ReportTable.vue`:
  - sticky header, number and currency formatting from column metadata
  - semantic formatting styles turned into Tailwind classes
  - grand total and subtotal rows
  - truncated banner ("Showing 500 of 8,214 — export for all rows")
- `PivotTable.vue` with a nested header and a sticky first column; horizontal scroll inside its container only.
- **Done when:** a 40-column report doesn't scroll the page horizontally at 375 px.

### 4.7 — Save, share, guards, navigation — `checkpoint: save-share`
- `ShareDialog.vue` (private/company, allow company edit, owner only).
- Unsaved-changes prompt on Inertia navigation and `beforeunload`.
- A "Reports" link in the dashboard and nav: update `Pages/Dashboard.vue` and the nav components; check the `site-navigation-restructure` module plan for conflicts first.
- Replace `Pages/Dashboard/Reports.vue` with a redirect to the library (the old components are removed in phase 8).
- **Done when:** Playwright smoke test: build from a template → change the filter → save → share → reopen as another company user (view-only) → duplicate.

## Files to create

- `resources/js/Pages/Reports/{Index,Builder}.vue`, `resources/js/Components/Reports/*`, `resources/js/Stores/useReportBuilder.js`, `resources/js/Reporting/catalog.js`, `tests/Browser/reports-builder.spec.*` (follow the existing Playwright layout)

## Files to modify

- `resources/js/Pages/Dashboard.vue`, the nav layout components, and `routes/web.php` if needed.

## Risks

- UI complexity on mobile. Settings live in a bottom-sheet style tab on
  mobile, and pivots are read-only on narrow screens with editing behind a
  "Configure" sheet.
- Preview request storms. Debounce, abort and throttle on the server; show a
  "Preview paused, too many requests" state on 429.

## Definition of done

Phase tests pass, `npm run build` passes, the Playwright smoke test passes,
the full suite passes, and pint has been run on the edited files. Set STATE to
`Awaiting Validation`.

**STOP. Await phase approval.**

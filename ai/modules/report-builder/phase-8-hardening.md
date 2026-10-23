---
plan: report-builder
phase: 8
title: Hardening, performance, legacy retirement
status: not_started
last_updated: 2026-10-23
depends_on: [phase-1, phase-2, phase-3, phase-4, phase-5, phase-6, phase-7]
checkpoint: none
---

# Phase 8 — Hardening

## Goal

The module is production-ready: fast on realistic data volumes, verified on
MySQL, accessible, with the legacy report code removed and deploy steps
written down.

## Success criteria

- The engine test suite passes on **MySQL** as well as SQLite.
- With a 500k order-line company dataset, a typical summary preview takes
  < 1.5 s and a pivot < 3 s on local MySQL. Slow-query findings are fixed with
  indexes.
- Accessibility check (axe through `@axe-core/playwright`) has no serious or
  critical violations on the Library, Builder and Dashboard pages.
- Legacy `UserDashboardReportService` report code, `DashboardReportController`,
  `DashboardReportExportController`, `DashboardReportRequest`,
  `Dashboard/Reports.vue`, `ReportBuilderForm.vue` and `ReportPreviewTable.vue`
  are removed. Old URLs redirect (`/dashboard/reports/export?group_by=…`
  keeps working through the phase 6 legacy mapping).
- `RUNBOOK.md` in this module lists the migrations, `npm run build`, the
  scheduler and queue worker requirements on Forge, and a rollback.

## Sub-phases

### 8.1 — MySQL verification — `checkpoint: mysql`
- Run `tests/Unit/Reporting`, `tests/Feature/Reporting` and `tests/Feature/Reports` against local MySQL (`start-local-mysql.ps1` from `nsn-sourcing-workflow-complete`) through a `phpunit.mysql.xml` or env override; document the command in RUNBOOK.
- **Done when:** both drivers are green.

### 8.2 — Performance — `checkpoint: perf`
- A seeder command `reports:seed-perf` (local only) builds a large company.
- Profile previews with `EXPLAIN`. Expected indexes: `orders(company_id, created_at)`, `orders(portal_user_id, created_at)`, `order_items(order_id, location_id)`, `quotes(company_id, created_at)`, `payments(order_id, status)`, `shipments(order_id, status)`. Add only what EXPLAIN shows is needed, in one migration.
- Confirm the query time limit works on MySQL (`/*+ MAX_EXECUTION_TIME(15000) */`) and the UI shows "Report took too long — add filters".
- **Done when:** the timing targets are met and recorded in STATE.

### 8.3 — Security review — `checkpoint: security`
- Re-run the tenant matrix with every dataset field in the final catalog. Fuzz `/preview` with random definitions (no 500s, no cross-tenant rows). Review the signed routes (export download, schedule pause) for expiry and scope.
- Run the `/security-review` skill on the module diff.
- **Done when:** no open high or medium findings.

### 8.4 — Accessibility and browser pass — `checkpoint: a11y`
- Playwright + axe on Library, Builder (each mode, chart tab, dialogs) and Dashboard with pins, at 375 px and 1280 px. Keyboard-only build-and-save flow.
- Remember: a backgrounded Chrome window throttles timers (see memory). Run headless.
- **Done when:** no serious or critical axe findings and the flows pass.

### 8.5 — Legacy retirement and docs — `checkpoint: retire`
- Remove the legacy files listed above (check `git log` and grep usages first). Keep `UserDashboardDataService` (the dashboard uses it).
- Update `CLAUDE.md` (Directory Structure: `app/Reporting/`), `ai/reference/remaining.md`, and write `RUNBOOK.md`.
- **Done when:** the full suite passes, `npm run build` passes, and nothing references the deleted files.

## Risks

- Removing legacy code that something else uses. Grep, and only delete in
  this phase after parity (phase 2.6) and the legacy export mapping (phase
  6.1) are proven.
- Performance targets on SQLite aren't meaningful. Only measure on MySQL.

## Definition of done

Everything above, then STATE → `Awaiting Validation`. When the owner marks
every phase `Complete`, rename the module to `report-builder-complete` and
update the references and `remaining.md` (CLAUDE.md step 5).

**STOP. Await phase approval.**

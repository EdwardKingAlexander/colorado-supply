# Normal User Dashboard Panel - State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-06-12 | Orchestrator approved overall plan |
| 1 | Location hierarchy and reporting foundation | Complete | 2026-06-12 | Implemented; targeted tests passing; orchestrator instructed continued execution |
| 2 | Dashboard backend data service and route wiring | Complete | 2026-06-12 | Implemented; backend dashboard tests passing |
| 3 | Dashboard UI and account panel | Complete | 2026-06-12 | Implemented with account, KPI, recent order, and top item panels |
| 4 | Charts and interactive time-frame filtering | Complete | 2026-06-12 | Implemented with server-backed filters and dependency-free bar charts |
| 5 | Report builder and CSV export | Complete | 2026-06-12 | Implemented report preview and CSV export |
| 6 | Testing, hardening, and performance pass | Complete | 2026-06-12 | Targeted tests, build, and formatting passing |

## Log

- 2026-06-12 - Plan drafted for a normal-user dashboard panel with account
  management, location/sublocation purchase analytics, selectable time frames,
  charts, report builder, CSV export, and hardening phases. Existing code
  review confirmed the current dashboard is a placeholder and locations are
  flat, so the plan includes a `locations.parent_id` hierarchy for
  sublocations. STOP. Await orchestrator approval before implementation.
- 2026-06-12 - Orchestrator approved the overall plan. Phase 1 scope confirmed:
  add a `locations.parent_id` hierarchy, model relationships, admin management
  support, and targeted hierarchy tests. STOP. Await Phase 1 approval before
  implementation.
- 2026-06-12 - Orchestrator approved Phase 1. Implementation started.
- 2026-06-12 - Phase 1 implemented:
  - Added `locations.parent_id` migration with self-referencing foreign key,
    index, and null-on-delete behavior.
  - Updated `Location` with parent/children relationships, `roots` and
    `forCompany` scopes, `display_name`, and shared validation preventing
    self-parenting or cross-company parent assignment.
  - Updated standalone and company-scoped Filament location forms/tables to
    manage and display parent locations and sublocation counts.
  - Added `tests/Feature/Models/LocationHierarchyTest.php`.
  - Verification passed:
    `php artisan test tests/Feature/Models/LocationHierarchyTest.php
    tests/Feature/StoreTest.php tests/Feature/StoreCheckoutPageTest.php
    tests/Feature/Api/StoreCheckoutTest.php` (20 tests, 107 assertions).
  - `vendor/bin/pint` passed for touched Phase 1 files.
  - STOP. Await orchestrator validation before Phase 2.
- 2026-06-12 - Orchestrator instructed to continue through all remaining
  phases without additional approval prompts. Phase 1 marked Complete and
  Phase 2 started.
- 2026-06-12 - Phase 2 implemented:
  - Added `DashboardController`, `DashboardFilterRequest`,
    `UserDashboardDataService`, and `DashboardDateRange`.
  - Rewired `/dashboard` to the controller and added scoped dashboard props.
  - Added backend tests for route rendering, empty/no-company state,
    cross-company isolation, date filtering, and location rollups.
  - Verification passed: dashboard backend tests (9 tests, 34 assertions).
  - Phase 3 started.
- 2026-06-12 - Phases 3-6 implemented:
  - Replaced the placeholder dashboard with an operational normal-user
    purchasing dashboard.
  - Added account/profile summary, KPI cards, recent orders, top purchased
    items, empty states, time-frame/location filters, and dependency-free bar
    chart components.
  - Added report builder page at `/dashboard/reports` and CSV export at
    `/dashboard/reports/export`.
  - Added `DashboardReportRequest`, `UserDashboardReportService`,
    `DashboardReportController`, and `DashboardReportExportController`.
  - Updated authenticated navigation to include Reports for normal users.
  - Added dashboard/report tests covering route access, no-company state,
    cross-company isolation, date filtering, location/sublocation rollups, and
    CSV export.
  - Admin-only `/dashboard` requests now redirect to Filament; normal web
    users use the customer dashboard.
  - Verification passed:
    `php artisan test tests/Feature/DashboardTest.php
    tests/Unit/Dashboard/DashboardDateRangeTest.php tests/Feature/Dashboard
    tests/Feature/Auth/AuthenticationTest.php tests/Feature/StoreTest.php
    tests/Feature/StoreCheckoutPageTest.php tests/Feature/Api/StoreCheckoutTest.php`
    (36 tests, 180 assertions).
  - `npm run build` passed.
  - `vendor/bin/pint` passed for dashboard/module files.
  - Module complete.

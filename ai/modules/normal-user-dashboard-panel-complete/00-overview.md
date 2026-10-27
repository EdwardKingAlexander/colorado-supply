# Normal User Dashboard Panel - Overview

## Problem Statement

The current normal-user dashboard is a placeholder Inertia page that only says
"You're logged in!" It does not help a store customer understand account
activity, manage profile details, review purchasing history, or generate
location-based spending reports.

Build a full customer dashboard panel for normal `web` users that supports:

- Account/profile visibility and edits.
- Order and payment summaries.
- Spend analytics with selectable time frames.
- Location and sublocation reporting based on the user's company structure and
  purchase history.
- Report generation/export suitable for operational purchasing review.

This plan is only for normal users. Filament admin tooling is out of scope
except where admin-managed data such as locations must support the dashboard.

## Current State

- `/dashboard` renders `resources/js/Pages/Dashboard.vue`, a basic placeholder.
- Profile editing exists at `/profile` through `ProfileController` and
  `resources/js/Pages/Profile/Edit.vue`.
- `User` belongs to `Company`; `Company` has many `Location` records.
- `Location` is currently flat: `company_id`, `name`, `slug`.
- `Order` has `portal_user_id`, `company_id`, totals, status, payment status,
  and timestamps.
- `OrderItem` has `location_id`, item name/SKU, quantity, unit price, and line
  total, which is enough for spending analytics by location.
- There is no existing sublocation data model.

## Key Assumptions

- "Normal user" means an authenticated `web` guard user, not an `admin` guard
  account.
- A user's visible dashboard data is scoped to their company and/or their own
  portal orders.
- "Sublocations" should be modeled as child locations under parent locations,
  not as a separate table, unless implementation reveals a hard reason to
  split them.
- Purchase reporting should use order-item `location_id` as the source of truth.
  Items without a location will appear under an "Unassigned" bucket.
- Reports should cover paid/confirmed orders by default, with filters for other
  statuses available if approved.
- Exports should start with CSV, because it is simple, testable, and useful for
  purchasing workflows. PDF can be added later if needed.

## Scope

### In Scope

- Replace the placeholder dashboard with a useful customer portal landing page.
- Add dashboard backend query/service layer for stats and chart data.
- Add selectable date ranges and custom start/end dates.
- Add location and sublocation hierarchy support.
- Add spend charts by period, category, location, and sublocation.
- Add recent orders, order status summary, payment summary, and top purchased
  items.
- Add account/profile panels that link to existing profile edit workflows.
- Add report builder UI for date range, location/sublocation, status, and
  grouping options.
- Add CSV report export endpoint.
- Add feature and unit tests covering authorization, aggregation, exports, and
  UI rendering.

### Out of Scope

- Admin dashboard redesign.
- Full CRM dashboard for sales staff.
- New payment gateway work.
- PDF reports unless explicitly added later.
- Complex forecasting or AI recommendations.
- Mutating order history from the customer dashboard.

## Architecture

```text
Authenticated web user
  -> GET /dashboard
  -> DashboardController
  -> UserDashboardDataService
       -> DashboardDateRange value object
       -> DashboardSpendQuery / report query helpers
       -> Orders + OrderItems + Locations scoped to company/user
  -> Inertia Dashboard.vue
       -> Account summary panel
       -> KPI summary tiles
       -> Time-frame controls
       -> Charts and tables
       -> Report builder entry point

Authenticated web user
  -> GET /dashboard/reports
  -> DashboardReportController@index
  -> Inertia report builder page

Authenticated web user
  -> GET /dashboard/reports/export?...
  -> DashboardReportExportController
  -> CSV streamed response
```

## Data Model Plan

### Locations and Sublocations

Use a self-referencing hierarchy on `locations`:

- `locations.parent_id` nullable foreign key to `locations.id`.
- Parent locations represent primary locations.
- Child locations represent sublocations.
- Enforce that parent and child belong to the same company.
- Existing flat locations remain valid as parent/root locations.

### Purchase Attribution

- `order_items.location_id` continues to point to `locations.id`.
- If it points to a child location, reports can show both:
  - Parent location rollup.
  - Child/sublocation drilldown.
- If it points to a root location, parent and leaf are the same.
- If it is null, the item is grouped as "Unassigned".

## Dashboard UX Plan

The dashboard should feel like an operational purchasing workspace, not a
marketing page.

First screen:

- Compact header with account/company identity and quick actions.
- KPI strip:
  - Total spend in selected period.
  - Orders placed.
  - Average order value.
  - Open/unpaid orders.
  - Top location by spend.
- Date range control:
  - This month.
  - Last 30 days.
  - Quarter to date.
  - Year to date.
  - Last 12 months.
  - Custom.
- Main content:
  - Spending over time chart.
  - Spend by location/sublocation chart.
  - Recent orders table.
  - Top purchased items table.
  - Account/profile panel.
  - Report builder shortcut.

Report builder:

- Filters:
  - Date range.
  - Parent location.
  - Sublocation.
  - Order status.
  - Payment status.
  - Group by: month, location, sublocation, product, order.
- Preview table.
- Export CSV action.

## Data Flow

```text
User selects time frame
  -> Inertia visit or partial reload with query parameters
  -> Controller validates filters
  -> Service scopes orders:
       company_id = user.company_id when available
       and/or portal_user_id = user.id for user-owned order history
  -> Service joins order_items + locations
  -> Aggregates totals
  -> Returns normalized props:
       summary, charts, recentOrders, topItems, locations, filters
  -> Vue renders dashboard without client-side financial calculations
```

## Authorization and Privacy

- Dashboard routes must require `auth` and should remain `web` user focused.
- Admins can continue to use admin/store testing flows separately; this
  dashboard should not become an admin bypass surface.
- A user should not see another company's order data.
- If `user.company_id` is null, the dashboard should gracefully show only the
  user's own orders and a setup/empty-state message for locations.
- Export endpoints must enforce the same scoping as the dashboard.

## Reporting Rules

- Monetary values are aggregated from server-side decimal columns, not browser
  calculations.
- Date filters should use the app timezone.
- Default reporting should use `created_at` initially; a later option may add
  "paid_at vs created_at" if needed.
- Canceled/deleted orders should be excluded by default.
- Refunded orders should be visible in payment status breakdowns and excluded
  or netted only if the phase-specific implementation plan chooses a clear
  rule.

## Risks

- **Ambiguous sublocation model**: There is no existing sublocation table.
  Mitigation: use `locations.parent_id`, preserve existing data, and add tests
  for same-company parent validation.
- **Incorrect financial totals**: Client-side aggregation could drift from
  persisted order totals. Mitigation: aggregate on the server and test with
  known fixtures.
- **Multi-tenancy leaks**: Reports could accidentally join cross-company
  records. Mitigation: centralize dashboard query scoping in one service and
  test cross-company isolation.
- **Large report performance**: Reporting over many order items could become
  slow. Mitigation: add indexes and limit preview rows; export can stream.
- **UI clutter**: A dashboard can easily become too busy. Mitigation: build
  compact operational panels and keep report configuration on a separate page.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Location hierarchy and reporting foundation | `phase-1-location-reporting-foundation.md` |
| 2 | Dashboard backend data service and route wiring | `phase-2-dashboard-backend.md` |
| 3 | Dashboard UI and account panel | `phase-3-dashboard-ui-account-panel.md` |
| 4 | Charts and interactive time-frame filtering | `phase-4-charts-timeframes.md` |
| 5 | Report builder and CSV export | `phase-5-report-builder-export.md` |
| 6 | Testing, hardening, and performance pass | `phase-6-testing-hardening.md` |

Status tracking lives in `STATE.md`.

**STOP. Await plan approval before implementation begins.**

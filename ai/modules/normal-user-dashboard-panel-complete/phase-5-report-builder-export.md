# Phase 5 - Report Builder and CSV Export

## Goal

Give normal users a dedicated report builder where they can generate purchasing
reports by date range, location, sublocation, product, order, and status, then
export the result.

## Scope

- Add report builder page.
- Add report preview endpoint/page props.
- Add CSV export endpoint.
- Reuse Phase 2/4 filtering and aggregation rules.
- Keep report access scoped to the normal user's allowed data.

## Files to Create

- `app/Http/Controllers/DashboardReportController.php`
- `app/Http/Controllers/DashboardReportExportController.php`
- `app/Http/Requests/Dashboard/DashboardReportRequest.php`
- `app/Services/Dashboard/UserDashboardReportService.php`
- `resources/js/Pages/Dashboard/Reports.vue`
- `resources/js/Components/Dashboard/ReportBuilderForm.vue`
- `resources/js/Components/Dashboard/ReportPreviewTable.vue`
- `tests/Feature/Dashboard/DashboardReportTest.php`
- `tests/Feature/Dashboard/DashboardReportExportTest.php`

## Files to Modify

- `routes/web.php`
- `resources/js/Pages/Dashboard.vue`
- Dashboard navigation components if needed

## Routes

- `GET /dashboard/reports`
  - Shows report builder and preview.
  - Name: `dashboard.reports`.

- `GET /dashboard/reports/export`
  - Streams CSV based on validated filters.
  - Name: `dashboard.reports.export`.

## Report Filters

- Date range.
- Parent location.
- Sublocation.
- Order status.
- Payment status.
- Group by:
  - Month.
  - Parent location.
  - Sublocation.
  - Product.
  - Order.

## CSV Columns

Column set depends on group mode, but common fields should include:

- Period or date.
- Parent location.
- Sublocation.
- Order number when grouped by order/item.
- Product/SKU when grouped by product.
- Quantity.
- Spend.
- Order status.
- Payment status.

## Preview Rules

- Preview should be limited to a reasonable number of rows.
- Export should include the full result within validated filters.
- Empty reports should show a useful no-results state.

## Tests to Write

- Report page renders for normal users.
- Guests cannot access reports.
- Cross-company data is excluded.
- Report filters produce expected grouped totals.
- CSV export returns correct headers and rows.
- Cross-company location filters cannot leak data.
- User with no company can export only their own visible order data.

## Success Criteria

- Users can generate and preview reports.
- Users can export CSV files.
- Report data matches dashboard aggregation rules.
- Authorization is tested.
- Targeted report tests pass.
- Frontend build passes.
- `vendor/bin/pint --dirty` passes.

**STOP. Await phase approval before implementation.**

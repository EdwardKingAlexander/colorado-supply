# Phase 3 — First-Party Business Analytics

## Goal

Add an owner/admin analytics dashboard based on authoritative Colorado Supply
records, complementing the existing customer purchasing dashboard without
sending business data to another analytics vendor.

## Scope

- Add a dedicated aggregation service for owner/admin reporting.
- Add an authorized Filament page or equivalent established admin surface.
- Support preset/custom date ranges and comparison with the preceding period.
- Report revenue/order, product/category, quote, repair, refund, and payment
  outcome KPIs.
- Provide compact trend and breakdown charts using the existing dependency-free
  chart approach where practical.
- Permit CSV export only when it can reuse existing safe report/export patterns.
- Cache expensive aggregates briefly with tenant/date-aware cache keys and
  explicit invalidation or short TTLs.

## Expected Files to Create

- `app/Filament/Pages/BusinessAnalyticsDashboard.php`
- `app/Services/Analytics/BusinessAnalyticsService.php`
- `app/Support/Analytics/AnalyticsDateRange.php`
- `resources/views/filament/pages/business-analytics-dashboard.blade.php`
- Focused Filament widgets/components if the page architecture requires them
- `tests/Feature/Analytics/BusinessAnalyticsDashboardTest.php`
- `tests/Unit/Analytics/BusinessAnalyticsServiceTest.php`

## Expected Files to Modify

- `app/Providers/Filament/AdminPanelProvider.php` only if page discovery does
  not already register the page
- Relevant navigation configuration
- Existing export service only if a safe reusable path exists

No new third-party analytics or chart subscription is allowed. No new raw
tracking-event table is planned in this phase; authoritative domain records are
the source of truth. A future zero-result-search feature would require its own
privacy-reviewed plan.

## KPI Definitions

- Gross and paid revenue, paid orders, average order value.
- Refund count/value and payment failure count/rate.
- Order status and payment processor breakdowns.
- Top products and categories by quantity and revenue.
- Quotes created/accepted/converted where current model states support those
  definitions.
- Repair requests by status and completion/conversion where current records
  support it.
- Current-period versus preceding-period percentage changes with safe handling
  for zero denominators.

Before implementation, each KPI must be mapped to exact model fields and enum
values. Unsupported conversion claims must be omitted rather than inferred.

## Tests to Write

- Only authorized admins/owners can access the dashboard and export.
- Cross-company records are excluded for tenant-scoped roles; global admins
  follow existing admin scope rules.
- Date and comparison ranges use consistent timezone boundaries.
- Orders, payments, refunds, quotes, repairs, products, and categories aggregate
  correctly from fixtures.
- Canceled/failed states are included or excluded according to the documented
  KPI definition.
- Empty datasets and zero comparison periods render without errors.
- Query count/performance remains bounded for representative fixtures.

## Success Criteria

- An owner can answer the main sales, lead, repair, and payment questions from
  one internal page.
- KPIs have documented, tested definitions and honor authorization boundaries.
- The completed normal-user purchasing dashboard remains unchanged except for
  explicitly shared, backward-compatible helpers if needed.
- No business data is transmitted to a new third party.
- Targeted tests, `php artisan test`, and `vendor/bin/pint --dirty` pass.

**STOP. Await phase approval before implementation.**

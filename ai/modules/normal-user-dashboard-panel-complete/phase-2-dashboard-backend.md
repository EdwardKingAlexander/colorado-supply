# Phase 2 - Dashboard Backend Data Service and Route Wiring

## Goal

Replace the placeholder dashboard backend with a real, scoped data contract for
normal users. This phase prepares Inertia props for summary cards, charts,
tables, filters, and report links, but does not complete the final UI polish.

## Scope

- Add a dedicated dashboard controller.
- Add a service layer for dashboard aggregation.
- Add request validation for date range and location filters.
- Return normalized Inertia props to `Dashboard.vue`.
- Keep all financial aggregation on the server.

## Files to Create

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Requests/Dashboard/DashboardFilterRequest.php`
- `app/Services/Dashboard/UserDashboardDataService.php`
- `app/Support/Dashboard/DashboardDateRange.php`
- `tests/Feature/Dashboard/UserDashboardDataTest.php`
- `tests/Unit/Dashboard/DashboardDateRangeTest.php`

## Files to Modify

- `routes/web.php`
- `resources/js/Pages/Dashboard.vue` only enough to consume props safely or
  show a temporary structured layout
- `tests/Feature/DashboardTest.php`

## Route Design

- `GET /dashboard`
  - Name: `dashboard`
  - Middleware: `auth`, `verified` if email verification remains required for
    normal users
  - Controller: `DashboardController`

Admin guard support added for store testing should not make this dashboard an
admin analytics route. If admin access remains technically possible through
previous auth changes, controller behavior should redirect admins to Filament or
show a neutral state unless the orchestrator approves admin dashboard reuse.

## Filter Inputs

Supported query parameters:

- `range`: `this_month`, `last_30_days`, `quarter_to_date`, `year_to_date`,
  `last_12_months`, `custom`
- `start_date`: required only for custom
- `end_date`: required only for custom
- `location_id`: nullable
- `sublocation_id`: nullable
- `status`: nullable order status
- `payment_status`: nullable payment status

## Service Output Contract

`UserDashboardDataService` returns:

```php
[
    'filters' => [...],
    'account' => [
        'name' => string,
        'email' => string,
        'company' => ?array,
        'profile_complete' => bool,
    ],
    'summary' => [
        'total_spend' => float,
        'orders_count' => int,
        'average_order_value' => float,
        'open_orders_count' => int,
        'unpaid_orders_count' => int,
        'top_location' => ?array,
    ],
    'charts' => [
        'spend_over_time' => [],
        'spend_by_location' => [],
        'spend_by_sublocation' => [],
        'payment_status_breakdown' => [],
    ],
    'recent_orders' => [],
    'top_items' => [],
    'locations' => [],
]
```

## Query Rules

- Use the authenticated user's company when `company_id` exists.
- Also ensure portal-user visibility for user-specific order history where
  appropriate.
- Exclude soft-deleted orders.
- Default to non-canceled orders.
- Use `OrderItem.line_total` for item-level location/product aggregation.
- Use `Order.grand_total` for order-level summary cards.

## Tests to Write

- Dashboard route renders for a normal user.
- Guest users are redirected to login.
- A user with no company receives an empty company/location state without a
  crash.
- Cross-company orders do not appear in stats.
- Date ranges produce correct start/end boundaries.
- Summary totals match known order fixtures.
- Location and sublocation aggregations roll up correctly.

## Success Criteria

- `/dashboard` returns meaningful structured props.
- Data scoping is centralized and tested.
- Existing profile and store routes continue working.
- No final dashboard design or report export is required in this phase.
- Targeted dashboard tests pass.
- `vendor/bin/pint --dirty` passes.

**STOP. Await phase approval before implementation.**

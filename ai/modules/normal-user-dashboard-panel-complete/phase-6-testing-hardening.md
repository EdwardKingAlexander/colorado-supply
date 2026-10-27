# Phase 6 - Testing, Hardening, and Performance Pass

## Goal

Harden the dashboard and reporting feature for production use by expanding test
coverage, checking performance-sensitive queries, and verifying the user
experience across realistic states.

## Scope

- Add broader integration tests.
- Review database indexes and query patterns.
- Verify no cross-company data leakage.
- Verify frontend build and responsive layout.
- Confirm empty/error states.
- Update module state with known residual risks.

## Files to Create

- `tests/Feature/Dashboard/DashboardAuthorizationTest.php`
- `tests/Feature/Dashboard/DashboardPerformanceShapeTest.php`
- Additional focused tests only where previous phases leave gaps

## Files to Modify

- Any dashboard service/component files requiring hardening from test findings
- `ai/modules/normal-user-dashboard-panel-complete/STATE.md`

## Hardening Checklist

- Authorization:
  - Guest users redirected.
  - Normal users see only permitted orders.
  - Cross-company order items are excluded.
  - Cross-company locations cannot be used as filters.

- Data correctness:
  - Null location item handling.
  - Parent location rollups.
  - Sublocation drilldowns.
  - Refunded/failed/unpaid status behavior.
  - Custom date edge cases.

- Performance:
  - Confirm queries use indexes on `orders.company_id`,
    `orders.portal_user_id`, `orders.created_at`, `order_items.order_id`,
    `order_items.location_id`, and `locations.parent_id`.
  - Add missing indexes only if tests or query review show a real gap.
  - Keep report preview bounded.

- UX:
  - No broken dashboard state for no-company users.
  - No broken dashboard state for no-order users.
  - Filters are visible and preserve current selections.
  - Mobile layout does not overlap or hide controls.

## Tests to Run

- All dashboard/report tests.
- Existing auth/profile tests.
- Existing store checkout tests, because dashboard links into purchasing flows.
- Frontend build.

Suggested commands:

```bash
php artisan test tests/Feature/Dashboard tests/Feature/Auth tests/Feature/StoreTest.php tests/Feature/StoreCheckoutPageTest.php
npm run build
vendor/bin/pint --dirty
```

## Success Criteria

- Dashboard feature is production-ready for normal users.
- Authorization and aggregation are covered by tests.
- Performance-sensitive queries have appropriate indexes or documented
  rationale.
- Frontend build passes.
- Known unrelated failures, if any, are documented in `STATE.md`.
- Module phase is set to `Awaiting Validation`.

**STOP. Await phase approval before implementation.**

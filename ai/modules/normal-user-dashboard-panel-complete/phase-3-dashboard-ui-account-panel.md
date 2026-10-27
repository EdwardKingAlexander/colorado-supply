# Phase 3 - Dashboard UI and Account Panel

## Goal

Build the primary normal-user dashboard experience using the backend props from
Phase 2. The result should be a practical account and purchasing workspace, not
a placeholder or marketing page.

## Scope

- Replace the current placeholder `Dashboard.vue`.
- Add account/profile summary and account-management entry points.
- Add KPI cards and recent activity sections.
- Add empty states for new users and users without company/location data.
- Keep charts basic or placeholder-ready; Phase 4 owns full chart behavior.

## Files to Create

- `resources/js/Components/Dashboard/AccountSummaryPanel.vue`
- `resources/js/Components/Dashboard/DashboardKpiGrid.vue`
- `resources/js/Components/Dashboard/RecentOrdersTable.vue`
- `resources/js/Components/Dashboard/TopItemsTable.vue`
- `resources/js/Components/Dashboard/DashboardEmptyState.vue`
- `tests/Feature/Dashboard/DashboardPageTest.php`

## Files to Modify

- `resources/js/Pages/Dashboard.vue`
- `resources/js/Layouts/AuthenticatedLayout.vue` if navigation needs clearer
  dashboard/profile/report links
- Existing profile page links only if needed for account-management flow

## UI Requirements

- The dashboard should be dense, readable, and operational.
- Avoid oversized hero sections and decorative card-heavy marketing layout.
- Use compact panels with clear labels.
- Include obvious actions:
  - Edit profile.
  - Change password.
  - Browse store.
  - View report builder.
  - View recent order details or payment page where supported.
- Account panel should show:
  - Name.
  - Email.
  - Company.
  - Assigned locations count.
  - Profile completion indicator.

## Dashboard Sections

1. Header
   - User/company identity.
   - Selected reporting period.
   - Quick action buttons.

2. KPI strip
   - Total spend.
   - Orders.
   - Average order value.
   - Open/unpaid orders.
   - Top location.

3. Purchasing activity
   - Recent orders.
   - Top purchased items.
   - Payment status summary.

4. Account management
   - Profile details.
   - Links to edit profile/password.
   - Company/location state.

5. Reporting entry point
   - Short panel linking to report builder with current filters.

## Empty States

- No orders yet:
  - Show a concise state with a Browse Store action.
- No company:
  - Explain that location reporting is unavailable until the account is linked
    to a company.
- No locations:
  - Show purchasing stats but hide location drilldowns.

## Tests to Write

- Dashboard page renders the expected Inertia component.
- Dashboard props include account and summary data.
- Empty state appears for a user with no orders.
- Account panel includes profile edit route.
- Recent order table uses scoped orders only.

## Success Criteria

- `/dashboard` is useful for a normal user.
- Account-management options are visible and connected to existing profile
  routes.
- UI handles no-company, no-location, and no-order users.
- No full chart implementation is required yet.
- Targeted feature tests pass.
- `npm run build` should pass if frontend build is run in this phase.
- `vendor/bin/pint --dirty` passes for PHP changes.

**STOP. Await phase approval before implementation.**

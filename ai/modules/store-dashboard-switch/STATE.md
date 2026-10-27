# Store Dashboard Switch — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Approved | 2026-10-04 | User: "Approved" |
| 1 | Dashboard switch widget + maintenance copy | Awaiting Validation | 2026-10-05 | Implemented, tested, verified live |

## Log

- 2026-10-04 — User asked for an on/off slider on the admin dashboard to
  open/close the store, with customers sent to an "Under Maintenance" page
  when off. Investigation found the flag (`store-settings`), middleware
  (`EnsureStoreEnabled`), customer page (`Store/Unavailable.vue`), and two
  click-to-toggle actions (MCP Dashboard header, profile menu) already exist.
  Plan scoped to a dashboard switch widget, a shared helper, and
  "Under Maintenance" copy. Awaiting plan approval.
- 2026-10-04 — User approved the plan ("Approved"). Implemented phase 1:
  - `App\Support\StoreAvailability` (`isEnabled`, `set`, `maintenancePageProps`)
    now backs the middleware, the MCP Dashboard action and the profile-menu
    action (one source of truth for the `store-settings` flag).
  - `StoreStatusWidget` on `/admin` (sort -10, full width, not lazy) with a
    `<x-filament::toggle>` entangled live to `$storeEnabled`; persisting in
    `updatedStoreEnabled()` with a notification.
  - `Store/Unavailable.vue` retitled "Store Under Maintenance"; admin-only
    `GET /store/maintenance-preview` (`auth:admin`) added before `/store/{slug}`.
  - Stale "Regular users will see a 403 error" modal copy fixed in both actions.
  - Tests: `tests/Feature/Filament/StoreStatusWidgetTest.php` (5). Full-page
    `/admin` test needs the admin enrolled in MFA (`toggleEmailAuthentication(true)`)
    because the panel enforces MFA.
  - Full suite: 899 passed / 5 skipped / 0 failed. Pint clean. `npm run build` OK.
- 2026-10-05 — Browser verification on https://colorado-supply.test/admin:
  card renders at top of dashboard; switching off persisted (`enabled=false`)
  and survived reload; maintenance preview renders the new copy; switching back
  on persisted (`enabled=true`). Found and fixed one issue: Tailwind utility
  classes in the widget view weren't styled (panel uses Filament's precompiled
  CSS, no custom theme), so the layout uses inline styles. Store left **open**.
- 2026-10-10 — Committed by the product-detail-page session at the owner's request (owner chose to commit the working state). Still awaiting owner validation.

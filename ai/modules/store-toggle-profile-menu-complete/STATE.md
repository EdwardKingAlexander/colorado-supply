# Store Toggle in Profile Menu — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Approved | 2026-07-10 | User: "clean it up and implement the button" |
| 1 | User-menu toggle action | Complete | 2026-07-10 | Implemented, tested, verified live |

## Log

- 2026-07-10 — User asked for a dropdown control in the admin profile
  menu to enable/disable the store for non-admin users, mid-Laravel-13-
  upgrade deployment checklist. Investigated existing code before
  planning: `EnsureStoreEnabled` middleware, `McpSettings` persistence,
  and a working "Disable Store"/"Enable Store" header action on
  `McpDashboard` already fully implement this — nothing new needed on
  the backend, this is a UI-surface-only addition (a second, quicker
  entry point to the same toggle).
  - Asked two clarifying questions before planning per this project's
    workflow: menu-item style (plain click-to-toggle text vs. a real
    switch control) and whether to keep the confirmation modal. User
    chose plain click-to-toggle item, with confirmation kept.
  - Confirmed which of the two duplicate Filament admin panel providers
    (`AdminPanelProvider.php` / `AdminPanelPanelProvider.php`, both
    registered for panel id `admin`) is actually active by checking
    which one's `discoverResources()` directory exists on disk —
    `AdminPanelPanelProvider.php` points at a nonexistent
    `app/Filament/AdminPanel/` directory and is dead code. Plan targets
    `AdminPanelProvider.php`.
  - Plan drafted (`00-overview.md` + `phase-1-user-menu-toggle-action.md`),
    single phase since the backend already exists. Awaiting approval.
- 2026-07-10 — User approved and asked to implement directly ("clean it
  up and implement the button").
  - Deleted `AdminPanelPanelProvider.php` and removed its entry from
    `bootstrap/providers.php` (only referenced itself, the cached
    `bootstrap/cache/services.php`, and the providers list — safe).
  - Added `storeToggleUserMenuAction()` to `AdminPanelProvider.php`,
    wired into `->userMenuItems([...])`, mirroring
    `McpDashboard::getHeaderActions()`'s `toggleStore` action but with
    all state-dependent parts as Closures (re-read `McpSettings` fresh
    on every menu render, not computed once). Added a "keep in sync"
    comment in both places.
  - Wrote `tests/Feature/Filament/StoreToggleUserMenuTest.php` (3
    tests). Hit and fixed two real test-writing bugs along the way,
    not application bugs:
    1. `getUserMenuItems()` evaluates all items eagerly, including
       Filament's default profile item which needs an authenticated
       user — had to call `actingAs()` before `getUserMenuItems()`,
       not after.
    2. The 403-for-non-admin test kept getting a 200. Root cause:
       Laravel's `actingAs($admin, 'admin')` calls `shouldUse('admin')`
       internally, which persists as the "default guard" for the rest
       of the test. A later `actingAs($user)` (no explicit guard)
       silently resolved back to `'admin'` instead of `'web'` because
       of that lingering `shouldUse` state — re-authenticating the
       admin guard with a `User` model instead of touching the web
       guard at all. Fixed by explicitly passing `actingAs($user,
       'web')` rather than relying on the default.
  - Full test suite: 71 failed/5 skipped/458 passed (455 baseline + 3
    new, zero regressions). Pint clean.
  - Verified live in the browser: dropdown shows "Disable Store"/
    "Enable Store" with correct color/icon for current state,
    confirmation modal matches the existing MCP Dashboard control's
    copy, confirming actually flips `store-settings.enabled`. Restored
    to `enabled: true` after manual testing so as not to leave the
    storefront disabled.
  - Committed `585e7189`. Left unrelated concurrent changes from
    another agent (Navigation.vue, Login.vue, Register.vue, a new
    BrandedAuthLayout.vue, logo, SSR asset rebuild churn) uncommitted —
    out of scope for this module.
  - Phase 1 and the module marked **Complete**.

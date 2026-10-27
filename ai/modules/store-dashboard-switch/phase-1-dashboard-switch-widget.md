---
plan: store-dashboard-switch
phase: 1
title: Dashboard switch widget + maintenance copy
status: awaiting_validation
last_updated: 2026-10-05
depends_on: []
checkpoint: 1c-maintenance-copy-preview
---

# Phase 1 — Dashboard Switch Widget + Maintenance Copy

## Goal

An admin opens `/admin`, sees a "Store Status" card at the top with a
switch, flips it off, and customers hitting any `/store*` URL see the
"Store Under Maintenance" page. Flipping it back on restores the store.

## Sub-phases

### 1a. `StoreAvailability` helper
- Create `app/Support/StoreAvailability.php` with `isEnabled(): bool` and
  `set(bool $enabled): void` wrapping `McpSettings` key `store-settings`.
- Refactor `EnsureStoreEnabled`, `McpDashboard::getHeaderActions()`, and
  `AdminPanelProvider::storeToggleUserMenuAction()` to use it.
- **Done when**: existing `StoreToggleUserMenuTest` + store tests still pass.

### 1b. `StoreStatusWidget`
- Create `app/Filament/Widgets/StoreStatusWidget.php` (+ Blade view
  `resources/views/filament/widgets/store-status-widget.blade.php`):
  - `$sort = -10`, `$columnSpan = 'full'`.
  - Public `bool $storeEnabled` loaded in `mount()` from the helper.
  - Filament `Toggle` (`onColor('success')`, `offColor('danger')`,
    `live()`), `afterStateUpdated` → `StoreAvailability::set()` +
    success notification.
  - Status line: "Open — customers can browse and order" /
    "Closed — customers see the Under Maintenance page".
  - Link "Preview maintenance page" → admin-only preview route (1c).
- **Done when**: widget renders on `/admin`, switch persists.

### 1c. Maintenance page copy + preview
- `Store/Unavailable.vue`: heading → "Store Under Maintenance",
  subheading "We'll be back online soon", `<title>` updated.
- Add `GET /store/maintenance-preview` (admin guard only) rendering the
  same Inertia page so admins can see what customers see.
- Update stale "403 error" modal copy in both existing actions.
- **Done when**: preview route works for admins, 403/redirect for others.

## Files

- New: `app/Support/StoreAvailability.php`,
  `app/Filament/Widgets/StoreStatusWidget.php`,
  `resources/views/filament/widgets/store-status-widget.blade.php`,
  `tests/Feature/Filament/StoreStatusWidgetTest.php`
- Modify: `app/Http/Middleware/EnsureStoreEnabled.php`,
  `app/Filament/Pages/McpDashboard.php`,
  `app/Providers/Filament/AdminPanelProvider.php`,
  `resources/js/Pages/Store/Unavailable.vue`, `routes/web.php`

## Tests

- Widget renders for admin with switch reflecting current state.
- Toggling off writes `enabled=false`; non-admin `/store` →
  `Store/Unavailable`; admin `/store` still → store index.
- Toggling on restores access.
- Preview route: admin 200 + `Store/Unavailable`, web user denied.

## Success criteria

`php artisan test` shows no new failures vs. baseline (~71 pre-existing),
`vendor/bin/pint --dirty` clean, `npm run build` succeeds, switch verified
in the browser.

## Risks

- Filament widget with an embedded form needs `HasForms`/`InteractsWithForms`
  (v4: `HasSchemas`/`InteractsWithSchemas`) — verify against installed
  version before writing.

STOP. Await phase approval.

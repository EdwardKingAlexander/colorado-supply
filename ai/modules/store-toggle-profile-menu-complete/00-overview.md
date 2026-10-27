# Store Toggle in Profile Menu — Overview

## Problem Statement

Admins currently have to navigate to the MCP Dashboard page
(`/admin/mcp-control-panel`) to enable/disable the storefront for
non-admin users. The user wants quick access to this same toggle
directly from the admin panel's profile dropdown (top-right, where
Profile/Admin Panel/Sign Out already live), without leaving whatever
page they're on.

## What Already Exists (do not rebuild)

- `App\Http\Middleware\EnsureStoreEnabled`: gates all `/store/*` routes.
  Reads `McpSettings::for('store-settings', ['enabled' => true])`;
  admins (`Auth::guard('admin')->check()`) always pass through even when
  disabled; non-admins get a 403 when disabled.
- `App\Support\McpSettings::for()` / `::put()`: generic key/value
  settings persistence backed by the `mcp_settings` table.
- `App\Filament\Pages\McpDashboard::getHeaderActions()`: an existing
  `Action::make('toggleStore')` header action that already implements
  the exact toggle behavior (label/icon/color flip based on current
  state, confirmation modal, `McpSettings::put()` call, success/failure
  notifications).

This feature is UI-surface-only: expose the same toggle as a Filament
user-menu item, reusing the existing action logic rather than
duplicating it.

## Constraints / Design Decisions (confirmed with user)

- Menu item style: a plain click-to-toggle Filament dropdown item
  (label text flips between "Disable Store" / "Enable Store"),
  consistent with the existing Profile/Admin Panel/Sign Out items —
  not a custom visual switch/slider control.
- Keep the confirmation modal before applying (matches the existing
  MCP Dashboard control; this affects live customer-facing access).
- The existing MCP Dashboard header action stays as-is; this adds a
  second, quicker entry point, it doesn't replace the first.

## Architecture

Filament panels expose `->userMenuItems([...])` on the panel
provider, accepting an array of `Filament\Actions\Action` instances
rendered into the dropdown (`vendor/filament/filament/resources/views/
components/user-menu.blade.php`, confirmed during the Laravel 13
upgrade's Phase 6 investigation — same component whose positioning bug
was just fixed in `ai/modules/laravel-13-upgrade`).

Add one `Action::make('toggleStoreAvailability')` to
`AdminPanelProvider::panel()`'s `->userMenuItems([...])` call
(confirmed via directory check to be the actually-active provider —
`AdminPanelPanelProvider.php` points at a nonexistent
`app/Filament/AdminPanel/` resource directory and is dead code, out of
scope for this fix). The action's closures mirror
`McpDashboard::getHeaderActions()`'s `toggleStore` action verbatim
(same settings key, same notification copy), so behavior is identical
between both entry points.

## Data Flow

```
Profile dropdown item click
  -> Filament Action (requiresConfirmation)
  -> confirm in modal
  -> Action::action() closure
  -> McpSettings::put('store-settings', ['enabled' => !current])
  -> Notification::make()->send()
  -> (next request to /store/*) EnsureStoreEnabled reads the new value
```

No new tables, no new middleware, no new routes.

## Risks

- Low risk: reuses fully-tested existing persistence/middleware, only
  adds a UI entry point. Main risk is drift between the two action
  definitions (MCP Dashboard's and the new user-menu one) if one is
  edited later without the other — noted in the phase file as a
  "keep in sync" comment in both places rather than extracting a shared
  helper (extracting one for two ~15-line closures would be
  over-engineering per this project's simplicity guidance).

## Phases

| # | Phase | Goal |
|---|-------|------|
| 1 | User-menu toggle action | Add the toggle action to the admin panel's user menu, verified live and via test |

**STOP. Await plan approval.**

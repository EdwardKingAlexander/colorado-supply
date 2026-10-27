# Phase 4 — Admin Sidebar Store-Availability Toggle

## Goal

Replace the buried "Disable/Enable Store" header action on
`McpDashboard` (Automation → MCP Control Panel) with an always-visible
switch in the admin panel's own sidebar, so store availability can be
flipped from anywhere in the admin panel without navigating to a specific
page.

## Confirmed API Surface

Checked the installed Filament version's
`vendor/filament/filament/src/View/PanelsRenderHook.php` — `SIDEBAR_FOOTER`
(`panels::sidebar.footer`) exists and is designed for exactly this: fixed
content pinned in the sidebar, independent of which nav item is active.
Use this hook; `SIDEBAR_NAV_END` is the fallback if `SIDEBAR_FOOTER`
doesn't render where expected during implementation.

## Files to Create/Modify

- `app/Livewire/Filament/StoreAvailabilityToggle.php` (new) — a real
  Livewire component (not Volt, to match this repo's convention of
  class-based Livewire elsewhere):
  - `public bool $enabled` — hydrated in `mount()` from
    `McpSettings::for('store-settings', ['enabled' => true])['enabled']`.
  - `updatedEnabled()` (or an explicit `toggle()` method bound to the
    switch's `wire:click`) — persists via
    `McpSettings::put('store-settings', ['enabled' => $this->enabled], 'Store availability settings')`
    and fires a Filament `Notification` confirming the new state.
  - Only rendered for authenticated admin users (the render hook only fires
    inside the admin panel layout, so this is implicit, but double check
    guard access if the component is ever reachable outside a panel
    context).
- `resources/views/livewire/filament/store-availability-toggle.blade.php`
  (new) — small switch UI (label "Store" + an on/off pill/switch styled
  with the same Tailwind conventions Filament v4 uses elsewhere in this
  app), `wire:click` or `wire:model.live` wired to the component.
- `app/Providers/Filament/AdminPanelProvider.php` — register the render
  hook:
  ```php
  ->renderHook(
      \Filament\View\PanelsRenderHook::SIDEBAR_FOOTER,
      fn () => \Illuminate\Support\Facades\Blade::render('@livewire(\'filament.store-availability-toggle\')'),
  )
  ```
  (exact registration mechanics — e.g. using a Livewire component's
  auto-discovered tag vs. an explicit `Livewire::mount()` call in a closure
  — finalized during implementation; goal is unchanged: the toggle renders
  in the sidebar on every admin page.)
- `app/Filament/Pages/McpDashboard.php` — remove the `toggleStore`
  `Action` from `getHeaderActions()` (and the now-unused `McpSettings`
  store-settings read at the top of that method, if nothing else in the
  page uses it). Confirmed via search that no other code or test depends on
  this specific action before removing it.

## Out of Scope

- No change to `EnsureStoreEnabled` middleware, `McpSettings` storage, or
  the public-facing store routes.
- No change to any other `McpDashboard` functionality (vendor login,
  product scraper, competitor pricing forms) — only the store toggle
  action is removed.

## Success Criteria

- Logging into `/admin`, the store toggle switch is visible in the sidebar
  on every admin page (not just one specific page), reflects the current
  `store-settings.enabled` value, and flipping it immediately updates
  `McpSettings` (verify via `tinker` or a feature test) with a confirmation
  notification — no full page reload required.
- `McpDashboard`'s old "Disable/Enable Store" header action no longer
  appears.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

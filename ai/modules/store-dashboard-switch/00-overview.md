# Store Dashboard Switch — Overview

## Problem

The admin wants an on/off **switch (slider)** on the admin dashboard
(`/admin`) to open or close the customer store. When the store is off,
customers should land on an "Under Maintenance" page.

## What already exists (do not rebuild)

- **Flag**: `McpSettings` key `store-settings` → `['enabled' => bool]`
  (default `true` when unset).
- **Gate**: `App\Http\Middleware\EnsureStoreEnabled` (alias
  `store.enabled`) on every `/store*` route in `routes/web.php`. Admin
  guard bypasses it; everyone else gets `Inertia::render('Store/Unavailable')`
  at HTTP 200.
- **Page**: `resources/js/Pages/Store/Unavailable.vue` — branded
  "temporarily unavailable" page with contact email.
- **Existing toggles** (click + confirm, not a switch):
  `McpDashboard` header action `toggleStore` and the profile-menu item
  `AdminPanelProvider::storeToggleUserMenuAction()`.

So this is a UI addition plus copy changes — no new storage, no new
middleware.

## Constraints

- Filament v4: schema components live in `Filament\Schemas\Components`,
  form fields in `Filament\Forms\Components\Toggle`.
- All three controls must read/write the same `store-settings` key so
  they never disagree.
- Admins must still be able to browse the store while it is closed
  (preview before reopening) — existing behavior, keep it.

## Architecture

```
/admin Dashboard
  └─ StoreStatusWidget (new Filament widget, sort first, full width)
       ├─ Toggle "Store open"  ──live──▶ McpSettings::put('store-settings', ...)
       └─ status text + "Preview maintenance page" link
Customer ──▶ /store* ──▶ EnsureStoreEnabled ──(off)──▶ Store/Unavailable.vue ("Under Maintenance")
```

A small `App\Support\StoreAvailability` helper (`isEnabled()`,
`set(bool)`) replaces the three copies of
`McpSettings::for('store-settings', ['enabled' => true])['enabled'] ?? true`
in the middleware, `McpDashboard`, and `AdminPanelProvider`.

## Decisions / assumptions

1. **Switch flips immediately, no confirmation modal** — a slider that
   pops a modal feels broken; a success notification confirms the change
   and the switch is one click to undo. (Profile-menu and MCP actions keep
   their confirmations.)
2. **"Under Maintenance" copy**: retitle the existing `Unavailable.vue`
   heading/`<title>` to "Store Under Maintenance" rather than creating a
   second page. Keep the contact-email CTA.
3. Fix stale modal copy in the two existing actions ("Regular users will
   see a 403 error" → "…will see the Under Maintenance page").
4. Scope stays `/store*` routes only — the rest of the customer portal
   (dashboard, profile, SAM) is unaffected.

## Risks

- Widget visibility: dashboard widgets are auto-discovered; the new widget
  must only render for the admin panel (it already requires `admin` auth).
- Livewire toggle state drifting from DB if another admin flips it in a
  different tab — mitigated by re-reading on mount; acceptable.

## Phases

| # | Phase | File |
|---|-------|------|
| 1 | Dashboard switch widget + maintenance copy | `phase-1-dashboard-switch-widget.md` |

# Phase 5 — Dead-Code Cleanup (De-Laravel-ify)

## Goal

Remove the Laravel/Breeze/Flux starter UI that no longer renders anywhere.
Verify each file before deleting it. Deletions are revertible via git.

## Verification (per candidate, recorded in STATE.md)
- No `Inertia::render` / route / `@include` / `<x-…>` / `<livewire:…>` /
  `Volt::route` / Filament reference from live code.
- `grep -rn` for the file's name/component across `app routes resources tests`.

## Candidates
Vue (made dead by Phases 2–4):
- `Layouts/AuthenticatedLayout.vue`
- `Components/NotificationBell.vue` (old bell; the shell uses
  `Components/Notifications/NotificationBell.vue`)
- `Components/NavLink.vue`, `ResponsiveNavLink.vue`, `Dropdown.vue`,
  `DropdownLink.vue` (only if no other caller remains)
- `Pages/Store/Public.vue`, `Pages/Store/Authenticated.vue` (no render call)

Blade starter kit (from the July investigation):
- `resources/views/dashboard.blade.php`
- `resources/views/components/layouts/app.blade.php`, `app/sidebar.blade.php`,
  `app/header.blade.php`
- `components/app-logo.blade.php`, `app-logo-icon.blade.php`,
  `placeholder-pattern.blade.php`, `components/settings/layout.blade.php`
- `resources/views/livewire/settings/*`
- `resources/views/flux/**` (only if no live Flux view uses it)
- `resources/views/livewire/auth/*` (only if proven dead)

## Out of Scope
- Composer/npm uninstalls (noted as follow-up only).
- Filament.

## Tests
- Full `php artisan test`, `npm run build`; hit `/dashboard`, `/store`,
  `/login`, `/register`, `/admin`.

## Success Criteria
- Every removed file was verified dead; the app and admin render; tests green.

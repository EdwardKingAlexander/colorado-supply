---
plan: laravel-13-upgrade
phase: 3
title: Inertia.js v2 → v3 migration
status: not_started
last_updated: 2026-07-09
depends_on: [phase-2]
checkpoint: none
---

# Phase 3 — Inertia.js v2 → v3 Migration

## Goal

Bump `inertiajs/inertia-laravel` and `@inertiajs/vue3` to v3, and confirm
every v3 breaking change either doesn't apply to this codebase (per
planning-time audit) or is fixed. This is the least-audited phase in the
plan — re-verify against the live v3 changelog at implementation time, since
Inertia v3 moved from beta to stable in the same window this plan was
written, and planning-time web research may already be outdated.

## First Step (before writing any code)

Re-fetch https://inertiajs.com/docs/v3/getting-started/upgrade-guide fresh
(don't rely solely on the summarized findings in `00-overview.md`) — confirm
the exact Vite version requirement, since planning-time research found
"supports Vite 8, drops Vite 6" but did not confirm where Vite 7 (currently
installed, `vite: ^7.0.4` in `package.json`) stands.

## Steps

1. Bump `composer.json`: `"inertiajs/inertia-laravel": "^2.0"` → `"^3.0"`,
   `composer update inertiajs/inertia-laravel`.
2. Bump `package.json`: `"@inertiajs/vue3": "^2.1.7"` → `"^3.0"` (or
   whatever version the fresh docs check confirms), `npm install`.
3. If the Vite check from the First Step requires it, bump `"vite":
   "^7.0.4"` in `package.json` to the required floor and re-run `npm
   install`. Confirm `vite.config.js`'s `@` / `@images` aliases
   (`vite.config.js` per `CLAUDE.md`) still resolve after the bump.
4. Clear cached views: `php artisan view:clear` — the `@inertia` Blade
   directive's output changed in v3.
5. Grep the codebase again for `Inertia::lazy(` and router event listeners
   (`router.on('invalid'`, `router.on('exception'`, `onInvalid`,
   `onException`) — planning found zero matches, but confirm again since
   this phase's dependency bump may reveal call sites planning missed (e.g.
   inside a Blade/Vue file pattern the earlier grep didn't cover).
6. Axios: planning confirmed `axios: ^1.7.4` is already a direct
   `package.json` dependency (not solely bundled via Inertia), and it's
   imported directly in `resources/js/app.js`,
   `Components/FrontEnd/Contact.vue`, `Components/FrontEnd/RepairRequestForm.vue`,
   `Pages/Sam/Favorites.vue`, `Pages/Store/Checkout.vue`,
   `Pages/Store/CheckoutPay.vue`, `Pages/Store/StoreIndex.vue`. No action
   expected — confirm none of these actually relied on Inertia
   re-exporting/configuring a shared Axios instance rather than importing
   their own.
7. Run `npm run build` and confirm it completes without errors.
8. Manual browser smoke test (start `composer run dev`):
   - Storefront: `/`, `/store`, a product detail page, add-to-cart flow
   - Auth: login, register (if applicable), logout
   - Dashboard (`resources/js/Pages/Dashboard.vue`)
   - CRM-adjacent customer-facing pages if any exist under Inertia (not
     Filament)
   - Confirm no console errors related to Inertia routing, page props, or
     the `@inertia`/`<x-inertia::app>` directive output.

## Files to Create/Modify

- `composer.json`, `composer.lock`
- `package.json`, `package-lock.json`
- `vite.config.js` (only if the Vite version bump requires config changes)
- Any Blade view directly referencing the old `@inertia`/`@inertiaHead`
  directives if migrating to the new `<x-inertia::app>`/`<x-inertia::head>`
  components is chosen (optional per the guide — directives still work)

## Tests

- `php artisan test` — Inertia-related feature tests (Dashboard, Store,
  Auth) must still pass.
- Manual browser verification per Step 8 is required and not satisfied by
  automated tests alone — a broken Inertia page frequently renders a blank
  screen or console error without a server-side test failure.

## Success Criteria / Definition of Done

- `composer show inertiajs/inertia-laravel` and `npm list @inertiajs/vue3`
  both confirm v3.
- `npm run build` succeeds.
- Manual smoke test of Storefront, Auth, Dashboard pages shows no console
  errors and normal navigation/interactivity.
- No regressions in the Inertia-related automated test suite.

**STOP. Await phase approval.**

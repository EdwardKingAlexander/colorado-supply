# Customer Portal UI Rebrand — Overview

> Revised 2026-10-23. The July draft predated `store-home-redesign` (October),
> which gave the store its own branded shell (`StoreLayout.vue` + `st-*`
> tokens). The user approved the plan and, in the same session, chose to put
> the account pages on that shared store shell and to add a theme toggle.
> The phases below reflect those answers.

## Problem Statement

When a normal (web-guard) customer signs in, they land on `/dashboard`
(`DashboardController` → Inertia `Dashboard.vue`), wrapped in
`resources/js/Layouts/AuthenticatedLayout.vue`. That layout is stock Laravel
Breeze: the literal Laravel SVG in `ApplicationLogo.vue`, `bg-gray-100`,
indigo focus rings, no dark styles. The user reported that the dashboard
"looks like a default Laravel dashboard."

Since July, the store has been redesigned: `StoreLayout.vue` has the real
logo, catalog search, cart, account menu, notification bell, and a token
system (`.store-shell` → `--st-*` in `resources/css/app.css`) with light and
dark values. The site root is forced dark (`<html class="dark">` in
`resources/views/app.blade.php`), so the store renders in "steel night" while
the dashboard, which has no `dark:` styles, renders as white Breeze. Moving
between the two feels like switching applications.

## Approved Decisions

From the user, 2026-07-10 (still valid):

1. Blue primary (brand `#1D4ED8`); amber/safety yellow as a secondary signal;
   red for alerts and destructive actions.
2. Scope: every signed-in customer page, plus dead-code cleanup.
3. Dark mode supported.
4. Filament admin is out of scope.

From the user, 2026-10-23 (replace the July nav decision):

5. **Shared shell.** Signed-in account pages use the store header (logo,
   catalog search, cart, account menu, bell) plus an account sub-nav:
   Overview, Reports, Notifications, Company, Profile. This is the
   Amazon Business / Grainger / McMaster-Carr pattern: one shell for the whole
   signed-in experience.
6. **Theme: dark default + toggle.** Dark stays the default so nothing
   changes for anyone today. A Light / Dark / System switch in the account
   menu is remembered per browser (`localStorage`). Public marketing and auth
   pages stay forced dark.

## Constraints

- Filament admin untouched (`app/Filament/**`, `resources/views/filament/**`).
- Reuse the existing `st-*` token system and the store's type voice
  (Archivo, `.st-display`, `.st-num`). Do not add a parallel color system.
- Shared Breeze primitives (`PrimaryButton`, `TextInput`, …) are also used
  by the auth pages, which sit on light cards inside a forced-dark document.
  Primitives therefore must **not** gain plain `dark:` variants. Brand-align
  them (indigo → brand blue), and adapt them inside the shell with the scoped
  arbitrary variant `[.store-shell_&]:…`.
- Preserve behavior: every nav destination, bell, toasts, cookie banner,
  admin-panel link for admin-guard users, `min-h-11/12` touch targets,
  `safe-*` insets, focus rings, `aria-*`.
- Presentation only. No prop or data-shape changes from controllers, except
  the null-company guard on `CompanyController` (it 500s today when a user
  has no company, and the new Company tab makes that reachable).
- CSP: any inline script carries `$cspNonce`.

## Architecture (textual)

```
app.blade.php
  <html class="dark" data-portal-theme?>   (attribute set server-side for portal pages)
  inline nonce'd script: if data-portal-theme, read localStorage 'cs-theme'
  (light|dark|system) and set/remove .dark before first paint
        │
resources/js/Support/theme.js  ── preference get/set, applyPortalTheme(),
        │                         forceSiteDark(), system-change listener
        ▼
StoreLayout.vue (shared shell)  ── header + ThemeSwitcher in account menu
        │                          + slim footer; calls applyPortalTheme()
        ├── store pages (catalog, product, cart, checkout, success/cancel)
        └── AccountLayout.vue ── account title row + AccountNav tabs
                 ├── Dashboard, Dashboard/Reports
                 ├── Notifications/Index
                 ├── Company/Index
                 ├── Profile/Edit
                 └── Sam/Favorites (wrapped; not added to nav)
AppLayout / BrandedAuthLayout / GuestLayout ── call forceSiteDark() on mount
```

`App\Support\PortalTheme::appliesTo($component)` decides server-side which
Inertia components get the `data-portal-theme` attribute, so a light-mode
user never sees a dark flash on first load.

## Risks

- **Theme flash / wrong theme after client navigation** between a public page
  (forced dark) and a portal page. Mitigation: server attribute for first
  paint; every layout re-applies its rule on mount.
- **Primitives used on auth pages.** Mitigation: no plain `dark:` variants in
  primitives; screenshot login/register in Phase 6.
- **Teleported/`<dialog>` content** (`Modal.vue`, headless UI `Dialog`)
  renders outside `.store-shell`, so `st-*` vars are undefined there.
  Mitigation: dialog panels add the `store-shell` class themselves (the
  pattern `StoreLayout`'s mobile drawer already uses).
- **Existing tests** assert markup (Playwright `tests/Browser/*.spec.ts`,
  Inertia component assertions). Mitigation: keep IDs/aria labels; run
  the full suite. Baseline: full suite green on 2026-10-10 (980 pass), so any
  failure is a regression.
- **Dead-code removal (Phase 5)** must be verified per file.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Theme foundation & shared primitives | `phase-1-foundation-primitives.md` |
| 2 | Shared shell: account layout, sub-nav, theme switch, footer | `phase-2-authenticated-chrome.md` |
| 3 | Dashboard & dashboard widgets | `phase-3-dashboard.md` |
| 4 | Remaining customer pages | `phase-4-remaining-pages.md` |
| 5 | Dead-code cleanup | `phase-5-cleanup.md` |
| 6 | Visual QA, build & test | `phase-6-verification.md` |

Status tracking lives in `STATE.md`.

# Customer Portal UI Rebrand — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-23 | User approved; revised same day per user's shell/theme answers |
| 1 | Theme foundation & shared primitives | Complete | 2026-10-23 | Theme head script, `theme.js`, brand logo, primitives |
| 2 | Shared shell: account layout, sub-nav, theme switch, footer | Complete | 2026-10-23 | `AccountLayout`, `AccountNav`, `ThemeSwitcher`, StoreLayout footer |
| 3 | Dashboard & dashboard widgets | Complete | 2026-10-23 | Ledger KPI strip, validated bar colors, single empty state |
| 4 | Remaining customer pages | Complete | 2026-10-23 | Profile, Company (+ no-company fix), Notifications, SAM favorites, checkout success/cancel |
| 5 | Dead-code cleanup | Complete | 2026-10-23 | 40 files removed (staged with git rm, not committed) |
| 6 | Visual QA, build & test | Complete | 2026-10-23 | Pest 1201 pass; new browser spec 9/9; screenshots in qa/ |
| 7 | Account URLs under /account | Complete | 2026-10-23 | Requested by user; 301 redirects from old addresses |

## Key Decisions

July 2026 (still valid): blue primary, amber/yellow as secondary signal,
red for alerts; all customer pages in scope; dark mode supported; Filament
admin out of scope.

2026-10-23 (user, replaces the July "separate top nav" decision):
1. Signed-in account pages share the store header (`StoreLayout`) plus an
   account sub-nav (Overview, Reports, Notifications, Company, Profile).
2. Theme: dark default + Light / Dark / System toggle, remembered per browser.
   Public marketing/auth pages stay forced dark.

## Log

- 2026-07-10 — Investigated the Breeze `AuthenticatedLayout`, drafted the
  6-phase plan, collected 4 scope decisions. Plan → Awaiting Plan Approval.
- 2026-10-23 — User approved the plan. Re-checked scope against current code:
  - `store-home-redesign` (Oct) added `StoreLayout.vue` and `st-*` tokens;
    catalog/product/cart/checkout/pay and Notifications already use it.
  - `<html class="dark">` is forced site-wide, so the store renders dark and
    the dashboard (no `dark:` styles) renders as white Breeze.
  - `Store/Public.vue` and `Store/Authenticated.vue` have no render call (dead).
  - `CheckoutSuccess`/`CheckoutCancel` still use the public `AppLayout`.
  - `Sam/Favorites.vue` renders with no layout.
  - `CompanyController` 500s for a user without a company.
  - Asked the user two questions (shell, theme); revised all plan files.
- 2026-10-23 — Phase 1 implemented:
  - `app/Support/PortalTheme.php` decides which Inertia components follow the
    preference; `app.blade.php` adds `data-portal-theme` and a nonce'd head
    script that resolves `localStorage['cs-theme']` (default dark) before
    first paint, and sets `theme-color`.
  - `resources/js/Support/theme.js`: `applyPortalTheme` (StoreLayout mount),
    `forceSiteDark` (AppLayout/GuestLayout/BrandedAuthLayout mount),
    `setThemePreference`, system-change listener, storage try/catch.
  - `ApplicationLogo.vue` now renders the brand logo (`surface` prop
    auto/light/dark); GuestLayout pins `surface="light"` (light card page).
  - Primitives: indigo → brand blue, gray-800 primary → `primary-700`,
    danger → `accent`, `focus-visible` rings, `[.store-shell_&]:` variants for
    shell context. No plain `dark:` variants (auth pages are light cards in a
    dark document). `Modal.vue` needed no `shell` prop: its `<dialog>` stays
    in the DOM tree, so shell variants and `--st-*` vars apply.
  - Tests: `tests/Unit/PortalThemeTest.php` (18 cases) and a DashboardTest
    case asserting the attribute on `/dashboard` and not on `/`. Pass.
- 2026-10-23 — Phase 2 implemented:
  - `Components/ThemeSwitcher.vue` (native radio segmented control, drawer)
    and Light/Dark/System items in the desktop account menu (MenuItems, since
    Headless UI's Menu swallows Space/arrow keys inside radios).
  - `StoreLayout.vue`: "Dashboard" → "Account" with active state; menu and
    drawer gain Account overview, Notifications, Company (only with
    `auth.user.company_id`); slim footer (copyright, Main site, Text message
    terms; cookie choices already have a floating button); flex column so the
    footer sits at the bottom.
  - `Layouts/AccountLayout.vue` (title/description/actions, `narrow`) and
    `Components/Account/AccountNav.vue` (Overview, Reports, Notifications with
    unread badge, Company, Profile; `aria-current`).
- 2026-10-23 — Phase 3 implemented: shared `.st-panel`, `.st-btn-primary`,
  `.st-btn-secondary`, `.st-field`, `.st-table` classes in `app.css`; shell
  colors for the mobile `.responsive-data-table` cards. Dashboard: sentence
  case title row with the date range in words, ledger KPI strip (total spend
  as the one large figure), bars `primary-700` light / `primary-500` dark
  (both pass the dataviz validator against the panel surfaces; `#6EA0FF`
  failed the dark lightness band), status shown as dot + text. With zero
  orders the page shows one empty state beside the account card instead of
  four empty charts. Reports page and its form/table on the same system.
- 2026-10-23 — Phase 4 implemented: Profile on `AccountLayout` (`narrow`),
  partial colors on `st-*`, sentence-case headings; Company page with
  leader-dot location list and a no-company empty state.
  `CompanyController` no longer 500s without a company
  (`tests/Feature/Company/CompanyPageTest.php`). Notifications moved from
  `StoreLayout` to `AccountLayout`. `Sam/Favorites.vue` wrapped in
  `AccountLayout` with a token pass (not added to nav). Checkout
  success/cancel moved from the public `AppLayout` to `StoreLayout`; cancel
  now offers "Try payment again" (the cart is cleared once the order exists,
  so "return to cart" would land on an empty cart).
  - Found while doing this: `.store-shell .text-red-700` forced a pale red
    meant for dark mode; scoped it to `.dark .store-shell` now that light
    mode exists.
- 2026-10-23 — Phase 5: verified each candidate with grep across `app routes
  resources tests config bootstrap` before removal. Removed (git rm, staged,
  not committed): `AuthenticatedLayout.vue`, old `Components/NotificationBell.vue`,
  `NavLink`, `ResponsiveNavLink`, `Dropdown`, `DropdownLink`,
  `Pages/Store/Public.vue`, `Pages/Store/Authenticated.vue` (no render call),
  `views/dashboard.blade.php`, `views/components/layouts/**`, `app-logo*`,
  `placeholder-pattern`, `settings/layout`, `auth-header`,
  `auth-session-status`, `action-message`, `views/livewire/**` (Volt auth and
  settings components: no route, no embed), `views/flux/**`,
  `partials/settings-heading.blade.php`, `app/Livewire/Actions/Logout.php`
  (only used by the deleted Volt views). Kept `components/terminal-page`
  (used by Filament pages). Removed the two Volt-based delete-account tests
  in `tests/Feature/Settings/ProfileUpdateTest.php`; the live
  `DELETE /profile` route is covered by `tests/Feature/ProfileTest.php`.
  - Follow-ups (not done): `config/livewire.php` `layout` still names
    `components.layouts.app` (only used by full-page Livewire components,
    none exist outside Filament); `VoltServiceProvider` and the
    `livewire/volt` / Flux packages could be uninstalled.
- 2026-10-23 — Phase 6 verification:
  - `php artisan test --parallel`: 1201 passed, 5 skipped.
  - `npm run build` clean; Pint passed on edited PHP files.
  - New `tests/Browser/customer-portal.spec.ts` + fixture: 9/9 pass (shell +
    logo on every account page, `aria-current`, theme applies/persists and
    public pages stay dark, axe serious/critical clean in light and dark,
    no sideways scroll at 390px in both themes, drawer theme switch).
  - Screenshots reviewed: `qa/desktop-{light,dark}-{dashboard,profile,company}.png`,
    `qa/mobile-{light,dark}-dashboard.png`, `qa/login-check.png`.
  - Existing browser specs: run against the Vite dev server (`public/hot`
    present) they fail at login because hydration is slow and the cookie
    banner appears after the specs' `isVisible()` check. Against built
    assets (hot file set aside): 46/48 pass. The 2 failures:
    `product-page` large-430 passed when rerun alone (load flake);
    `will-call` order flow times out waiting for the admin's Reverb socket
    (`wss://colorado-supply.test:8080`, Herd self-signed cert, spec has no
    `ignoreHTTPSErrors`). Admin/Reverb are untouched by this module.
  - Status: all phases `Awaiting Validation`. Nothing committed.
- 2026-10-23 — Phase 7 (user request: why no user email/ID in the URL, why
  no shared prefix). Explained that session-scoped account URLs are the
  convention (privacy, edit-the-URL access risk, email changes); user chose
  an `/account` prefix with redirects. Moved the routes (names unchanged),
  added query-preserving 301s from every old GET address, updated the
  `AppNotification` fallback and `Navigation.vue` fallback, rewrote literal
  paths in tests, added `tests/Feature/AccountUrlRedirectTest.php`.
  - Full suite (parallel): 1471 passed, 5 skipped, 1 failed
    (`FetchSamOpportunitiesJobTest` partial-success case; passes alone, a
    known flaky SAM test). One earlier parallel run showed 737 transient
    failures that did not reproduce; the suite had grown to 1477 tests,
    likely from other uncommitted work in the tree.
  - `customer-portal.spec.ts` 9/9 on the new URLs (built assets).
  - Not rebuilt: the SSR bundle (`npm run ssr-build`, `bootstrap/ssr`).
- 2026-10-23 — User approved ("Love it. Mark it complete"). All phases
  Complete; module renamed to `customer-portal-ui-rebrand-complete`. Not yet
  committed. Before deploying, run `npm run ssr-build`.

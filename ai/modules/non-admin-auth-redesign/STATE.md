# Non-Admin Auth Redesign — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-07-10 | User approved by requesting Phase 1 start |
| 1 | Auth design system and shared shell | Complete | 2026-07-10 | User approved Phase 1 validation |
| 2 | Login and register page redesign | Awaiting Validation | 2026-07-10 | Login/register moved onto branded shell; awaiting review |
| 3 | Testing, build, and visual verification | Not Started | 2026-07-10 | Awaiting Phase 2 completion/approval |

## Log

- 2026-07-10 — Plan drafted after inspecting current Breeze-style
  `Login.vue`, `Register.vue`, `GuestLayout.vue`, homepage `Welcome.vue`,
  public `Navigation.vue`, `Hero.vue`, and existing auth tests. Scope is
  limited to public/non-admin login and register page presentation; no auth
  backend behavior, Filament admin login, or password reset flow changes are
  included. Awaiting overall plan approval before Phase 1 begins.
- 2026-07-10 — User approved Phase 1. Set module plan to `Complete` and
  Phase 1 to `In Progress`.
- 2026-07-10 — Phase 1 implemented:
  - Added `resources/js/Layouts/BrandedAuthLayout.vue`, a public-auth shell
    using the existing Colorado Supply logo, optimized pipeline imagery,
    dark/navy brand surfaces, amber accent text, a compact header, a desktop
    visual panel, and a mobile-friendly form area.
  - The layout accepts `title`, `subtitle`, `eyebrow`,
    `secondaryActionLabel`, and `secondaryActionHref` props and exposes a
    default slot for auth form content.
  - Left `GuestLayout.vue`, `Login.vue`, and `Register.vue` unchanged as
    required by Phase 1; those pages will move onto the new shell in Phase 2.
  - Verified `BrandedAuthLayout.vue` parses and compiles with
    `@vue/compiler-sfc`.
  - `npm run build` passed.
  - `vendor/bin/pint --dirty` passed.
  - Status set to `Awaiting Validation`. Awaiting approval before Phase 2
    begins.
- 2026-07-10 — User approved Phase 1. Marked Phase 1 `Complete` and set
  Phase 2 to `Awaiting Phase Approval`. Phase 2 scope remains accurate:
  move `Login.vue` and `Register.vue` onto `BrandedAuthLayout.vue`, preserve
  all existing auth form behavior, and leave backend auth/Filament admin
  login/password reset flows out of scope.
- 2026-07-10 — User approved Phase 2 by asking to move forward. Set Phase 2
  to `In Progress`.
- 2026-07-10 — Phase 2 implemented:
  - `resources/js/Pages/Auth/Login.vue` now uses
    `BrandedAuthLayout.vue`, branded procurement/account copy, amber-focused
    field/link states, a secondary create-account link, and the existing
    forgot-password/remember-me behavior. Existing `useForm` fields and
    `form.post(route('login'))` behavior are unchanged.
  - `resources/js/Pages/Auth/Register.vue` now uses
    `BrandedAuthLayout.vue`, branded account setup copy, amber-focused
    field/link states, and a login link. Existing `useForm` fields and
    `form.post(route('register'))` behavior are unchanged.
  - Left password reset, forgot password, confirm password, backend auth
    controllers, and Filament admin login out of scope.
  - Verified `/login` and `/register` return 200 locally and include an
    Inertia `data-page` payload plus branded Colorado Supply text.
  - `php artisan test tests/Feature/Auth/AuthenticationTest.php
    tests/Feature/Auth/RegistrationTest.php` passed (8 tests, 18
    assertions).
  - `npm run build` passed.
  - `npm run ssr-build` passed.
  - `vendor/bin/pint --dirty` passed, but formatted two unrelated files that
    were already dirty before this phase:
    `app/Filament/Pages/McpDashboard.php` and `bootstrap/providers.php`.
    Review those separately from the auth redesign.
  - Status set to `Awaiting Validation`. Awaiting approval before Phase 3.

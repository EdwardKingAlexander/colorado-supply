# Laravel 12 → 13 Upgrade — Overview

## Problem Statement

The app runs Laravel 12.x (`laravel/framework: ^12.0`). Laravel 13.x is out
(latest stable `v13.19.0`, requires PHP `^8.3`) and the official guide
(https://laravel.com/docs/13.x/upgrade) estimates "10 minutes" — but that
estimate only covers the framework's own breaking changes. Live dependency
research during planning found the upgrade also pulls in two collateral
major-version bumps for packages this app depends on directly:

- `inertiajs/inertia-laravel` v2 → v3 is available and needed for Laravel 13
  alignment — this touches the entire Vue/Inertia customer-facing frontend
  (Blade directive output changes, `Inertia::lazy()` → `Inertia::optional()`,
  router event renames, Axios no longer bundled).
- `spatie/laravel-activitylog` has a v5 with breaking changes (DB schema
  change, `CauserResolver` removed in favor of `Activity::defaultCauser()`)
  that would directly collide with `app/Support/ActivitylogCauserResolver.php`
  from the in-progress `audit-logging` module (see `[[project_gov_b2b_readiness]]`
  memory). **Decision: pin `spatie/laravel-activitylog` to `^4.0` for this
  upgrade** — the latest 4.x release (4.12.3) already supports Laravel 13
  (`illuminate/support: ...||^13.0`), so no forced v5 rewrite is needed. The
  v5 migration is a separate, deliberate future decision, not scope creep
  here.
- `spatie/laravel-permission` is currently unpinned (`"*"` in
  `composer.json`) and locked at `6.24.0`, but the latest available is
  `8.3.0` — a 2-major jump a plain `composer update` would silently take.
  **Decision: pin to `^6.24` for this upgrade** for the same reason —
  isolate the framework upgrade from an unplanned RBAC package rewrite
  (this app's guards/policies/roles are core to multi-tenancy and admin
  access, not a place to absorb surprise breaking changes alongside a
  framework bump).

Both pinning decisions were confirmed with the user before this plan was
written.

## Research Findings (composer/packagist, verified live during planning)

| Package | Current constraint | Latest available | Laravel 13 support | Plan |
|---|---|---|---|---|
| `laravel/framework` | `^12.0` | `v13.19.0` | — | Bump to `^13.0` |
| `php` | `^8.2` | — | Laravel 13 needs `^8.3` | Bump to `^8.3` |
| `laravel/tinker` | `^2.10.1` | v3.x | required by guide | Bump to `^3.0` |
| `laravel/boost` | `^1.6` | `v2.4.12` | required by guide for `/upgrade-laravel-v13` | Bump to `^2.0` |
| `pestphp/pest` | `^3.8` | `v4.7.5` | required by guide | Bump to `^4.0` |
| `filament/filament` | `^4.1` | `v4.11.8` | **confirmed** — `filament/support` requires `illuminate/contracts: ^11.28|^12.0|^13.0`, `livewire/livewire: ^3.5` (no forced Livewire 4) | No constraint change needed; verify resolution |
| `inertiajs/inertia-laravel` | `^2.0` | `v3.1.1` | v3 targets Laravel 11+ | Bump to `^3.0` — own phase (Phase 3) |
| `@inertiajs/vue3` (npm) | `^2.1.7` | v3.x | must match PHP adapter | Bump to `^3.0` in Phase 3 |
| `laravel/sanctum` | `^4.0` | `v4.3.2` | confirmed (`^11.0|^12.0|^13.0`) | No constraint change needed |
| `spatie/laravel-backup` | `^10.3` | `10.3.0` | confirmed (`^12.40|^13.0`) | No constraint change needed |
| `spatie/laravel-activitylog` | `^4.0` | 5.x available | v4.12.3 confirmed on `^13.0`; v5 requires PHP 8.4+/Laravel 12+ and breaks `CauserResolver` | **Pin `^4.0`, stay on 4.x** |
| `spatie/laravel-permission` | `*` (locked `6.24.0`) | `8.3.0` | v8 requires `^12.0|^13.0` | **Pin `^6.24`** |
| `sentry/sentry-laravel` | `^4.26` | `4.26.0` | confirmed (`^6.0`...`^13.0`) | No constraint change needed |
| `laravel/mcp` | `^0.2.1` | 0.8.x | confirmed on 13.0 per Laravel's own MCP docs | Verify resolves; bump floor if composer requires |
| `maatwebsite/excel` | `^3.1` | `3.1.69` | confirmed (`...||^13.0`) | No constraint change needed |
| `tightenco/ziggy` | `^2.0` | `v2.6.3` | confirmed (`>=9.0`) | No constraint change needed |
| `vite` (npm) | `^7.0.4` | — | Inertia v3 docs mention Vite 8 support / Vite 6 dropped; Vite 7 status not confirmed | Verify explicitly in Phase 3, bump if required |

Packages not individually verified during planning (`barryvdh/laravel-dompdf`,
`chrome-php/chrome`, `stripe/stripe-php`, `smalot/pdfparser`,
`phpoffice/phpword`, `thiagoalessio/tesseract_ocr`, `symfony/http-client`,
`symfony/mailgun-mailer`, `laravel/breeze`, `laravel/pail`, `laravel/sail`)
are lower-risk, widely-maintained packages — Phase 1's `composer update
--dry-run` will surface any real conflicts rather than guessing at all ~25
dependencies up front.

## Codebase Impact Audit (grep-verified during planning)

- **CSRF middleware rename (High impact, confirmed direct hit)**:
  `bootstrap/app.php` imports and references `VerifyCsrfToken::class` twice
  (line 8 import, line 19 `->withoutMiddleware(VerifyCsrfToken::class)` for
  the `routes/ai.php` MCP group). Must become `PreventRequestForgery`.
- **Cache `serializable_classes` (Medium impact)**: only cache-writing call
  site found is `app/Support/SamOpportunitiesCache.php`, which caches plain
  arrays only (no objects) — new default-`false` setting requires no
  application change, just confirmation.
- **Database `upsert()` / MySQL `DELETE ... JOIN` (Medium/Low impact)**: no
  `->upsert(` calls and no `DELETE ... JOIN` query patterns found anywhere
  in `app/`. No action expected; Phase 5's full test run is the backstop.
- **Model `boot()` nested instantiation (Very Low impact)**: five
  `booted()`/`boot()` overrides found (`Location`, `Quote`, `Product`,
  `OrderItem`, `Order`) — none instantiate `new static()` inside the boot
  method itself (they only register scopes/event listeners). No action
  expected.
- **`Manager::extend()` pattern (Low impact)**:
  `app/Services/DocumentParsing/DocumentParserManager.php` is the only
  custom manager-shaped class; it does not use Laravel's `extend()`
  driver-registration pattern. No action expected.
- **Pagination Bootstrap view names, `Js::from`, custom `array_first()`/
  `array_last()` helpers, `Str` factories in tests**: no matches found
  anywhere in the app. No action expected.
- **Audit-logging collision**: `app/Support/ActivitylogCauserResolver.php`
  and `app/Providers/AppServiceProvider.php` both reference
  `CauserResolver` — this is why activitylog is pinned to `^4.0` (see
  above), not upgraded to v5 as part of this plan.

## Constraints

- Production runs on Laravel Forge, confirmed already on PHP ^8.3+ (user
  confirmed) — the PHP floor bump is **not** a deployment blocker.
- `spatie/laravel-activitylog` and `spatie/laravel-permission` are pinned to
  their current majors for this upgrade (user-confirmed decision above).
  Do not let `composer update` silently take them past their pinned range.
- This upgrade must not be bundled with unrelated feature work. The three
  in-progress gov/B2B-readiness modules (`audit-logging`,
  `admin-account-security-complete`, `production-observability`) continue
  independently; this plan only needs to avoid breaking their already-shipped
  code (verified above: no `spatie/laravel-activitylog` v5 collision since
  we're pinning to v4).
- Local dev runs on Laravel Herd (SQLite locally, per `.env` `DB_CONNECTION`).
  Confirm production's actual DB driver (MySQL/MariaDB via Forge is the
  working assumption) before treating the MySQL `DELETE ... JOIN` change as
  fully moot — Phase 1 should confirm this rather than assume.
- Filament v4, Livewire 3, and Volt/Flux are **not** being bumped to their
  newer major lines (Filament v5, Livewire 4) as part of this plan — research
  confirmed Filament 4.11.8 (which `^4.1` already resolves to) supports
  Laravel 13 without requiring those jumps. A future Filament v5 / Livewire 4
  migration is out of scope here.

## Architecture (textual)

```
Phase 1 — Preflight audit
  -> confirm production DB driver
  -> pin spatie/laravel-permission (^6.24) and spatie/laravel-activitylog (^4.0)
     in composer.json BEFORE touching laravel/framework, so the dry run
     reflects the real target state
  -> composer update --dry-run with laravel/framework:^13.0 etc.,
     capture and read the full conflict list

Phase 2 — Core framework + first-party package upgrade
  -> bump php, laravel/framework, laravel/tinker, laravel/boost,
     pestphp/pest (+ pest-plugin-laravel), phpunit/phpunit if present
  -> composer update, resolve any real conflicts Phase 1 didn't catch
  -> apply High/Medium/Low impact code changes from the official guide
     (VerifyCsrfToken -> PreventRequestForgery; verify the rest are no-ops
     per the codebase audit above)
  -> full test suite pass, pint clean

Phase 3 — Inertia.js v2 -> v3 migration
  -> bump inertiajs/inertia-laravel (composer) and @inertiajs/vue3 (npm)
  -> verify/bump Vite to whatever v3 actually requires
  -> clear cached views, verify Blade directive output
  -> confirm no Inertia::lazy()/router event-name usage broke (audit found
     none, but re-check after the bump since transitive behavior may differ)
  -> manual browser smoke test of Store, Auth, Dashboard, CRM pages

Phase 4 — Filament/Livewire verification (no version bump expected)
  -> composer update, confirm filament/filament, livewire/livewire,
     livewire/volt, livewire/flux resolve to compatible versions without
     jumping to Filament v5 / Livewire 4
  -> manual browser verification of admin panel: MFA login, key resources,
     relation managers, actions

Phase 5 — Remaining package sweep + full test suite
  -> resolve any remaining composer packages not pre-verified in planning
  -> full php artisan test, vendor/bin/pint --dirty, npm run build
  -> manual smoke test of storefront/checkout/quote flows

Phase 6 — Deployment readiness
  -> confirm Forge PHP version in production matches composer.json's new
     floor (already confirmed >= 8.3, re-verify at deploy time)
  -> staging deploy + smoke test
  -> rollback plan (git tag pre-upgrade, documented revert steps)
```

## Risks

- **Inertia v3's frontend-side breaking changes are the least-audited area**
  — the guide's own release notes are still evolving; Phase 3 must
  re-verify against the *actual* installed `@inertiajs/vue3` v3 changelog
  at implementation time, not just this planning-time research, since Inertia
  v3 shipped from beta to stable within the same window as this plan.
- **Vite version requirement for Inertia v3 is unconfirmed** — research
  found "supports Vite 8, drops Vite 6" but didn't confirm Vite 7 (currently
  installed) status. Phase 3 must confirm this precisely before assuming
  the current `vite: ^7.0.4` is sufficient.
- **Filament 4.11.8 was published 2026-07-02, one week before this plan** —
  freshly-added Laravel 13 support in a fast-moving package carries more
  regression risk than a change that's been stable for months. Phase 4's
  manual admin-panel verification is not optional.
- **Production DB driver is assumed, not confirmed** — if production is not
  MySQL/MariaDB, the "no `DELETE...JOIN` found" finding is still valid (no
  such queries exist regardless of driver) but the constraint about
  confirming driver in Phase 1 stands for completeness.
- **spatie/laravel-permission and spatie/laravel-activitylog pins must
  survive `composer update`** — a bare `composer update` with no explicit
  package list argument could still touch them if the pin isn't written
  into `composer.json` correctly before running it. Phase 1 must verify the
  pin holds after every `composer update` in this plan, not just set it once.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Preflight audit & dependency pinning | `phase-1-preflight-audit.md` |
| 2 | Core framework + first-party package upgrade | `phase-2-core-framework-upgrade.md` |
| 3 | Inertia.js v2 → v3 migration | `phase-3-inertia-v3-migration.md` |
| 4 | Filament/Livewire verification | `phase-4-filament-livewire-verification.md` |
| 5 | Remaining package sweep + full test suite | `phase-5-package-sweep-test-suite.md` |
| 6 | Deployment readiness | `phase-6-deployment-readiness.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per `PROJECT_CANNON.md`.

**STOP. Await approval before any implementation begins.**

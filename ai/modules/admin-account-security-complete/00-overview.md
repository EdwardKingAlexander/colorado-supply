# Admin Account Security (MFA + Security Headers) — Overview

## Problem Statement

Two standard items on nearly every enterprise/government vendor security
questionnaire don't exist in this app today: multi-factor authentication for
privileged accounts, and baseline HTTP security headers. Both are checked
directly:

```
grep -rn "TwoFactor\|two_factor\|MFA" app/     -> no results
grep -rn "SecureHeaders\|Content-Security-Policy\|Strict-Transport" app/ -> no results
```

We're adding both. Scope is **admin/staff accounts** (the Filament panel,
`admin` guard) — not the customer-facing storefront login, since the
questions security reviewers ask are almost always about who can reach
privileged/administrative functions, and Filament v4 has this built in
already (confirmed via research: native `multiFactorAuthentication()` panel
method, two built-in methods — TOTP app-based and email-based one-time
codes, no third-party plugin needed).

## Constraints

- Installed Filament version is **v4.3.1**. The native MFA feature is
  confirmed to exist in v4, but some documented conveniences (e.g., default
  traits for app-authentication columns) were called out as "new in v4.5"
  during research — **Phase 1 must confirm exactly what's available at
  4.3.1 by reading the installed package's own source/docs before writing
  code**, and only propose a Filament version bump if the 4.3.1 API
  genuinely lacks something Phase 1 needs (don't upgrade Filament
  speculatively).
- MFA should be **required** for the `admin` guard (super_admin, admin,
  sales_manager roles at minimum — decide exact role scope in Phase 1
  based on what `RolesAndPermissionsSeeder` defines), not merely available
  and skippable, since an optional control that nobody turns on doesn't
  satisfy a security review.
- Security headers must not break the existing Inertia/Vue frontend or the
  Filament panel (Filament ships its own inline styles/scripts — a
  restrictive `Content-Security-Policy` written naively will break the
  admin panel; this needs testing against both surfaces, not just added and
  assumed to work).
- HTTPS enforcement (`URL::forceScheme('https')` or equivalent) should be
  environment-gated (`APP_ENV === 'production'`) so local dev over HTTP
  (this project runs on Laravel Herd locally) keeps working.

## Architecture (textual)

```
Admin Panel (Filament, /admin):
  AdminPanelProvider::panel()
    -> ->multiFactorAuthentication([...], isRequired: true)
    -> user enrolls TOTP or email-OTP from their profile page on first
       login after this ships; subsequent logins prompt for the code

All responses (web + admin + api):
  New SecurityHeaders middleware (global, in bootstrap/app.php)
    -> adds Content-Security-Policy, X-Frame-Options, X-Content-Type-Options,
       Referrer-Policy, Permissions-Policy
    -> adds Strict-Transport-Security only when the request is already HTTPS
       (never on local HTTP dev)

Production only:
  AppServiceProvider::boot() (or a dedicated provider)
    -> URL::forceScheme('https') when app()->environment('production')
```

## Risks

- **CSP breaking the Filament panel or Vite-served Vue app** — the single
  highest-risk item. Filament and Vite both need specific CSP allowances
  (inline styles for Filament, `'unsafe-inline'` or nonces for Vite's
  dev-server HMR in local dev vs. hashed/nonced production bundles). Phase
  2 must manually verify both surfaces still render and function, not just
  pass automated tests (a CSP violation often only shows up as a silently
  broken UI, not a server error).
- **MFA lockout** — if an admin loses their TOTP device with no recovery
  path, they're locked out of the only admin panel. Confirm Filament's
  native recovery-codes support (mentioned as a v4.5 addition in research)
  is available at the installed 4.3.1, or plan a manual admin-recovery
  procedure (e.g., a `php artisan` command to reset a user's MFA
  enrollment) as a fallback — this must exist before `isRequired: true`
  ships, not after the first support ticket.
- **Forcing HTTPS in an environment that isn't actually behind TLS
  termination yet** — if production isn't already served over HTTPS,
  `forceScheme('https')` would break it outright. Confirm production's
  actual TLS setup before Phase 3.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Filament native MFA for the admin panel | `phase-1-mfa.md` |
| 2 | Security headers middleware | `phase-2-security-headers.md` |
| 3 | HTTPS enforcement (production-gated) | `phase-3-https-enforcement.md` |
| 4 | Testing & hardening | `phase-4-testing-hardening.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per `PROJECT_CANNON.md`.

**STOP. Await approval before any implementation begins.**

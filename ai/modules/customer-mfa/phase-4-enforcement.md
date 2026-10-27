---
plan: customer-mfa
phase: 4
title: Mandatory enforcement, forced enrollment, lockout recovery
status: not_started
last_updated: 2026-07-10
depends_on: [phase-3]
checkpoint: none
---

# Phase 4 — Mandatory Enforcement & Recovery

## Goal

Make MFA **required for every non-admin (web-guard) user**: any web user
without a confirmed second factor is redirected to enrollment and cannot use
the portal until enrolled; enrolled users must pass the challenge (Phase 3).
Ship the lockout-recovery command **before** flipping enforcement on.

## Hard Prerequisite (order of operations)

`php artisan user:reset-mfa {email}` and Phase 2 recovery codes must both
exist and be tested **before** the `mfa` middleware is registered. Do not
enable enforcement in the same commit that lacks a proven recovery path — a
locked-out user with no escape is the failure mode we are explicitly avoiding
(same rule the admin MFA module followed before `isRequired: true`).

## Files to Create/Modify

- `app/Http/Middleware/EnsureMfaSatisfied.php` (alias `mfa`):
  - **admin guard authenticated → return `$next($request)`** (Filament MFA
    already handled it; never double-challenge shared `auth.web_or_admin`
    routes),
  - web user **not enrolled** (`!hasTwoFactorEnabled()`) → redirect to
    `mfa.settings` with a "MFA is required" flash,
  - web user enrolled but session **not `mfa.passed`** → redirect to
    `mfa.challenge`,
  - else pass. Must **exempt** the enrollment, challenge, email-verification,
    logout, and profile-security routes so users aren't trapped in a redirect
    loop.
- `bootstrap/app.php` — register the `mfa` alias (next to `verified.enabled`).
- `routes/web.php` / `routes/auth.php` — add `mfa` to the gated customer
  surface (dashboard, store, company, SAM favorites, mil-spec download,
  profile — the same set carrying `verified.enabled`, plus store routes).
  Public/guest routes (welcome, order tracker, contact, repair services,
  privacy consent) stay open. Confirm the exact list against `routes/web.php`
  at implementation.
- `app/Http/Controllers/Auth/MfaSettingsController.php@disable` — for web
  users, disallow self-disable once mandatory (or require re-enroll), since
  disabling would immediately bounce them back to forced enrollment. Keep the
  command as the only reset path.
- `app/Console/Commands/ResetCustomerMultiFactorAuthentication.php`
  (`user:reset-mfa {email}`) — mirror `admin:reset-mfa`: confirm, null out
  `two_factor_secret` / `two_factor_recovery_codes` / `two_factor_confirmed_at`
  / `two_factor_method`, delete pending `mfa_codes`, report success.
- Docs: `.env.example` / README note — `MAIL_MAILER=log` locally means
  email-OTP codes are in `storage/logs`; production needs a running queue
  worker for `MfaEmailCode`.

## Rollout Notes (call out to the orchestrator)

- Every existing user has `two_factor_confirmed_at = null` → on next login they
  hit forced enrollment. This is intended but support-heavy; recommend a
  heads-up announcement and confirming the queue worker + Mailgun are healthy
  before merge.
- Consider shipping Phases 1–3 first (enrollment + challenge available but
  optional) so willing users pre-enroll, then enabling this phase's middleware
  as a distinct, announced change.

## Tests

- `tests/Feature/Auth/MfaEnforcementTest.php` (Pest):
  - Enrolled web user who hasn't passed the challenge is blocked from the
    dashboard/store and redirected to the challenge.
  - **Unenrolled** web user is redirected to `mfa.settings` and cannot reach
    any gated route until enrolled.
  - **Admin** on a shared `auth.web_or_admin` route passes through with no MFA
    redirect (regression guard for double-challenge).
  - Enrollment, challenge, and logout routes remain reachable while unmet (no
    redirect loop).
  - `user:reset-mfa {email}` clears enrollment; the user is forced to
    re-enroll on next login; unknown email errors cleanly.

## Success Criteria

- No non-admin user can access a gated customer route without a confirmed,
  challenge-passed second factor.
- Admins are never MFA-redirected on the customer surface.
- A locked-out user has a tested recovery path (recovery codes + command).
- `php artisan test` green; `vendor/bin/pint --dirty` clean.
- Real-browser walk-through owed before `Complete`: enroll → log out → log in
  → challenge → dashboard, plus the forced-enrollment redirect, verified in
  the actual UI (automated feature tests can't fully cover the Inertia flow).

**STOP. Await phase approval.**

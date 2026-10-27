---
plan: customer-mfa
phase: 2
title: Self-service enrollment (Security page) — not yet enforced
status: not_started
last_updated: 2026-07-10
depends_on: [phase-1]
checkpoint: none
---

# Phase 2 — Self-Service Enrollment

## Goal

Let a logged-in customer voluntarily enroll a second factor (authenticator or
email) from a **Security** page, confirm it, and receive recovery codes.
Still **not enforced** — a user who ignores it can keep using the portal. This
de-risks Phase 4 by letting real enrollment be exercised before it's mandatory.

## First Step

Confirm the profile UI structure: `app/Http/Controllers/ProfileController.php`
+ `resources/js/Pages/Profile/` (Breeze Inertia). Decide whether Security is a
new page (`Profile/Security.vue`) or a partial on the existing profile edit
page. Recommendation: a dedicated `Profile/Security.vue` reached from the
profile menu, keeping concerns separate.

## Files to Create/Modify

- **Routes** (`routes/web.php`, inside the existing `auth` group):
  - `GET  /profile/security` → `MfaSettingsController@show` (`mfa.settings`)
  - `POST /profile/security/enroll` → `@enroll` (choose method; TOTP →
    generate + stash pending secret, return QR; email → issue enroll OTP)
  - `POST /profile/security/confirm` → `@confirm` (verify live code → set
    `two_factor_confirmed_at`, `two_factor_method`, persist secret, generate +
    return recovery codes)
  - `POST /profile/security/recovery-codes` → `@regenerateRecoveryCodes`
  - `DELETE /profile/security` → `@disable` (allowed **only while MFA is not
    yet mandatory**; Phase 4 removes/guards this for web users). Requires
    password confirmation.
- `app/Http/Controllers/Auth/MfaSettingsController.php` — thin controller over
  `TwoFactorAuthenticationService`. Pending TOTP secret held in the session
  until confirmed (never persisted unconfirmed).
- `resources/js/Pages/Profile/Security.vue` — method chooser, QR display
  (inline SVG from Phase 1), code-confirm field, recovery-code panel
  (copy/download, shown once), regenerate + disable actions. Match the
  existing profile page styling / `AuthenticatedLayout`.
- Profile menu entry linking to the Security page (wherever the profile
  dropdown/nav lives in the Vue layout).
- Form Request(s) for enroll/confirm validation (6-digit code format, method
  in `totp|email`).

## Out of Scope

- No login challenge yet (Phase 3) — enrolling here does **not** start
  prompting the user at login. (Implementation note: Phase 3 introduces the
  challenge; until then an enrolled user simply has the columns set.)
- No enforcement/forced enrollment (Phase 4).

## Tests

- `tests/Feature/Auth/MfaEnrollmentTest.php` (Pest):
  - TOTP: enroll returns a secret/QR; confirming with a valid TOTP sets
    `two_factor_confirmed_at` + `two_factor_method='totp'` and returns 8
    recovery codes; an invalid code does not enroll.
  - Email: enroll issues a code (assert `mfa_codes` row / `Notification::fake`
    once Phase 3 notification exists — for Phase 2, assert the row); confirming
    with the right code enrolls with `two_factor_method='email'`.
  - Regenerate replaces recovery codes (old set no longer valid).
  - Disable clears all `two_factor_*` (password-confirmation enforced).
  - A guest cannot reach any of these routes.

## Success Criteria

- A user can fully self-enroll TOTP and email methods and see recovery codes.
- Unconfirmed secrets are never persisted to `users`.
- `php artisan test` green; `vendor/bin/pint --dirty` clean.
- Existing login/logout behavior unchanged.

**STOP. Await phase approval.**

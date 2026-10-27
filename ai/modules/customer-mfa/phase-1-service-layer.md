---
plan: customer-mfa
phase: 1
title: Schema, models & TwoFactorAuthenticationService (no behavior change)
status: not_started
last_updated: 2026-07-10
depends_on: []
checkpoint: none
---

# Phase 1 — Data Model & Service Layer

## Goal

Lay the storage + crypto foundation for customer MFA **without changing any
user-facing behavior**. After this phase, nobody is challenged or forced to
enroll — the columns, table, and service simply exist and are unit-tested.
Safe to ship on its own.

## First Step (before writing code)

Confirm package versions resolve against the installed Laravel 12 / PHP
version and pin them:
- `pragmarx/google2fa` (TOTP generate/verify).
- `bacon/bacon-qr-code` (QR SVG rendering, no JS dependency).

Prefer the SVG QR backend (no `imagick`/`gd` requirement). Verify no existing
`two_factor_*` columns exist on `users` (grep migrations — there are none as
of planning).

## Files to Create/Modify

- `composer.json` — add `pragmarx/google2fa` and `bacon/bacon-qr-code`.
- `database/migrations/xxxx_add_two_factor_columns_to_users_table.php`:
  `two_factor_secret` (text, nullable), `two_factor_recovery_codes` (text,
  nullable), `two_factor_confirmed_at` (timestamp, nullable),
  `two_factor_method` (string, nullable). All nullable — no backfill needed.
- `database/migrations/xxxx_create_mfa_codes_table.php`: `id`, `user_id`
  (foreignId, cascade on delete), `code_hash` (string), `purpose` (string:
  `enroll`|`challenge`), `expires_at` (timestamp), `consumed_at` (timestamp,
  nullable), timestamps. Index on `(user_id, purpose)`.
- `app/Models/User.php`:
  - add `two_factor_secret`, `two_factor_recovery_codes` to `$hidden`,
  - cast `two_factor_secret` → `encrypted`, `two_factor_recovery_codes` →
    `encrypted:array`, `two_factor_confirmed_at` → `datetime`,
  - helper `hasTwoFactorEnabled(): bool` (`two_factor_confirmed_at !== null`),
  - `mfaCodes(): HasMany` relationship.
- `app/Models/MfaCode.php` — model with fillable + `expires_at`/`consumed_at`
  casts; scopes `active()` (not consumed, not expired) and `forPurpose()`.
- `app/Services/Auth/TwoFactorAuthenticationService.php`:
  - `generateSecret(): string`
  - `qrCodeSvg(User $user, string $secret): string` (otpauth URI →
    `Company name` issuer, user email label)
  - `verifyTotp(User $user, string $code): bool` (±1 period window)
  - `generateRecoveryCodes(int $count = 8): array` and
    `consumeRecoveryCode(User $user, string $code): bool`
  - `issueEmailCode(User $user, string $purpose): void` (create hashed
    `mfa_codes` row, 10-min TTL, dispatch notification — notification class
    lands in Phase 3; for now the method may create the row and the
    notification wiring is completed in Phase 3, OR stub the send. Decide at
    implementation: keep Phase 1 send-free to avoid a half-built notification.)
  - `verifyEmailCode(User $user, string $code, string $purpose): bool`
    (hash-compare against active rows, mark `consumed_at`)
  - `canIssueEmailCode(User $user): bool` (rate-limit guard, e.g. max 5 / 10
    min via `RateLimiter`).

## Out of Scope

- No UI, no routes, no login changes, no middleware — those are Phases 2–4.
- The email-OTP **notification class** and its wiring belong to Phase 3;
  Phase 1 only builds the `mfa_codes` row creation + verification so it can be
  unit-tested without sending mail.

## Tests

- `tests/Unit/TwoFactorAuthenticationServiceTest.php` (Pest):
  - secret generation is non-empty / base32,
  - a code from the same secret verifies; a wrong code does not,
  - recovery codes generate the requested count, are unique, a valid one is
    consumed once and rejected on reuse,
  - email code: issue → verify succeeds once → reuse fails; expired row fails;
    wrong-purpose row fails,
  - `canIssueEmailCode` returns false after the rate cap.
- Assert `two_factor_secret` / `two_factor_recovery_codes` are stored
  **encrypted at rest** (raw DB value != plaintext), mirroring the admin MFA
  smoke test.

## Success Criteria

- Migrations run clean up and down.
- `php artisan test` green (new unit tests + no regressions).
- `vendor/bin/pint --dirty` clean.
- No change to any existing login, route, or page.

**STOP. Await phase approval.**

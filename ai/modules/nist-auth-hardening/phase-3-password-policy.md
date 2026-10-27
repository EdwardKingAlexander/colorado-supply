# Phase 3 — Password Policy with Breached-Password Check

## Goal

Every password set for a customer or admin follows NIST SP 800-63B: checked
against known-breached passwords, minimum length enforced, long passphrases
allowed, and no composition rules or forced periodic changes.

Independent of Phases 1–2.

## NIST rules applied

- SHALL compare new passwords to a blocklist of compromised values →
  Laravel `Password::uncompromised()` (Have I Been Pwned range API,
  k-anonymity: only the first 5 SHA-1 characters leave the server).
- Minimum 8 characters when the password is used with MFA (all accounts
  after Phase 2); SHOULD allow at least 64 → `max(128)`.
- SHALL NOT impose composition rules (no mixed-case/symbol requirements)
  and SHALL NOT require periodic changes.
- Allow all printing characters and spaces (Laravel default).

## Sub-phases

### 3a. Inventory
- List every place a `User` or `Admin` password is set (registration,
  reset, profile change, Filament `UserResource`, admin profile page,
  admin creation commands such as `EnsureEdwardAdminUser`). Record the list
  in STATE.md.

### 3b. Central policy
- `AppServiceProvider::boot`: `Password::defaults(fn () =>
  Password::min(8)->max(128)->uncompromised())`.
- Every location from 3a uses `Password::defaults()` (Filament fields via
  `->rule(Password::defaults())`).
- Decide outage behavior (owner): if the breached-password API is
  unreachable, Laravel's `uncompromised()` passes the check by default.
  Recommended: accept that default and log a warning, so an outside outage
  can't block password changes; record the decision.

### 3c. Messages
- Clear error text: "This password has appeared in a data breach. Please
  choose a different one."

## Files to Create/Modify

- Modify: `app/Providers/AppServiceProvider.php`,
  `app/Http/Controllers/Auth/RegisteredUserController.php`,
  `NewPasswordController.php`, `PasswordController.php`,
  `app/Filament/Resources/UserResource.php`, admin profile page and admin
  creation command(s) from 3a, `lang/en/validation.php` if needed.

## Tests (Pest)

- Breached password rejected at registration, reset, profile change, and in
  Filament forms (HTTP faked with `Http::fake` for the range API).
- 7-character password rejected; 8 accepted; 128 accepted; 129 rejected.
- No composition rule: an all-lowercase, non-breached 12-char passphrase
  is accepted.
- No real network calls in tests.

## Success Criteria

- Every password-setting path enforces the same policy.
- Full suite green; Pint clean.

STOP. Await phase approval.

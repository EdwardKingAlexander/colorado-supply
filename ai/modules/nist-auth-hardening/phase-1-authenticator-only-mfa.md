# Phase 1 — [server] Authenticator-App-Only MFA on the Website

## Goal

Make the authenticator app (TOTP) plus recovery codes the only second
factor on the website for customers and staff (admin panel), matching the
mobile app (owner decision
2026-10-26). NIST SP 800-63B forbids email as an out-of-band authenticator,
so email codes are removed from enrollment and from the login challenge.
Customers already using email codes are moved to an authenticator app
without losing access or MFA protection.

No dependencies. Required by `mobile-foundation` Phase 1.

## Current State (verified 2026-10-26)

- `MfaSettingsController::enable` accepts `method` `totp` or `email`;
  `confirm` stores `two_factor_method`.
- `MfaChallengeController::verify` accepts an email code as the configured
  method **or as a fallback for TOTP users**; `sendEmailCode` +
  route `mfa.challenge.email` send codes.
- `TwoFactorAuthenticationService` has `issueEmailCode`, `verifyEmailCode`,
  `canIssueEmailCode`; `MfaEmailCode` notification; `mfa_codes` table.
- Vue: `Profile/Partials/TwoFactorAuthenticationForm.vue` (method picker),
  `Auth/Mfa/Challenge.vue` ("email me a code").
- `user:reset-mfa` command (lost-device recovery) stays as is.
- Admin panel (Filament) also allows `EmailAuthentication`; removed in 1b-admin
  (owner decision 2026-10-26).
- Local database has 0 enrolled customers; production count unknown.

## Sub-phases

### 1a. Measure existing email-method users
- Add `mfa:email-method-report` Artisan command: prints the count (and, with
  `--list`, emails) of users with `two_factor_method = 'email'` and confirmed
  MFA. Owner runs it on production.
- **Decision point:** if the count is 0, 1c's transition path is skipped and
  email codes are removed outright. If > 0, 1c is implemented.

**Done when:** command tested; owner has reported the production count.

### 1b. Remove email codes from enrollment and challenge
- `enable` validates `method` as `in:totp` only; remove email branch.
- `verify` accepts TOTP or recovery code only; remove email fallback.
- Remove `sendEmailCode`, route `mfa.challenge.email`, `MfaEmailCode`
  notification and the service's email-code methods.
- Vue: enrollment form shows authenticator setup only (QR + setup key);
  challenge page drops "email me a code", keeps recovery code option.
- Migration dropping `mfa_codes` (after 1c, if 1c applies).

### 1b-admin. Remove email codes from the admin panel
- `AdminPanelProvider::multiFactorAuthentication()` keeps only
  `AppAuthentication::make()->recoverable()` (still `isRequired: true`).
- Before deploying, list admins whose only confirmed Filament factor is email
  (`has_email_authentication = true` and `app_authentication_secret` null —
  verified on `Admin` 2026-10-26). Extend the 1a report command with an
  `--admins` section for this.
- Remove `HasEmailAuthentication` from `Admin` (interface, cast, methods) and
  drop the `has_email_authentication` column in a migration once no admin
  depends on it. Note: `app/Models/Admin.php` currently has uncommitted
  owner changes; build on them, never discard them. Each must set up an authenticator app first,
  or be reset with `admin:reset-mfa --force` and re-enroll at next login.
- Update `admin-account-security-complete` runbook text if it mentions email
  codes.

### 1c. Transition for existing email-method users (only if 1a count > 0)
- At their next website login, email-method users complete the challenge
  with a **recovery code**, or with a one-time email code allowed only for
  `two_factor_method = 'email'` users and only to reach enrollment.
- `EnsureMfaSatisfied` then forces them to the authenticator setup screen
  before any other page (same mechanism as `mfa.required` forced
  enrollment), regardless of `MFA_REQUIRED`.
- On TOTP confirmation their method becomes `totp`; new recovery codes shown.
- Branded email notice to affected customers announcing the change
  (owner approves wording before send; sending is an owner action).
- Once the report shows 0 email-method users, a follow-up step removes the
  transition path and drops `mfa_codes` (tracked in STATE.md, not left as
  dead code).

## Files to Create/Modify

- Create: `app/Console/Commands/MfaEmailMethodReport.php`; migration dropping
  `mfa_codes` (timing per 1a/1c); transition notification if 1c applies.
- Modify: `MfaSettingsController.php`, `app/Providers/Filament/AdminPanelProvider.php`, `MfaChallengeController.php`,
  `TwoFactorAuthenticationService.php`, `EnsureMfaSatisfied.php` (1c only),
  `ProfileController.php` (props), `routes/auth.php`, `routes/web.php`,
  `resources/js/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue`,
  `resources/js/Pages/Auth/Mfa/Challenge.vue`.
- Delete: `app/Notifications/MfaEmailCode.php` (after 1c, if it applies).
- Tests: `tests/Feature/Auth/MfaEnrollmentTest.php`, `MfaChallengeTest.php`,
  `MfaEnforcementTest.php`, `tests/Unit/TwoFactorAuthenticationServiceTest.php`.

## Tests (Pest)

- Enrollment with `method=email` → 422; TOTP enrollment unchanged.
- Challenge: TOTP and recovery code pass; an email code is rejected for a
  TOTP user (fallback removed); `mfa.challenge.email` route no longer exists.
- Report command counts only confirmed email-method users.
- 1c (if applies): email-method user can pass with a recovery code, is then
  forced to authenticator setup on every route except setup/logout, and is
  released after confirming TOTP.
- Admin panel offers only the authenticator app (and recovery codes);
  admin login with an email code is impossible; admin TOTP login unchanged.

## Success Criteria

- No customer can enroll in or pass MFA with an email code on the website
  (except the time-boxed 1c transition, if needed).
- Existing TOTP customers notice no change.
- `npm run build`, `php artisan test`, `vendor/bin/pint --dirty` clean.

## Risks

- Customers who lose their phone now rely on recovery codes or
  `user:reset-mfa` by staff. The setup screen must make saving recovery
  codes explicit.
- The `customer-mfa` module's phases are still `Awaiting Validation`; this
  phase changes code it introduced. Note the change in that module's
  STATE.md log so its validation reflects current behavior.

STOP. Await phase approval.

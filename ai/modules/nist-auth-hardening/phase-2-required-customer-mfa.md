# Phase 2 — Required MFA for Every Customer

## Goal

Every customer must have an authenticator app set up before using the
website (NIST SP 800-171 3.5.3). Enforced immediately on deploy (owner
decision 2026-10-26).

Depends on: Phase 1.

## Current State (verified 2026-10-26)

- `EnsureMfaSatisfied` already has a forced-enrollment branch behind
  `config('mfa.required')`, with `isEnrollmentSurface()` exemptions and JSON
  423 responses; `MfaSettingsController::disable` already refuses when
  required. Built and tested in `customer-mfa` Phase 4 with the flag off.

## Sub-phases

### 2a. Make required the default
- `config/mfa.php`: `'required' => env('MFA_REQUIRED', true)`; set
  `MFA_REQUIRED=true` in `.env.example` and `phpunit.xml`.
- Owner sets `MFA_REQUIRED=true` in production `.env` at deploy (owner
  action; agents do not edit environment files).

### 2b. Setup experience
- Forced-enrollment landing shows a clear explanation ("Colorado Supply
  requires an authenticator app for all accounts"), the authenticator setup
  card, and the recovery-codes step requiring an "I saved these" check
  before continuing.
- Disable-MFA control hidden in the profile UI when required.

### 2c. Registration path
- New customers are routed to authenticator setup right after registration
  (and email verification if enabled) before any other page.

## Files to Create/Modify

- Modify: `config/mfa.php`, `.env.example`, `phpunit.xml`,
  `app/Http/Middleware/EnsureMfaSatisfied.php` (only if 2c needs ordering
  changes), `resources/js/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue`,
  `resources/js/Pages/Profile/Edit.vue`,
  `app/Http/Controllers/Auth/RegisteredUserController.php` (redirect target).
- Tests: `tests/Feature/Auth/MfaEnforcementTest.php`,
  `RegistrationTest.php`, and any test that logs in a customer without MFA
  (updated to enroll or to set `mfa.required=false` explicitly only where
  the test is about something else).

## Tests (Pest)

- With default config, an unenrolled customer is redirected to setup from
  every gated route; JSON requests get 423.
- Enrollment surface, email verification and logout remain reachable.
- After confirming TOTP the customer reaches the dashboard.
- New registration lands on setup.
- Disabling MFA is refused.
- Admin routes unaffected.

## Success Criteria

- No customer can use the website past login without a confirmed
  authenticator app.
- Full suite green; Pint and build clean.

## Risks

- Many existing feature tests log in customers without MFA; updating them is
  part of this phase, not deferred.

STOP. Await phase approval.

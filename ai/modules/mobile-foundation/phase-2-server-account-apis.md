# Phase 2 — [server] Registration, MFA Enrollment, Email Verification, Password Reset, Account Deletion

## Goal

Every account action the app needs exists as a JSON API that shares its
rules with the website, so a brand-new customer can register, verify email,
set up MFA, recover a password and delete their account from the phone.

Depends on: Phase 1 (and `nist-auth-hardening` Phase 1, since enrollment is TOTP-only).

## Sub-phases

### 2a. Shared actions (no web behavior change)
- `App\Actions\Auth\RegisterCustomer` — validation rules and `User::create`
  + `Registered` event lifted from `RegisteredUserController::store`; web
  controller calls the action.
- `App\Actions\Auth\DeleteCustomerAccount` — the deletion logic in
  `ProfileController::destroy` (password check, delete user) plus revoking all
  mobile sessions; web controller calls the action.
- `TwoFactorAuthenticationService::beginEnrollment()/confirmEnrollment()` —
  the enable/confirm logic from `MfaSettingsController`, with the pending
  secret passed in/out instead of read from the session; web controller keeps
  storing it in the session.

**Done when:** all existing `tests/Feature/Auth/*` and `ProfileTest.php` pass
unchanged.

### 2b. Mobile endpoints (prefix `v1/mobile`)
- `POST auth/register` `{name, email, password, password_confirmation,
  device_name, platform}` — `RegisterCustomer`, then returns a challenge
  token with ability `mfa:enroll` (MFA setup is the next mandatory step).
  Throttled (5/min per IP).
- `POST auth/mfa/enroll/start` (ability `mfa:enroll`) `{method}` — TOTP only:
  returns `{secret, otpauth_uri}` (pending secret held server-side in cache
  keyed to the challenge token, 10 min).
- `POST auth/mfa/enroll/confirm` (ability `mfa:enroll`) `{code}` — persists
  factor, returns `{recovery_codes, access_token, unlock_secret, expires_at,
  user}` (session started via Phase 1 service).
- `POST email/verification-notification` (ability `app`) — resends the
  branded verification email (`throttle:6,1`). Verification itself stays the
  existing signed web link; the app polls `GET me`.
- `POST auth/forgot-password` `{email}` — `Password::sendResetLink`, always
  the same response (no account enumeration), throttled. Reset completes on
  the existing website page.
- `DELETE account` (ability `app`) `{password}` — `DeleteCustomerAccount`.
- `verified.enabled` applied to future `app` routes that need a verified
  email; `me` and the resend endpoint are exempt so the app can show the
  verify screen.

## Files to Create/Modify

- Create: `app/Actions/Auth/RegisterCustomer.php`,
  `app/Actions/Auth/DeleteCustomerAccount.php`,
  `app/Http/Controllers/Api/V1/Mobile/RegistrationController.php`,
  `.../Mobile/MfaEnrollmentController.php`,
  `.../Mobile/PasswordResetController.php`,
  `.../Mobile/AccountController.php`,
  `.../Mobile/EmailVerificationController.php`.
- Modify: `RegisteredUserController.php`, `ProfileController.php`,
  `MfaSettingsController.php`, `TwoFactorAuthenticationService.php`,
  `routes/api.php`.

## Tests (Pest)

`tests/Feature/Api/Mobile/MobileRegistrationTest.php`
- Register returns `mfa:enroll` challenge token, not an access token;
  duplicate email and weak password rejected with field errors; throttled.
- Enrollment start → confirm with a valid TOTP code → recovery codes + access
  token; invalid code rejected; expired pending secret rejected.
- Enroll token cannot reach `app` routes.

`tests/Feature/Api/Mobile/MobileAccountTest.php`
- Existing user without MFA logs in → `mfa_setup_required` → enroll → access.
- Forgot password returns identical response for known/unknown email and
  sends `ResetPassword` notification only for known.
- Resend verification throttled; `me.email_verified` flips after the signed
  link is visited.
- Delete account with wrong password → 422, user remains; correct password →
  user deleted, sessions and tokens gone.

Regression: web registration, MFA enrollment and profile deletion tests pass
unchanged.

## Success Criteria

- A new customer can go register → MFA setup → signed in using only the API.
- Website behavior is byte-for-byte unchanged (existing tests green).
- `php artisan test` and `vendor/bin/pint --dirty` clean.

## Risks

- Registration on the website may attach companies/organizations via
  `CompanyDomainService` side effects in the `User` model; the action must
  preserve them. Verify during 2a.
- Holding the pending TOTP secret in cache: use the challenge token id as the
  key and a 10-minute TTL so it cannot leak across users.

STOP. Await phase approval.

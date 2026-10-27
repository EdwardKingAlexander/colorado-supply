# Phase 4 — Session Limits: 60-Minute Idle, 12-Hour Re-authentication, No "Remember Me"

## Goal

Website sessions for customers and admins end after 60 minutes without
activity and always require a full sign-in (password + MFA, or passkey)
after 12 hours, matching the mobile app (NIST SP 800-63B-4 AAL2; SP 800-171
3.1.11).

Independent of Phases 1–3.

## Current State (verified 2026-10-26)

- `SESSION_LIFETIME=120`, `expire_on_close=false`.
- Customer login (`Login.vue`, `LoginRequest`) has a "Remember me"
  checkbox passed to `Auth::attempt(..., $remember)` — a remember cookie
  bypasses session expiry indefinitely.
- Filament admin login (`->login()`) shows its own "Remember me" toggle.

## Sub-phases

### 4a. Idle timeout
- `SESSION_LIFETIME=60` in `.env.example` and config default
  (`config/session.php` default 60). Owner updates production `.env`.

### 4b. Overall 12-hour limit
- On successful full authentication (customer MFA pass, admin MFA pass,
  passkey sign-in) store `auth.authenticated_at` in the session.
- `EnforceSessionLimits` middleware (web group, both guards): if
  `authenticated_at` is missing or older than 12 hours → log out that guard,
  invalidate session, redirect to the right login with a "Please sign in
  again" message (JSON: 401).
- `config/auth_limits.php` (or `config/mobile.php` shared value):
  `reauthenticate_after_hours` = 12, used by web and mobile.

### 4c. Remove "Remember me"
- Customer: remove checkbox from `Login.vue`; `LoginRequest` always passes
  `false`.
- Admin: custom Filament login page extending
  `Filament\Auth\Pages\Login` without the remember field (verify the class
  name and override point in Filament 4.15 vendor code); register via
  `->login(CustomLogin::class)`.
- Clear existing `remember_token` values for users and admins in a
  migration so old remember cookies stop working.

## Files to Create/Modify

- Create: `app/Http/Middleware/EnforceSessionLimits.php`,
  `app/Filament/Pages/Auth/Login.php`, config file, migration clearing
  remember tokens.
- Modify: `config/session.php`, `.env.example`, `bootstrap/app.php`,
  `app/Http/Requests/Auth/LoginRequest.php`,
  `resources/js/Pages/Auth/Login.vue`,
  `app/Http/Controllers/Auth/MfaChallengeController.php`,
  `app/Providers/Filament/AdminPanelProvider.php`.

## Tests (Pest)

- Customer and admin sessions rejected after 12 h (time travel) even with
  continuous activity; accepted at 11 h 59 m.
- Idle > 60 min → redirected to login.
- Login ignores a `remember=1` field; no remember cookie issued.
- Admin login page has no remember toggle.
- Existing remember cookies no longer authenticate after the migration.

## Success Criteria

- No website session survives 60 idle minutes or 12 hours.
- Full suite green; Pint and build clean.

## Risks

- Customers lose long-lived sign-ins on deploy (intended).
- 800-171 3.1.10 (session lock) on the web is satisfied by the 60-minute
  termination plus the user's operating-system screen lock; record this
  reasoning in the Phase 6 compliance record.

STOP. Await phase approval.

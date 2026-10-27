# Phase 1 — [server] Mobile Sessions, Token Issuance & MFA Challenge

## Goal

Customers can sign in from the app with password + MFA and receive a
short-lived, server-controlled access token. The server enforces the 12-hour
overall limit, the 60-minute idle limit, biometric-backed unlock, and logout.
Admins can never get a mobile token.

Depends on: `nist-auth-hardening` Phase 1 (challenge accepts TOTP and recovery codes only).

## Sub-phases

### 1a. Schema & models
- Migration `create_mobile_sessions_table`: `id`, `user_id` (FK, cascade
  delete), `device_name`, `platform` (`ios`/`android`), `unlock_secret_hash`,
  `authenticated_at`, `expires_at`, `last_unlocked_at` (nullable),
  `revoked_at` (nullable), timestamps; index `(user_id, revoked_at)`.
- Migration adding nullable `mobile_session_id` FK (cascade delete) to
  `personal_access_tokens`.
- `App\Models\MobileSession` with `isActive()`, `revoke()`, `user()`,
  `tokens()`.
- `App\Models\Sanctum\PersonalAccessToken` extending Sanctum's model with
  `mobileSession()`; registered via `Sanctum::usePersonalAccessTokenModel()`.
- `User` gains `HasApiTokens` and `mobileSessions()`.
- `config/mobile.php`: `session_hours` (12), `idle_minutes` (60),
  `challenge_minutes` (10).

**Done when:** migrations run up/down cleanly; model unit tests pass.

### 1b. Session service
- `App\Services\Mobile\MobileSessionService`:
  - `issueChallengeToken(User, device, platform): string` — ability
    `mfa:challenge` (enrolled) or `mfa:enroll` (not enrolled), expires in
    `challenge_minutes`.
  - `start(User, device, platform): array` — creates `MobileSession`
    (`expires_at = now + session_hours`), random 64-byte `unlock_secret`
    (stored hashed), access token (ability `app`, `mobile_session_id`,
    `expires_at = session expires_at`). Returns plain token + secret once.
  - `unlock(MobileSession, secret): string` — constant-time hash check,
    session still active → revokes old access tokens, issues a new one,
    sets `last_unlocked_at`.
  - `revoke(MobileSession)`.
- Token validation hook in `AppServiceProvider` via
  `Sanctum::authenticateAccessTokensUsing()`: tokens linked to a mobile
  session are valid only if the session is active **and**
  `last_used_at` (or `created_at`) is within `idle_minutes`. Tokens without
  a mobile session keep Sanctum's default behavior.

**Done when:** unit tests cover issuance, expiry, idle rejection, unlock,
revocation and constant-time secret comparison.

### 1c. Endpoints (`routes/api.php`, prefix `v1/mobile`, JSON only)
- `POST auth/login` `{email, password, device_name, platform}` — `User` only
  (never `Admin`), reuses `LoginRequest`-style throttle (5 / email+IP).
  Returns `{status: "mfa_required" | "mfa_setup_required", challenge_token,
  methods: ["totp","recovery_code"]}`. Same error for unknown email and wrong password.
- `POST auth/mfa/verify` (ability `mfa:challenge`) `{code | recovery_code}` —
  same rules as the website challenge after `nist-auth-hardening` Phase 1 (throttle 5, TOTP or
  recovery code only; shared `verifyChallenge`). Success → deletes challenge
  token, returns `{access_token, unlock_secret, expires_at, user}`.
- `POST auth/unlock` `{session_id, unlock_secret}` — unauthenticated, throttled
  (5 / session), returns new `access_token` or 401 `reauth_required`.
- `POST auth/logout` (ability `app`) — revokes the current mobile session.
- `GET me` (ability `app`) — `{id, name, email, email_verified,
  mfa_enabled, session_expires_at}` via an API Resource.
- Consistent error JSON: `{message, code}` with `code` in
  `invalid_credentials | throttled | mfa_invalid | session_expired |
  reauth_required | unauthorized`.
- Abilities middleware aliases (`abilities`, `ability`) registered in
  `bootstrap/app.php`.

**Done when:** feature tests below pass.

### 1d. Pruning & shared logic
- Extract the TOTP / email / recovery-code verification in
  `MfaChallengeController::verify` into `TwoFactorAuthenticationService::
  verifyChallenge(User, ?code, ?recovery)` so web and mobile share one
  implementation; web controller calls it unchanged in behavior.
- Schedule `sanctum:prune-expired --hours=24` and a `mobile:prune-sessions`
  command (deletes sessions expired/revoked > 24 h) daily.

## Files to Create/Modify

- Create: 2 migrations, `app/Models/MobileSession.php`,
  `app/Models/Sanctum/PersonalAccessToken.php`, `config/mobile.php`,
  `app/Services/Mobile/MobileSessionService.php`,
  `app/Http/Controllers/Api/V1/Mobile/AuthController.php`,
  `app/Http/Controllers/Api/V1/Mobile/MeController.php`,
  `app/Http/Requests/Mobile/LoginRequest.php`,
  `app/Http/Resources/Mobile/UserResource.php`,
  `app/Console/Commands/PruneMobileSessions.php`,
  `database/factories/MobileSessionFactory.php`.
- Modify: `app/Models/User.php`, `app/Providers/AppServiceProvider.php`,
  `bootstrap/app.php`, `routes/api.php`, `routes/console.php`,
  `app/Services/Auth/TwoFactorAuthenticationService.php`,
  `app/Http/Controllers/Auth/MfaChallengeController.php`.

## Tests (Pest)

`tests/Feature/Api/Mobile/MobileAuthTest.php`
- Login with valid credentials returns a challenge token, never an access
  token; enrolled vs. unenrolled status correct.
- Wrong password and unknown email return the same error; 6th attempt
  throttled.
- Admin credentials are rejected (no `User` row) even if emails collide.
- Challenge token cannot call `me` or any `app` route.
- Valid TOTP / recovery code → access token + unlock secret; recovery code
  is consumed; invalid code throttled after 5.
- Access token rejected after 12 h (time travel) and after 61 min idle;
  accepted at 59 min.
- Unlock with correct secret within 12 h → new token, old token revoked;
  wrong secret → 401; after 12 h → `reauth_required`.
- Logout revokes session; token then 401.
- Deleting the user cascades sessions and tokens.

`tests/Unit/Mobile/MobileSessionServiceTest.php` — service rules above.

Regression: existing `tests/Feature/Auth/MfaChallengeTest.php` and
`AuthenticationTest.php` pass unchanged.

## Success Criteria

- A customer can complete login → MFA → `me` entirely over the API.
- No path issues an `app` token without a passed MFA step.
- 12 h, 60 min and unlock rules enforced server-side and proven by tests.
- `php artisan test` and `vendor/bin/pint --dirty` clean.

## Risks

- `last_used_at` is written on every request; acceptable at mobile volume.
- Sanctum guard config lists `web` and `admin` first; bearer tokens are only
  consulted when no session cookie is present. Tests must call the API
  without cookies (as the app does).

STOP. Await phase approval.

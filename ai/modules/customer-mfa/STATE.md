# Customer (Web-Guard) MFA — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Awaiting Plan Approval | 2026-07-10 | Owner decisions captured: required for ALL non-admin users, custom build on Breeze (google2fa + bacon-qr-code + `mfa_codes` table), recovery codes, no remember-device. |
| 1 | Schema, models & service layer | Awaiting Validation | 2026-07-10 | Implemented: google2fa+bacon installed, 2 migrations, User casts/hidden/helper, MfaCode model, TwoFactorAuthenticationService. 9/9 unit tests pass, pint clean. |
| 2 | Self-service enrollment (Security page) | Awaiting Validation | 2026-07-10 | Implemented on Profile/Edit (TwoFactorAuthenticationForm card), MfaSettingsController, routes, MfaEmailCode notification (built early — email enrollment needs it). 7/7 feature tests, build clean. |
| 3 | Login challenge flow | Awaiting Validation | 2026-07-10 | Implemented: EnsureMfaSatisfied middleware (global web group, admin-skip), MfaChallengeController, Auth/Mfa/Challenge.vue, login-store redirect, rate limiting. 7/7 feature tests. Security note below. |
| 4 | Mandatory enforcement + recovery | Awaiting Validation | 2026-07-10 | Built, **flag OFF** (`config('mfa.required')` default false). Forced-enrollment branch in `EnsureMfaSatisfied`, disable blocked when required, `user:reset-mfa` command. 8/8 feature tests; Auth suite 79/79. Flip to on = set `MFA_REQUIRED=true` after infra confirmed + users notified. |

## Log

- 2026-07-10 — Plan drafted after codebase review and owner clarification.
  - Verified auth stack: Laravel Breeze + Inertia (Vue); login via
    `AuthenticatedSessionController@store` → `LoginRequest@authenticate()`
    (loops web then admin guard). No MFA packages installed for the web guard.
  - Admin MFA is Filament-native and out of scope; this module is the web
    guard only. Key hazard found: `auth.web_or_admin` routes are shared, so
    the enforcement middleware MUST skip the admin guard — regression-tested
    in Phase 4.
  - Owner decisions (2026-07-10): (1) MFA **required for every non-admin
    user** — no gov/commercial flag (none exists in schema; whole platform is
    CUI-relevant); (2) **custom build on Breeze** — `pragmarx/google2fa` +
    `bacon/bacon-qr-code`, `mfa_codes` table for email OTP; (3) **recovery
    codes** included; no remember-device, no Filament ops UI.
  - Mirrored existing patterns: `verified.enabled` middleware (enforcement
    shape), `admin:reset-mfa` command (lockout recovery), `VerifyEmailAddress`
    notification (branded queued mail).
  - Flagged as hard prerequisite: `user:reset-mfa` command + recovery codes
    must exist and be tested before the `mfa` middleware is registered.
    Recommended shipping Phases 1–3 (optional MFA) before enabling Phase 4's
    mandatory enforcement, with an announcement, since every existing user
    lands on forced enrollment at next login.
  - Awaiting orchestrator approval of the overall plan before any code.
- 2026-07-10 — Plan approved; implemented Phases 1–3 in one session (blanket
  phase-gate approval, see [[feedback_phase_gate_approval]]).
  - **Phase 1**: `pragmarx/google2fa` ^9 + `bacon/bacon-qr-code` ^3 installed;
    migrations `..._add_two_factor_columns_to_users_table` (encrypted
    secret/recovery-codes, confirmed_at, method) + `..._create_mfa_codes_table`
    (hashed single-use email codes); `MfaCode` model; `User` casts/hidden +
    `hasTwoFactorEnabled()`; `TwoFactorAuthenticationService` (TOTP verify ±1
    window, QR SVG, recovery codes, email OTP issue/verify, rate limit).
    Unit test 9/9. Note: Unit Pest tests need explicit
    `uses(Tests\TestCase::class, RefreshDatabase::class)` — the Pest.php only
    binds `TestCase` to Feature.
  - **Phase 2**: MFA management as a card on `Profile/Edit`
    (`TwoFactorAuthenticationForm.vue`) rather than a separate page — reachable
    under `auth` only, so it survives Phase 4 forced-enrollment. Deviation from
    plan: built `MfaEmailCode` notification here (Phase 2 email enrollment needs
    it) instead of Phase 3. `MfaSettingsController` (enable/confirm/regenerate/
    disable). Feature test 7/7.
  - **Phase 3**: `EnsureMfaSatisfied` middleware appended to the **web group
    globally** (not per-route) — admin guard skipped (verified by test:
    admin→Filament dashboard, never challenged), unenrolled users pass
    (Phase-3 optional), enrolled+not-`mfa.passed`→challenge.
    `MfaChallengeController` (TOTP/email-OTP/recovery, throttled),
    `Auth/Mfa/Challenge.vue`, login `@store` redirects enrolled web users to the
    challenge. Feature test 8/8.
  - **Security property discovered & locked in**: because the gate is global,
    an enrolled user who has NOT cleared the challenge is also blocked from the
    `mfa.disable`/`mfa.recovery-codes` routes — correct, since otherwise a
    password-only attacker could disable MFA. Test `blocks disabling MFA before
    the challenge is passed` asserts this. (Required updating three Phase-2 tests
    to seed `mfa.passed` in session, simulating a post-challenge user.)
  - Verified no new regressions: Auth suite 71/71; Profile/Store/Dashboard/
    Orders 38/38; targeted Store 11/11. `vendor/bin/pint --dirty` clean;
    `npm run build` clean (Challenge + Profile compile). **Full `php artisan
    test` cannot complete in this environment** — a pre-existing 128M PHP
    memory cap is exhausted by heavy Filament/blade-icon rendering tests
    (part of the ~71 known-failing baseline, see
    [[project_local_https_and_test_baseline]]), unrelated to MFA.
  - **Owed before Complete**: real-browser walkthrough (enroll TOTP → log out →
    log in → challenge → dashboard; email-code path; recovery-code path). The
    Inertia flow can't be fully asserted in feature tests.
  - **Phase 4 deliberately not started** — it makes MFA mandatory for every
    existing customer (all drop onto forced enrollment at next login). Paused
    for an explicit owner go/no-go + confirmation that the queue worker +
    Mailgun are healthy, and `user:reset-mfa` ships in the same change.
- 2026-07-10 — Owner chose "build Phase 4, keep the flag off". Implemented:
  - `config/mfa.php` → `'required' => env('MFA_REQUIRED', false)`. Chose an
    env/config switch over an McpSettings admin-toggle: a security-enforcement
    flip should be a deliberate, version-controlled deploy action, not a
    fat-fingerable UI button. **Default false — MFA stays opt-in until flipped.**
  - `EnsureMfaSatisfied`: added the forced-enrollment branch — when
    `config('mfa.required')` is on, unenrolled web users are redirected to
    `profile.edit` (with an `mfa-setup-required` status), except on the
    enrollment surface (`profile.edit/update`, `mfa.enable/confirm`,
    `verification.*`, `mfa.challenge*`, `logout`) to avoid a redirect loop. No
    per-route wiring needed — the middleware is already global on the web group.
  - `MfaSettingsController@disable`: blocked when `config('mfa.required')` is on
    (a disable would just bounce the user back to forced enrollment).
  - `App\Console\Commands\ResetCustomerMultiFactorAuthentication`
    (`user:reset-mfa {email}`) — mirrors `admin:reset-mfa`; the locked-out
    recovery path, in place before enforcement can be enabled.
  - Tests `MfaEnforcementTest` 8/8 (flag-off pass-through, forced-enrollment
    redirect, enrollment surface no-loop, enrolled-still-challenged,
    admin-never-forced, disable-forbidden-when-required, reset command
    success + unknown-email error). Full Auth suite 79/79, pint clean.
  - **To enable in production**: set `MFA_REQUIRED=true` (env), after confirming
    the queue worker + Mailgun are healthy and customers have been notified.
    No code change required to flip.
  - **Still owed before any phase is `Complete`**: the real-browser walkthrough
    (enroll/challenge/recovery-code paths) — not assertable via feature tests.

- 2026-10-26 — Superseded in part by `nist-auth-hardening` Phase 1: email
  codes can no longer be enrolled or used by authenticator users (NIST SP
  800-63B). Customers already on email codes get a one-time transition
  (email code or recovery code at login, then forced authenticator setup).
  Challenge logic now lives in `TwoFactorAuthenticationService::verifyChallenge`.

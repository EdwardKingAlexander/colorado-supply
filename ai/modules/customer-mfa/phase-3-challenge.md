---
plan: customer-mfa
phase: 3
title: Login challenge flow (TOTP / email OTP / recovery code)
status: not_started
last_updated: 2026-07-10
depends_on: [phase-2]
checkpoint: none
---

# Phase 3 — Login Challenge

## Goal

After a correct password for an **enrolled** web-guard user, require the
second factor before the session is treated as fully authenticated. Support
the enrolled primary method, an email-OTP fallback, and recovery codes.
Enrollment stays optional until Phase 4 — but any user who enrolled in Phase 2
now gets challenged.

## Design — session gating

Rather than half-authenticating, the user is logged in normally by
`LoginRequest@authenticate()`, but a session flag gates them:
- On successful password login of a web user with `hasTwoFactorEnabled()`,
  set session `mfa.pending = true` and `mfa.passed = false`.
- A lightweight guard (folded into the Phase 4 `EnsureMfaSatisfied` middleware,
  but the challenge routes + verification land here) blocks all gated routes
  while `mfa.pending && !mfa.passed`, redirecting to the challenge screen.
- On correct factor: set `mfa.passed = true`, clear `mfa.pending`, regenerate
  session, redirect to `intended()`.

Confirm the two auth stacks: the Inertia controller flow is live; verify the
Livewire auth views are dormant (grep for their routes) before relying solely
on `AuthenticatedSessionController`.

## Files to Create/Modify

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php@store` — after
  `$request->authenticate()`, if the authenticated **web** user is enrolled,
  set the `mfa.pending` session flags and redirect to `mfa.challenge` instead
  of the dashboard. (Admins: no change — they never hit this branch.)
- **Routes** (`routes/auth.php`, `auth` group — reachable while pending):
  - `GET  /mfa/challenge` → `MfaChallengeController@show` (`mfa.challenge`)
  - `POST /mfa/challenge` → `@verify` (TOTP or recovery code)
  - `POST /mfa/challenge/email` → `@sendEmailCode` (issue OTP, rate-limited)
- `app/Http/Controllers/Auth/MfaChallengeController.php` — verifies via
  `TwoFactorAuthenticationService` (TOTP → email OTP → recovery code, in that
  order of attempt based on submitted field); sets `mfa.passed` on success.
- `resources/js/Pages/Auth/Mfa/Challenge.vue` — code input, "email me a code"
  button (disabled/countdown after send), "use a recovery code" toggle,
  "log out" link. Uses `GuestLayout`/branded auth layout.
- `app/Notifications/MfaEmailCode.php` — queued (`ShouldQueue`) branded
  `MailMessage` (mirror `VerifyEmailAddress`: subject/greeting/salutation, code
  + 10-min expiry line, "didn't request this" line). Wire
  `TwoFactorAuthenticationService::issueEmailCode()` to dispatch it.
- Rate limiting: challenge `verify` and `sendEmailCode` keyed on user id + IP
  (reuse `RateLimiter` like `LoginRequest`); lock out after N attempts with a
  clear message.

## Out of Scope

- Mandatory enforcement / forced enrollment (Phase 4). An **unenrolled** user
  still logs straight in here — only enrolled users are challenged.
- `user:reset-mfa` command (Phase 4).

## Tests

- `tests/Feature/Auth/MfaChallengeTest.php` (Pest):
  - Enrolled (TOTP) user: password login → redirected to challenge, dashboard
    blocked until passed; correct TOTP → reaches dashboard; wrong code →
    rejected, still blocked.
  - Recovery code passes the challenge and is consumed (reuse fails).
  - Email OTP: `sendEmailCode` dispatches `MfaEmailCode`
    (`Notification::fake`); the emailed code verifies; expired/used fails.
  - Unenrolled user logs in without any challenge (no regression).
  - Rate limiting: excessive wrong codes / email sends are throttled.
  - Admin login is unaffected (no web challenge).

## Success Criteria

- Enrolled web users must pass a second factor every login; unenrolled users
  and admins are unaffected.
- Email codes deliver via the branded queued notification (in dev, appear in
  `storage/logs` — document).
- `php artisan test` green; `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

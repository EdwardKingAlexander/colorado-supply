# Customer (Web-Guard) Multi-Factor Authentication

## Problem Statement

The customer-facing portal (`web` guard `User`s — the `user` and `vendor`
roles) authenticates with a password only. The admin panel already enforces
MFA natively via Filament (see `ai/modules/admin-account-security-complete/`), but
non-admin users have no second factor.

Because this is a federal-contracting platform handling CUI (SAM.gov
documents, opportunity data), MFA for all users accessing the system is a
compliance driver, not a nicety — NIST SP 800-171 (3.5.3) and CMMC both
require multi-factor authentication for network access to accounts on systems
that store/process/transmit CUI.

**Owner decision (2026-07-10):** MFA is **required for every non-admin
(web-guard) user** — no government/commercial segmentation flag (there is none
in the schema today, and the whole platform is in scope for CUI). Users must
enroll a second factor before they can use the portal.

The owner wants two enrollment methods:

1. **Authenticator app (TOTP)** — user scans a QR code (Google Authenticator,
   Authy, 1Password, etc.).
2. **Email one-time code** — a 6-digit code emailed to the user's (verified)
   address.

Plus **recovery codes** as the sanctioned lockout-recovery path. No
"remember this device" (deliberately omitted — trusted-device bypass is
discouraged under strict CMMC and the owner opted out).

## Current State (verified in code)

- **Auth stack:** Laravel Breeze + Inertia (Vue 3). Login flows through
  `app/Http/Controllers/Auth/AuthenticatedSessionController@store` →
  `App\Http\Requests\Auth\LoginRequest@authenticate()`, which loops over the
  `web` then `admin` guards calling `Auth::guard($guard)->attempt()`. Login
  view: `resources/js/Pages/Auth/Login.vue`.
- **No MFA packages installed** for the web guard. `composer.json` has
  `laravel/breeze` only; no Fortify, no `pragmarx/google2fa`. Admin MFA is
  Filament-native (`Admin` model implements Filament's MFA contracts) and is
  **entirely separate** from what we build here.
- **`users` table:** `role` is `enum('admin','user','vendor')` default
  `user`; standard `email_verified_at`, `remember_token`. No two-factor
  columns.
- **Mixed guard access:** most customer routes use the `auth.web_or_admin`
  alias (`App\Http\Middleware\AuthenticateWebOrAdmin`) — an **admin can browse
  the customer store**. Any MFA enforcement we add **must skip the admin
  guard** so admins aren't double-challenged (they already passed Filament
  MFA). Pure-web routes use plain `auth` (e.g. `/profile`, `/company`).
- **Enforcement pattern to mirror:** `verified.enabled` alias →
  `App\Http\Middleware\EnsureEmailIsVerifiedWhenEnabled` (registered in
  `bootstrap/app.php`), which redirects unmet users to a notice route. Our MFA
  middleware follows the same shape.
- **Recovery-command pattern to mirror:**
  `app/Console/Commands/ResetAdminMultiFactorAuthentication.php`
  (`admin:reset-mfa {email}`) — a locked-out admin's escape hatch. We add the
  web-guard equivalent.
- **Branded mail pattern to mirror:** `App\Notifications\VerifyEmailAddress`
  (queued `MailMessage`, "Colorado Supply & Procurement" branding, published
  Markdown mail theme at `resources/views/vendor/mail/`). The email-OTP
  notification uses the same conventions.
- **Local mail caveat:** Mailgun sends fail in local dev; the app uses the
  `log` mailer locally, so **email-OTP codes land in `storage/logs` in dev**,
  not an inbox. Documented, not a bug (see `[[project_mailgun_domain_inactive]]`).

## Design

### Data model

New nullable columns on `users` (names deliberately match Fortify's
conventions for familiarity, even though the implementation is hand-rolled):

| Column | Type | Purpose |
|--------|------|---------|
| `two_factor_secret` | text, encrypted, nullable | TOTP shared secret |
| `two_factor_recovery_codes` | text, encrypted, nullable | JSON array of one-time codes |
| `two_factor_confirmed_at` | timestamp, nullable | enrollment completion marker |
| `two_factor_method` | string, nullable | chosen primary factor: `totp` \| `email` |

New table `mfa_codes` for email one-time codes (challenge + email-enrollment):

| Column | Notes |
|--------|-------|
| `id`, `user_id` (fk, cascade) | |
| `code_hash` | hashed 6-digit code (never store plaintext) |
| `purpose` | `enroll` \| `challenge` |
| `expires_at` | short TTL (10 min) |
| `consumed_at` | nullable, single-use |
| timestamps | |

A user is "MFA-enrolled" iff `two_factor_confirmed_at` is not null.

### Service layer

`App\Services\Auth\TwoFactorAuthenticationService` centralizes all crypto so
controllers stay thin and the security-sensitive logic is unit-tested in one
place:
- generate TOTP secret + provisioning QR (SVG via `bacon/bacon-qr-code`),
- verify a TOTP code (with a ±1 window for clock skew, via
  `pragmarx/google2fa`),
- generate N recovery codes, verify + consume one,
- issue an email OTP (create hashed `mfa_codes` row, dispatch notification),
  verify + consume one, with per-user rate limiting on issuance.

### Enrollment (self-service)

A new **Security** page under the profile area (Inertia Vue) lets a user:
- pick a method, scan the QR (TOTP) or send/confirm a test email code,
- confirm with a live code → sets `two_factor_confirmed_at` + `two_factor_method`,
- view/copy/download recovery codes (shown once), regenerate them.

### Login challenge

After a successful **password** attempt for a web-guard user who is enrolled,
the session is marked `mfa.pending` (user id stashed) and they are redirected
to a challenge screen **before** the session counts as MFA-passed. The
challenge screen accepts:
- the primary factor's code (TOTP input, or "email me a code" → OTP),
- a link to switch to the other method (email fallback always available since
  the address is verified),
- a "use a recovery code" affordance.

On success the session is flagged `mfa.passed` and the user proceeds to their
intended destination. Wrong codes are rejected and rate-limited.

### Enforcement (mandatory for all)

A single middleware `EnsureMfaSatisfied` (alias `mfa`) guards the
authenticated customer surface:
- **Admin guard → pass through** (Filament MFA already handled it).
- Web user **not enrolled** → redirect to the Security/enrollment page (forced
  enrollment; only enrollment, logout, and MFA routes remain reachable).
- Web user enrolled but session **not `mfa.passed`** → redirect to challenge.
- Otherwise pass.

Applied to the same gated route set as `verified.enabled` (dashboard, store,
profile, SAM favorites, etc.); the public/guest routes (order tracker,
contact, repair services, welcome) stay open.

### Recovery / ops

- `php artisan user:reset-mfa {email}` — clears a locked-out user's
  enrollment (mirrors `admin:reset-mfa`). Hard prerequisite before mandatory
  enforcement ships.
- Recovery codes are the primary self-service escape hatch.

## Data Flow

```
Login.vue → AuthenticatedSessionController@store → LoginRequest@authenticate (password ok)
    enrolled?  no  → EnsureMfaSatisfied redirects to Security page (forced enrollment)
               yes → session mfa.pending → Auth/Mfa/Challenge.vue
Challenge → verify TOTP / email-OTP / recovery code → session mfa.passed → intended()
Enrollment → TwoFactorService (secret + QR) → confirm code → two_factor_confirmed_at set + recovery codes shown
Email OTP → TwoFactorService::issueEmailCode → mfa_codes (hashed) → MfaEmailCode notification (queued, branded)
Lockout → php artisan user:reset-mfa {email} → enrollment cleared → re-enroll next login
```

## Risks / Constraints

- **Existing users predate MFA** (`two_factor_confirmed_at` null). Mandatory
  enforcement drops every current user onto forced enrollment at next login —
  intended, but a support-heavy moment. Rollout note: announce first; the
  `user:reset-mfa` command and recovery codes must both be in place before the
  `mfa` middleware is registered.
- **Admin double-challenge:** `auth.web_or_admin` routes are shared. The `mfa`
  middleware must branch on the guard and no-op for admins. Covered by an
  explicit test.
- **Email-OTP deliverability** depends on Mailgun + a running queue worker
  (same caveat as email verification). Local dev uses the `log` mailer — codes
  appear in `storage/logs`. Document in the phase and `.env.example`.
- **Brute force / email bombing:** rate-limit challenge verification and
  email-OTP issuance per user+IP (reuse `RateLimiter`, as `LoginRequest`
  already does).
- **Secrets at rest:** `two_factor_secret` and `two_factor_recovery_codes` use
  the `encrypted` cast (same posture as the admin model). Losing `APP_KEY`
  invalidates all enrollments — standard Laravel caveat, note it.
- **No remember-device** by design — stronger compliance posture, but every
  new session is challenged. Accepted by the owner.
- **Two dormant auth stacks** exist in the repo (Inertia + some Livewire auth
  views); the live customer flow is Inertia. Phase 3 confirms the Livewire
  views are dormant before ignoring them.

## Phase Table

| Phase | File | Goal | Depends on |
|-------|------|------|------------|
| 1 | `phase-1-service-layer.md` | Packages, migration (`two_factor_*` cols + `mfa_codes`), `User` casts, `TwoFactorAuthenticationService` + unit tests. No behavior change. | — |
| 2 | `phase-2-enrollment.md` | Self-service Security page: choose method, QR/email enroll, confirm, recovery codes. Not yet enforced. | 1 |
| 3 | `phase-3-challenge.md` | Login challenge flow, `mfa.pending`/`mfa.passed` session state, TOTP/email-OTP/recovery verify, branded `MfaEmailCode` notification, rate limiting. | 2 |
| 4 | `phase-4-enforcement.md` | `EnsureMfaSatisfied` (`mfa`) middleware (admin-skip, forced enrollment), route gating, `user:reset-mfa` command, rollout notes, full enforcement tests. | 3 |

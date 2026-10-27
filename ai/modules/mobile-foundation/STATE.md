# Mobile Foundation — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-26 | Owner approved 2026-10-26; starts after `nist-auth-hardening` Phase 1 |
| 1 | [server] Mobile sessions, token issuance & MFA challenge | Not Started | 2026-10-26 | After `nist-auth-hardening` Phase 1 |
| 2 | [server] Registration, MFA enrollment, verification, reset, deletion APIs | Not Started | 2026-10-26 | |
| 3 | [mobile] App foundation: tooling, tests, API client, branding, dev networking | Not Started | 2026-10-26 | Independent of server work |
| 4 | [mobile] Sign-in, MFA challenge, session storage, protected routes, sign-out | Not Started | 2026-10-26 | |
| 5 | [mobile] Registration, MFA setup, email verification, forgot password, delete account | Not Started | 2026-10-26 | |
| 6 | [mobile] Session lock, privacy screen, biometric unlock | Not Started | 2026-10-26 | Needs Apple Developer Program + Expo account |
| 7 | [both] Passkeys in the app | Not Started | 2026-10-26 | Needs `nist-auth-hardening` Phase 5 + Apple Team ID |
| 8 | [both] E2E verification, security review, compliance record | Not Started | 2026-10-26 | |

## Log

- 2026-10-26 — Plan drafted after reviewing server auth (Breeze session login,
  `LoginRequest` web→admin guard loop, `EnsureMfaSatisfied` web-group-only
  with session flag, `TwoFactorAuthenticationService`, `MfaSettingsController`,
  `RegisteredUserController`, `ProfileController::destroy`,
  `EnsureEmailIsVerifiedWhenEnabled`, Sanctum 4.3.2 config) and the fresh
  Expo SDK 58 mobile repo.
  - Key hazard: issuing tokens from a password check alone would bypass MFA
    (MFA gate is session-based, web group only). Design uses a challenge
    token with limited abilities until MFA passes.
  - Owner decisions: customers only; sign-in required; in-app registration
    (so in-app deletion); in-app MFA setup; MFA required for all mobile
    sign-ins; Balanced session policy (12 h full sign-in, 15 min lock,
    biometric unlock, 60 min server idle).
  - Research: NIST SP 800-63B-4 AAL2 (≤24 h overall SHOULD, ≤1 h idle
    SHOULD, biometric + session secret MAY re-authenticate after idle);
    NIST SP 800-171 3.5.3 / 3.1.10 / 3.1.11; Laravel 13 Sanctum mobile
    tokens and `authenticateAccessTokensUsing`; Expo SDK 58 SecureStore
    (`requireAuthentication` not in Expo Go); Expo Router `Stack.Protected`.
- 2026-10-26 — Owner decisions: authenticator app + recovery codes only
  (website and app, customers and admins); passkeys required; "adhere to
  NIST 100%". Gap review of the website found: optional customer MFA,
  no breached-password check, 120-min idle sessions with indefinite
  "Remember me", no phishing-resistant option. Owner chose a separate
  server module `nist-auth-hardening` (done first) and immediate MFA
  enforcement. Website MFA phase moved there; this module renumbered
  1–8 and gained Phase 7 (passkeys in the app).
  - Passkey research: `laravel/passkeys` v0.2.1 (first-party, pre-1.0),
    `spatie/laravel-passkeys` 1.8.1, `happenv-com/filament-passkeys` 1.4.2
    (Filament ^4.13.3, built on `laravel/passkeys`; server runs Filament
    4.15.3); `react-native-passkeys` needs a development build plus
    `apple-app-site-association` / `assetlinks.json` on cogovsupply.com.
  - Awaiting orchestrator approval of the overall plan before any code.
- 2026-10-26 — Owner approved the overall plan. Marked plan `Complete`.
  Waiting on `nist-auth-hardening` Phase 1 before Phase 1 here.

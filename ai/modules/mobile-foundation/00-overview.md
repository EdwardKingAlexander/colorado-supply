# Mobile Foundation — Overview

Cross-repository module. Phases are tagged `[server]` (this repo),
`[mobile]` (`C:\users\babyd\programming\colorado-supply-mobile`) or `[both]`.
Mobile phases follow the mobile repo's `AGENTS.md` and its checks
(`npx expo lint`, `npx tsc --noEmit`, `npm test`).

**Depends on the `nist-auth-hardening` module**, which brings the website to
full NIST authentication compliance first (authenticator-only MFA, required
MFA, breached-password checks, session limits, website passkeys). This
module reuses those rules for the app.

## Problem Statement

Colorado Supply is getting a customer-only iOS and Android app (React Native /
Expo SDK 58). Before any shopping feature can be built, the app needs a
secure way to sign customers in, keep them signed in within federal
cybersecurity limits, and talk to this server's API.

Today the server cannot support that:

- **No mobile login.** Customer auth is Breeze session/cookie login only.
  `User` does not use `HasApiTokens` and nothing calls `createToken()`.
- **MFA would be bypassed.** `EnsureMfaSatisfied` runs only in the `web`
  middleware group and records success in the session (`mfa.passed`). A
  token issued straight from a password check would skip MFA entirely.
- **Session policy is browser-shaped.** There is no server-enforced
  re-authentication or idle limit for a long-lived device credential.

The mobile repo is a fresh Expo template with sample screens and no API
client, auth, tests or branding.

## Owner Decisions (2026-10-26)

| Topic | Decision |
|---|---|
| Compliance target | **Adhere to NIST 100%** (SP 800-171 and SP 800-63B-4 AAL2) for authentication and sessions. |
| Audience | Customers only. No staff/admin features in the app, ever. |
| Guest access | **Sign-in required.** The app opens to sign-in; nothing is visible until authenticated. |
| Sign-up | **In-app registration.** Apple then requires in-app account deletion (App Review Guideline 5.1.1(v)), so deletion is in scope. |
| MFA setup | **In the app** (not website-only). |
| MFA methods | **Authenticator app (TOTP) + recovery codes only**, website and app (email codes removed in `nist-auth-hardening`). |
| MFA policy | **Required for every sign-in**, website and app. |
| Session policy | **Balanced:** full sign-in (password + MFA, or passkey) at least every **12 hours**; app locks after **15 min** idle or when backgrounded and unlocks with Face ID / fingerprint; server ends the session after **1 hour** without use unless biometric unlock renews it. |
| Phishing-resistant option | **Passkeys** in the app (Phase 7) and on the website (`nist-auth-hardening`). |

### Compliance basis

- **NIST SP 800-171** — 3.5.3 (MFA for network access to non-privileged
  accounts), 3.1.10 (session lock with pattern-hiding after inactivity),
  3.1.11 (terminate session after a defined condition).
- **NIST SP 800-63B-4, AAL2** — overall re-authentication SHOULD be ≤ 24 h
  (we use 12 h); inactivity timeout SHOULD be ≤ 1 h; after the inactivity
  timeout the verifier MAY accept a biometric comparison together with the
  session secret; verifiers SHALL offer a phishing-resistant option
  (passkeys); email is not an allowed authenticator. Biometrics are never an
  authenticator on their own.
- Code can make authentication and sessions compliant; company-wide 800-171
  compliance (110 requirements, many procedural) is confirmed by the
  owner's assessor against the System Security Plan.

## Constraints

- **Customers only.** Mobile auth queries the `User` model exclusively.
  Admins (`Admin` model, `admin` guard) can never obtain a mobile token.
- **Server is the source of truth for every timer.** The phone cannot extend
  a session by tampering with its clock or storage.
- **No website behavior changes in this module.** All website auth changes
  live in `nist-auth-hardening`. Shared logic is extracted into
  services/actions, not duplicated, and existing web tests must stay green.
- **Reuse existing building blocks:** `TwoFactorAuthenticationService`
  (TOTP, recovery codes), `Password::defaults()` (breached-password check
  from `nist-auth-hardening`), the `Registered` event,
  `EmailVerificationSettings`, `verified.enabled`, `LoginRequest`
  rate-limit pattern, the passkey library chosen in `nist-auth-hardening`.
- **Expo:** use `npx expo install`; consult SDK 58 versioned docs before
  touching Expo APIs. Biometric-gated SecureStore,
  `expo-local-authentication` and passkeys do **not** work in Expo Go, so a
  development build (EAS) is required from Phase 6 on.
- No stubs/TODOs; tests mandatory; one phase at a time, per
  `PROJECT_CANNON.md`.

## Architecture (textual)

```
                         [server]  /api/v1/mobile/*
Mobile app                                                   Database
──────────                                                   ────────
Sign-in screen ── POST auth/login {email,password,device} ─► verify password (User only, throttled)
                ◄── challenge_token (10 min, ability mfa:challenge or mfa:enroll)

MFA screen ────── POST auth/mfa/verify  {code|recovery_code}
Enroll screens ── POST auth/mfa/enroll/start|confirm
Passkey button ── POST auth/passkey/options|verify (user verification required)
                ◄── access_token (ability app)          ──►  mobile_sessions row
                    unlock_secret (one-time display)         (authenticated_at,
                                                              expires_at = +12h,
                                                              unlock_secret_hash)
                                                             personal_access_tokens
                                                              .mobile_session_id

Every API call ── Authorization: Bearer <access_token>
                  Sanctum::authenticateAccessTokensUsing:
                    reject if session revoked / past 12h / token idle > 60 min

Lock screen ───── Face ID reads unlock_secret from SecureStore
                  (requireAuthentication) ── POST auth/unlock
                ◄── fresh access_token (only while session < 12h old)

Sign out ──────── POST auth/logout  → revoke session + all its tokens
Delete account ── DELETE account {password} → same as web ProfileController::destroy
```

Mobile app structure (Expo Router):

```
src/app/_layout.tsx        SessionProvider + Stack.Protected guards + LockGate
src/app/(auth)/…           sign-in, register, mfa, mfa-setup, verify-email, forgot-password
src/app/(app)/…            tabs: Home (placeholder for catalog module), Account, Passkeys
src/lib/api/               typed fetch client, error mapping, endpoint functions
src/lib/session/           secure storage, session state machine, idle/lock timers
```

## Data Flow

```
Login:    password ok → challenge_token
          ├─ user enrolled  → mfa/verify (TOTP | recovery code)
          └─ not enrolled   → mfa/enroll/start → confirm → recovery codes shown once
          or passkey (user-verified) → straight to session
          → mobile_session created (expires_at = now + 12h)
          → access_token + unlock_secret → SecureStore
          → email unverified & verification enabled → verify-email screen (resend; poll /me)

Use:      request → token valid? session active? idle ≤ 60 min? → 200, last_used_at = now
          else 401 {code: session_expired | reauth_required}

Lock:     app backgrounded or 15 min no touches → privacy cover + lock screen
          unlock → biometric prompt → unlock_secret → auth/unlock
          session < 12h → new access_token; else → full sign-in

Logout / delete / 12h reached / server revocation → SecureStore wiped → sign-in
```

## Phases

| # | Tag | Phase | File |
|---|-----|-------|------|
| 1 | [server] | Mobile sessions, token issuance & MFA challenge | `phase-1-server-mobile-sessions.md` |
| 2 | [server] | Registration, MFA enrollment, email verification, password reset, account deletion APIs | `phase-2-server-account-apis.md` |
| 3 | [mobile] | App foundation: tooling, tests, API client, branding, dev networking | `phase-3-mobile-foundation.md` |
| 4 | [mobile] | Sign-in, MFA challenge, session storage, protected routes, sign-out | `phase-4-mobile-sign-in.md` |
| 5 | [mobile] | Registration, MFA setup, email verification, forgot password, delete account | `phase-5-mobile-account-flows.md` |
| 6 | [mobile] | Session lock, privacy screen, biometric unlock (development build) | `phase-6-mobile-session-lock.md` |
| 7 | [both] | Passkeys in the app (domain association, passkey API, screens) | `phase-7-passkeys-in-app.md` |
| 8 | [both] | End-to-end verification on iOS + Android, security review, compliance record | `phase-8-e2e-verification.md` |

Order: `nist-auth-hardening` Phase 1 before Phase 1 here. Phase 3 does not
depend on any server work and may run in parallel if the owner approves.
Phase 7 also needs `nist-auth-hardening` Phase 5 (website passkeys).

## Risks

- **Biometric unlock and passkeys need development builds.** Expo Go cannot
  use them. iOS builds require an Apple Developer Program membership;
  development is on Windows, so iOS builds go through EAS. See
  Prerequisites.
- **Biometric enrollment changes invalidate the unlock secret** (SecureStore
  behavior). Handled by falling back to full sign-in — must be tested.
- **Devices with no biometrics / passcode.** Policy: unlock falls back to full
  sign-in. Confirmed in Phase 6.
- **Shared-route regression.** Extracting registration/MFA/deletion logic out
  of web controllers could change web behavior. Mitigated by keeping existing
  `tests/Feature/Auth/*` green and adding web regression assertions.
- **Local dev networking.** `colorado-supply.test` resolves only on the PC; a
  phone cannot reach it. Phase 3 picks LAN IP or `herd share` (Expose) and
  documents it. Passkeys need the real HTTPS domain (Phase 7).
- **App Store review** requires a working demo account with MFA (reviewers
  must be able to sign in). Phase 8 prepares a review account procedure.
- **Token table growth.** Expired sessions/tokens pruned daily
  (`sanctum:prune-expired` + mobile session prune).

## Open Decisions

None. All decided 2026-10-26 (see Owner Decisions and STATE.md log).

## Prerequisites (owner, before Phase 6)

- Apple Developer Program membership (needed for any iOS development build)
  and its Team ID (needed for passkeys in Phase 7).
- Expo account (free) for EAS builds.
- Google Play Console account is not needed until store submission.

## Out of Scope

- Product catalog, cart, quotes, orders, checkout, notifications (later
  modules built on this foundation).
- Website UI for listing/revoking mobile devices (follow-up).
- Website auth changes (all in `nist-auth-hardening`).
- Push notifications, offline mode, store submission.

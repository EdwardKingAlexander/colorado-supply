# NIST Authentication Hardening — Overview

Server-only module (website customer portal + Filament admin panel). It
must be completed before `mobile-foundation` reuses its rules.

## Problem Statement

The owner requires 100% adherence to NIST SP 800-171 and SP 800-63B-4 (AAL2)
for authentication and sessions. A gap review on 2026-10-26 found the
website falls short in five places:

| # | Gap | NIST rule |
|---|---|---|
| 1 | Email codes accepted as a second factor (customers and admins), including a hidden email fallback for authenticator users | 800-63B: email SHALL NOT be used as an out-of-band authenticator |
| 2 | Customer MFA optional (`MFA_REQUIRED=false`) | 800-171 3.5.3: MFA for network access to non-privileged accounts |
| 3 | No breached-password check; no explicit password policy (Laravel default min 8) | 800-63B: verifiers SHALL compare new passwords against a blocklist of compromised values |
| 4 | 120-minute idle sessions; "Remember me" keeps sessions alive indefinitely; no overall re-authentication limit | 800-63B AAL2: overall ≤ 24 h, idle ≤ 1 h; 800-171 3.1.11 session termination |
| 5 | No phishing-resistant option | 800-63B-4 AAL2: verifiers SHALL offer at least one phishing-resistant option |

## Owner Decisions (2026-10-26)

- Authenticator app (TOTP) + recovery codes are the only second factor, for
  customers **and** admins. Email codes removed everywhere.
- MFA required for every customer, **enforced immediately** when Phase 2
  ships (no announcement grace period).
- Session limits match the mobile app: idle 60 min, full re-sign-in every
  12 h, no "Remember me".
- Passkeys built now (not deferred), for customers and admins.
- Separate module, completed before `mobile-foundation`.

## Constraints

- Admin MFA stays Filament-native (`AppAuthentication`); customer MFA stays
  the custom Breeze build (`TwoFactorAuthenticationService`).
- No one is locked out: every change that removes a factor or forces
  enrollment has a tested recovery path (`user:reset-mfa`,
  `admin:reset-mfa --force`, recovery codes).
- Existing web tests stay green except where behavior intentionally changes;
  those tests are updated in the same phase.
- No stubs/TODOs; one phase at a time per `PROJECT_CANNON.md`;
  `php artisan test`, `vendor/bin/pint --dirty`, `npm run build` clean.

## Architecture (textual)

```
Customer login (Breeze)                 Admin login (Filament)
  password (breached check on set)        password (breached check on set)
  → TOTP / recovery code (required)       → AppAuthentication TOTP (required)
  → or passkey (user-verified)            → or passkey (Filament plugin)
        │                                        │
        └──────── EnforceSessionLimits middleware ┘
                  idle  > 60 min → logout
                  since auth > 12 h → logout, full sign-in
```

## Phases

| # | Phase | File |
|---|-------|------|
| 1 | Authenticator-app-only MFA (customers + admin panel) | `phase-1-authenticator-only-mfa.md` |
| 2 | Required MFA for every customer | `phase-2-required-customer-mfa.md` |
| 3 | Password policy with breached-password check | `phase-3-password-policy.md` |
| 4 | Session limits: 60-min idle, 12-h re-authentication, no "Remember me" | `phase-4-session-limits.md` |
| 5 | Passkeys on the website (customers + admins) | `phase-5-website-passkeys.md` |
| 6 | Verification and compliance record | `phase-6-verification-compliance.md` |

Phases 1–4 are independent of each other except that Phase 2 follows
Phase 1 (forced enrollment must offer the authenticator app only).

## Risks

- **Immediate enforcement** sends every unenrolled customer to setup at
  next login; support load is expected. Phase 2 makes the setup screen and
  recovery-code saving clear.
- **Admin lockout** if an admin's only factor is email. Phase 1 lists them
  before deploy.
- **Breached-password API** (Have I Been Pwned range API, k-anonymity) is an
  external call; outage behavior must be decided in Phase 3.
- **Passkey library maturity:** `laravel/passkeys` is pre-1.0. Phase 5
  evaluates it against `spatie/laravel-passkeys` before committing.
- **Relying party ID** (`cogovsupply.com`) is effectively permanent once
  passkeys exist; decided in Phase 5.
- `customer-mfa` and `admin-account-security-complete` modules introduced
  the code being changed; their STATE logs get a note.

## Out of Scope

- Mobile app (all in `mobile-foundation`).
- Company-wide 800-171 controls that are procedural (audit, incident
  response, training); the owner's assessor confirms those against the
  System Security Plan.

# NIST Authentication Hardening — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-26 | Owner approved 2026-10-26 |
| 1 | Authenticator-app-only MFA (customers + admin panel) | Awaiting Validation | 2026-10-26 | Owner: run `mfa:email-method-report` on production; cleanup step open |
| 2 | Required MFA for every customer | Not Started | 2026-10-26 | Enforced immediately on deploy |
| 3 | Password policy with breached-password check | Not Started | 2026-10-26 | |
| 4 | Session limits: 60-min idle, 12-h re-auth, no "Remember me" | Not Started | 2026-10-26 | |
| 5 | Passkeys on the website (customers + admins) | Not Started | 2026-10-26 | Library choice + RP ID need owner approval |
| 6 | Verification and compliance record | Not Started | 2026-10-26 | |

## Log

- 2026-10-26 — Module created during `mobile-foundation` planning after the
  owner required 100% NIST adherence. Gap review found: email-code MFA
  (customers and Filament admins), optional customer MFA, no
  breached-password check, 120-min idle sessions with indefinite
  "Remember me", no phishing-resistant option.
  - Owner decisions: authenticator app + recovery codes only (customers and
    admins); MFA required, enforced immediately; session limits match the
    app (60 min idle, 12 h); passkeys now; separate module done first.
  - Phase 1 moved here from `mobile-foundation` (where it was drafted as
    that module's Phase 1).
  - Local DB: 0 MFA-enrolled customers; production counts unknown.
  - Awaiting orchestrator approval of the overall plan before any code.
- 2026-10-26 — Owner approved the overall plan (and `mobile-foundation`'s).
  Marked plan `Complete`. Phase 1 scope re-checked against current code:
  still accurate. Added admin detail: Filament stores email MFA in
  `Admin.has_email_authentication` (interface `HasEmailAuthentication`);
  authenticator in `app_authentication_secret`. `app/Models/Admin.php` has
  uncommitted owner changes — Phase 1 builds on them. Phase 1 set to
  `Awaiting Phase Approval`.
- 2026-10-26 — Owner approved Phase 1. Set `In Progress`.
- 2026-10-26 — Phase 1 implemented; awaiting validation.
  - **1a:** `mfa:email-method-report [--list]` counts customers with confirmed
    email-method MFA and admins whose only factor was Filament email codes.
    Owner must still run it on production (see deploy notes below).
  - **1b (customers):** enrollment accepts `totp` only; confirm accepts only a
    pending TOTP secret (stale email-enrollment sessions rejected). Challenge
    logic moved to `TwoFactorAuthenticationService::verifyChallenge()` (shared
    with mobile later): recovery code, TOTP, or an email code **only** for a
    legacy email-method user. The hidden email fallback for TOTP users is gone;
    `mfa.challenge.email` returns 403 unless the user is legacy. Vue: email
    option and texts removed; legacy users see "Set up an authenticator app to
    continue".
  - **1b-admin:** `EmailAuthentication` removed from `AdminPanelProvider`;
    `HasEmailAuthentication` + methods + cast removed from `Admin`;
    `admin:reset-mfa` no longer toggles email; `AdminFactory::withAppAuthentication()`
    added and 6 admin test files switched to it. Email-only admins are sent to
    authenticator setup by Filament at next login (tested).
  - **1c (deviation, deliberate):** production count unknown, so the
    transition path was built now rather than gated on 1a: legacy email users
    pass the challenge once (email or recovery code), then `EnsureMfaSatisfied`
    holds them on profile setup (JSON 423) until they confirm TOTP, which
    switches `two_factor_method` to `totp` and deletes their email codes.
    If the report shows zero, nothing ever takes this path.
  - **Kept for the cleanup step (not dead code yet):** `mfa_codes` table,
    `MfaEmailCode` notification, service email-code methods,
    `mfa.challenge.email` route, `admins.has_email_authentication` column.
    Cleanup = remove all of these once the report shows 0 / 0 on production;
    tracked as an open item for Phase 6.
  - Customer/admin email-code notices to affected users: not sent (owner
    action, only if the report shows anyone).
  - Notes added to `customer-mfa` and `admin-account-security-complete` STATE.
  - Verification: Phase 1 tests 136 passed (627 assertions). Full suite
    (`--parallel`): 1528 passed, 5 skipped, 1 failed —
    `FetchSamOpportunitiesJobPersistenceTest` "skips records with no
    notice_id", which passes in isolation with and without this phase's
    changes (parallel-run flake, unrelated). `pint --dirty` fixed import order
    in `AdminFactory`; `npm run build` passed.
  - Deploy notes: run `php artisan mfa:email-method-report --list` on
    production after deploy; if anyone is listed, owner approves a notice
    email. No `.env` changes needed for this phase.

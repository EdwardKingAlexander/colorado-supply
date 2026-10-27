# NIST Authentication Hardening — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-26 | Owner approved 2026-10-26 |
| 1 | Authenticator-app-only MFA (customers + admin panel) | Awaiting Phase Approval | 2026-10-26 | 1a needs owner to run report on production |
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

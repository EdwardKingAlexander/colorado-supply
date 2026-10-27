# Phase 6 — Verification and Compliance Record

## Goal

Prove every gap from the overview is closed on a production-like
environment and leave a compliance record the owner can hand to an
assessor.

Depends on: Phases 1–5.

## Sub-phases

### 6a. Manual verification checklist
- `ai/modules/nist-auth-hardening/VERIFICATION.md`: customer and admin
  walkthroughs (no email-code option anywhere; unenrolled customer forced
  to setup; breached password rejected; idle 60 min and 12 h logouts using
  temporarily shortened local config; no remember-me; passkey register and
  sign-in on desktop and phone), each with date and pass/fail.

### 6b. Security review
- Review the module's diff for: rate limits on MFA and passkey endpoints,
  secrets in logs/Sentry, session fixation on every login path, admin vs.
  customer guard separation, recovery paths documented.

### 6c. Compliance record
- `ai/modules/nist-auth-hardening/COMPLIANCE.md`: table mapping NIST SP
  800-171 3.5.3, 3.1.10, 3.1.11 and SP 800-63B-4 (password rules,
  authenticator types, AAL2 reauthentication, phishing-resistant option,
  no email OTP) to the exact code, config and tests. Includes the 3.1.10
  web reasoning from Phase 4 and the breached-password outage decision
  from Phase 3. `mobile-foundation` Phase 8 extends this record for the app.

### 6d. Operations notes
- Production `.env` values the owner must set (`MFA_REQUIRED=true`,
  `SESSION_LIFETIME=60`), deploy order, and the support script for
  customers who lose their authenticator.

## Success Criteria

- All checklist items pass; no open high-severity findings.
- Owner has `COMPLIANCE.md` and deploy notes.
- `php artisan test`, `vendor/bin/pint --dirty`, `npm run build` clean.

STOP. Await phase approval.

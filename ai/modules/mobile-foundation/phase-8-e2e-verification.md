# Phase 8 — [both] End-to-End Verification, Security Review, Compliance Record

## Goal

Prove the whole foundation works together on real iOS and Android devices
against the server, review it for security gaps, and leave a written record
mapping the implementation to the NIST controls for the owner's compliance
file.

Depends on: Phases 1–7 and the completed `nist-auth-hardening` module.

## Sub-phases

### 7a. End-to-end test script
- `ai/modules/mobile-foundation/E2E-CHECKLIST.md` — step-by-step manual
  script run on one iPhone and one Android phone, each step with
  pass/fail and date: register → MFA setup → verify email → sign out →
  sign in → MFA → lock/unlock → idle > 60 min unlock → 12 h expiry
  (server config temporarily shortened in local env only) → forgot password →
  delete account; admin credentials rejected.

### 7b. Security review
- Review both repos' changes for: token leakage in logs/error reporting
  (Sentry scrubbing of `Authorization`, `unlock_secret`, codes), rate limits
  on every unauthenticated endpoint, account enumeration, admin exclusion,
  storage options, privacy cover, HTTPS-only API URL in preview/production
  builds.
- Findings fixed or recorded with owner decision.

### 7c. Compliance record
- `ai/modules/mobile-foundation/COMPLIANCE.md` — table mapping NIST SP
  800-171 3.5.3, 3.1.10, 3.1.11 and SP 800-63B-4 AAL2 reauthentication
  requirements to the exact server/mobile code and tests that implement
  them, plus open items (passkeys follow-up).

### 7d. Store-readiness notes
- App Review demo account procedure (customer account with TOTP and how the
  reviewer gets codes), privacy nutrition label inputs (data collected:
  name, email), account-deletion location for reviewers.

## Files to Create/Modify

- Create: `E2E-CHECKLIST.md`, `COMPLIANCE.md`, `STORE-READINESS.md` in this
  module directory; any fixes from 7b in either repo.
- Modify: `config/sentry.php` or `before_send` scrubbing if 7b finds gaps;
  mobile README.

## Tests

- All server and mobile suites green.
- Added regression tests for any 7b finding.

## Success Criteria

- Every E2E checklist step passes on both platforms.
- No open high-severity security findings.
- Owner has `COMPLIANCE.md` and `STORE-READINESS.md`.
- `php artisan test`, `vendor/bin/pint --dirty`, `npx expo lint`,
  `npx tsc --noEmit`, `npm test` clean.

STOP. Await phase approval.

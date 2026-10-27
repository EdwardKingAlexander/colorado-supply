# Phase 4 — Testing & Hardening

## Goal

Confirm MFA, security headers, and HTTPS enforcement work together without
regressions, and document the operational runbook for MFA recovery.

## Work

- **Full regression run**: `php artisan test` — compare against the
  documented pre-existing baseline (as of 2026-07-04:
  `CustomerResourceGooglePlacesTest` and a Filament/Blade memory-exhaustion
  crash; RBAC and ComplianceAuditTool issues were fixed earlier this
  session). Confirm no new failures.
- **End-to-end manual check**: enroll MFA as a test admin, log out, log
  back in with the second factor, confirm the security headers are present
  throughout, confirm the admin panel is fully functional (not just
  "loads" — exercise a create/edit/delete flow on at least one resource).
- **Document the MFA recovery runbook** (in this module's `STATE.md` log,
  not a new doc file per this project's no-unrequested-docs convention):
  exact steps to recover a locked-out admin, so this isn't tribal knowledge
  living only in whoever implemented it.
- `vendor/bin/pint --dirty` across all module-touched files.

## Success Criteria

- All new tests from Phases 1-3 pass together.
- No new full-suite regressions.
- MFA recovery runbook documented and test-verified (actually run the
  recovery steps once against a locked-out test admin, don't just describe
  them).
- `STATE.md` updated to `Awaiting Validation`.

**STOP. Await phase approval.**

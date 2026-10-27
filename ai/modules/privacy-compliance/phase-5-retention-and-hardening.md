---
plan: privacy-compliance
phase: 5
title: Retention, Hardening & Compliance Mapping
status: not_started
last_updated: 2026-07-10
depends_on: [phase-1, phase-2, phase-3, phase-4]
checkpoint: none
---

# Phase 5 — Retention, Hardening & Compliance Mapping

## Goal

Close the loop: automated retention enforcement, abuse hardening, the manual
GA4-property checklist, and a final requirements-to-implementation compliance
matrix that demonstrates (to management, customers, or a regulator) how each
obligation is met.

## Success Criteria

- Scheduled jobs purge expired data per `config/privacy.php` retention map and
  are covered by tests.
- A written GA4 property checklist exists (console settings that cannot be
  done in code).
- `compliance-matrix.md` maps every baseline obligation → implementation →
  test → status.
- Full regression: `php artisan test` no new failures vs baseline;
  `vendor/bin/pint --dirty` clean; browser verification matrix re-run.

## Sub-Phases

### 5.1 Retention jobs (`retention` checkpoint)
- `app/Jobs/Privacy/PurgeExpiredPrivacyData.php` (or artisan command
  `privacy:purge`) scheduled daily in `routes/console.php`:
  - unverified DSRs older than 30 days,
  - completed DSRs + export artifacts past 24 months,
  - consent receipts past the proof window (5 years) — keep latest per visitor,
  - expired export zips on disk.
- Each purge run logs counts to the audit log.

### 5.2 Abuse & security hardening (`hardening` checkpoint)
- Rate limits reviewed: consent endpoint, DSR intake, verification endpoint
  (signed + throttled + single-use).
- Export downloads: signed URLs, short TTL, deleted after download window.
- PII minimization audit of new tables (ip_hash only, truncated user agents,
  hashed verification tokens) — confirm no raw PII beyond necessity.
- Confirm no analytics on authenticated SAM/federal-workspace pages (or
  documented as consent-gated + data-minimized) per the contractor posture.

### 5.3 GA4 manual configuration checklist (`ga4-checklist` checkpoint)
- Document in `ga4-property-checklist.md`: set data retention to 14 months,
  disable Google Signals (or document the sharing disclosure it triggers),
  disable ads personalization for restricted states, verify Consent Mode
  status in DebugView, IP handling note.

### 5.4 Compliance matrix + final validation (`matrix` checkpoint)
- `compliance-matrix.md`: rows = obligations (GPC honoring, opt-out link,
  privacy policy content items, DSR rights incl. appeal, deadlines, consent
  records, retention); columns = implementing artifact, test reference,
  status, counsel-review flag.
- Re-run the Phase 2 browser matrix + a full DSR dry run; record in STATE log.
- Update `MEMORY`/project docs pointer so future work knows the privacy
  registry is the source of truth for any new cookie or tracker.

## Files to Create/Modify

| File | Action |
|---|---|
| `app/Jobs/Privacy/PurgeExpiredPrivacyData.php` (or `app/Console/Commands/PrivacyPurge.php`) | create |
| `routes/console.php` | modify (schedule) |
| `ai/modules/privacy-compliance/ga4-property-checklist.md` | create |
| `ai/modules/privacy-compliance/compliance-matrix.md` | create |
| misc: throttle/config touch-ups surfaced by 5.2 | modify |

## Tests to Write

- `tests/Feature/Privacy/RetentionPurgeTest.php` — time-travel purge behavior
  for each retention rule; latest-consent-per-visitor preserved.
- `tests/Feature/Privacy/ExportDownloadSecurityTest.php` — expired/forged
  signed URLs rejected; artifact cleanup.

## Risks / Blockers

- GA4 console steps are manual and unverifiable from code — checklist must be
  executed by a human with property access before production sign-off.
- Retention deletion must never race an open DSR (purge excludes non-terminal
  statuses — asserted in tests).

## Definition of Done

Purge scheduled + tested; hardening items closed; GA4 checklist and compliance
matrix committed; full validation pass recorded in STATE.md.

STOP. Await phase approval.

---
plan: dibbs-rfq-tracking
phase: 7
title: Hardening and operational readiness
status: completed
last_updated: 2026-10-04
depends_on: [phase-2, phase-3, phase-4, phase-5, phase-6]
checkpoint: 7.5-full-validation
---

# Phase 7 — Hardening and operational readiness

## Goal

Make the pipeline something the business can rely on daily: visible health,
bounded data growth, and documented recovery.

## Sub-phases

### 7.1 Health visibility
Steps:
1. Ingestion status panel: last file date/source/rows/errors, next scheduled
   run, consecutive failures, "automation blocked" banner with upload link.
2. Alert when no new file has been imported for 2 business days.
DoD: tests for stale-ingestion alert and status panel states.

### 7.2 Retention
Steps:
1. Prune RFQs closed > N days (config, default 180) **unless** tracked
   (any status other than `new`) or matched; prune raw files after import.
2. Scheduled prune command with dry-run option.
DoD: prune tests (tracked rows kept).

### 7.3 Performance
Steps:
1. Load a full real day's file; measure import time and list/filter query times.
2. Add indexes or chunking where > targets (import < 60 s; list < 500 ms).
DoD: measurements recorded in `STATE.md`.

### 7.4 Runbook
Steps:
1. `ai/modules/dibbs-rfq-tracking/RUNBOOK.md`: manual upload procedure, what
   alerts mean, how to re-import a day, how to disable automation (config flag).
DoD: runbook reviewed by the user.

### 7.5 Full validation
Steps:
1. Full test suite, pint, and one end-to-end business day: download/upload →
   matches → digest → worksheet → export.
DoD: results logged in `STATE.md`.

## Files

Create: prune command, status widget, runbook. Modify: scheduler, config.

## Tests

Prune, stale alert, status widget. Full suite + pint.

## Success criteria

- A full business day runs end to end without manual intervention (or with
  the documented upload step if automation is blocked).

## Risks / blockers

- DB growth if retention misconfigured; dry-run first.

STOP. Await phase approval.

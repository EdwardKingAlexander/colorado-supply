---
plan: will-call-notifications
phase: 7
title: Testing, accessibility, browser QA and deploy runbook
status: in_progress
last_updated: 2026-10-12
depends_on: [phase-1, phase-2, phase-3, phase-4, phase-5, phase-6]
checkpoint: runbook
---

# Phase 7 — Testing, Accessibility, Deploy

## Goal

Prove the whole flow end to end at phone and desktop sizes and leave a
runbook so production setup is a checklist, not guesswork.

## Success criteria

- Full suite green (`php artisan test --parallel`), pint clean on changed
  files, `npm run build` OK.
- Playwright: buyer places Will Call order → admin sees live bell/toast →
  admin marks ready → buyer sees live toast and the pickup code → admin marks
  picked up. At 320/390/1280px, axe clean, 44px targets, no sideways scroll.
- `RUNBOOK.md` complete and followed once on staging/production by the owner.

## Sub-phases

### 7.1 — Browser tests — `checkpoint: playwright`
- Self-cleaning fixture (pattern from `tests/Browser/support/
  product-page-fixture.php`): token-scoped buyer, admin, pickup location.
- Specs: checkout will call; live bell both sides (Reverb started by the
  Playwright webServer config); notifications page; push opt-in states with
  granted/denied permissions.

### 7.2 — Accessibility and hardening — `checkpoint: hardening`
- Toast live region, bell `aria-expanded`, unread conveyed in text not colour
  only, focus management on dropdown.
- Rate limits on notification and push/SMS endpoints; N+1 check on the
  bell/index (query count test).
- Failure modes: Reverb down, Twilio errors, push 410 — logged, no user-
  facing errors.

### 7.3 — Runbook — `checkpoint: runbook`
- `RUNBOOK.md` in this module: Forge Reverb daemon (`php artisan reverb:start`)
  and Nginx websocket proxy; env vars (REVERB_*, VITE_REVERB_*, VAPID_*,
  TWILIO_*, SMS_ENABLED); queue worker restart; scheduler for reminders and
  pruning; Twilio webhook URL; A2P registration steps; how to verify each
  channel after deploy; rollback (set BROADCAST_CONNECTION=log, SMS_ENABLED
  =false — everything falls back to email + polling bell).

### 7.4 — Closeout — `checkpoint: closeout`
- STATE.md updated, follow-ups listed, `remaining.md` updated.

STOP. Await phase approval.

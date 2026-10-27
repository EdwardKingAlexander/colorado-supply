---
plan: laravel-13-upgrade
phase: 1
title: Preflight audit & dependency pinning
status: not_started
last_updated: 2026-07-09
depends_on: []
checkpoint: none
---

# Phase 1 — Preflight Audit & Dependency Pinning

## Goal

Lock down the two package pins the user approved (`spatie/laravel-permission`
`^6.24`, `spatie/laravel-activitylog` `^4.0`), confirm production's real DB
driver, and run a `composer update --dry-run` against the Laravel 13 target
to get a real, current conflict list before touching any code — this
plan's package-compatibility research was done via packagist queries during
planning and may already be stale by the time this phase executes.

## Steps

1. Confirm production's DB driver (Forge dashboard or ask the user directly)
   — working assumption is MySQL/MariaDB. Record the answer in this phase's
   log entry in `STATE.md` regardless of outcome, since the `00-overview.md`
   risk section flags this as assumed-not-confirmed.
2. In `composer.json`:
   - Change `"spatie/laravel-permission": "*"` to
     `"spatie/laravel-permission": "^6.24"`.
   - Confirm `"spatie/laravel-activitylog": "^4.0"` already reads that way
     (it does per current `composer.json`) — no change needed, just verify
     it isn't touched later.
3. Run `composer update laravel/framework:^13.0 laravel/tinker:^3.0
   laravel/boost:^2.0 pestphp/pest:^4.0 --dry-run` (do not apply yet) and
   read the full output. This surfaces any of the ~15 unverified packages
   from `00-overview.md`'s research table that actually conflict, rather
   than guessing.
4. If the dry run shows conflicts, identify which package is blocking and
   whether it needs its own version bump, a pin, or a suitable replacement.
   Do not silently let `composer update` (no package args) resolve
   `spatie/laravel-permission` or `spatie/laravel-activitylog` past their
   pins to satisfy a conflict — if one of those two is the actual blocker,
   stop and flag it back to the user rather than un-pinning it unilaterally.
5. Update `php` constraint in `composer.json` from `^8.2` to `^8.3`.
6. Document the dry-run output (or a summary of it) in this phase's
   `STATE.md` log entry so Phase 2 doesn't have to re-derive it.

## Files to Create/Modify

- `composer.json` — pin `spatie/laravel-permission` to `^6.24`, bump `php`
  to `^8.3`. Do not touch `laravel/framework` or other version constraints
  yet — that's Phase 2.

## Tests

No new tests in this phase — it's audit-only plus a composer.json edit.
Run `composer validate` after editing `composer.json` to confirm it's still
well-formed.

## Success Criteria / Definition of Done

- Production DB driver confirmed and recorded.
- `spatie/laravel-permission` pinned to `^6.24` in `composer.json`.
- `php` constraint bumped to `^8.3` in `composer.json`.
- A `composer update --dry-run` conflict list exists and has been read —
  either "no conflicts beyond the known package list" or a documented list
  of additional packages needing attention in Phase 2.
- `composer validate` passes.

**STOP. Await phase approval.**

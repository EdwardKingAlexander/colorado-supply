---
plan: laravel-13-upgrade
phase: 6
title: Deployment readiness
status: in_progress
last_updated: 2026-07-09
depends_on: [phase-5]
checkpoint: tag-and-commit
---

# Phase 6 — Deployment Readiness

## Goal

Confirm production (Laravel Forge) is actually ready for this upgrade, and
establish a concrete rollback plan before merging/deploying — a framework
major-version bump is not a change to deploy with only "tests passed" as
the safety net.

## Steps

1. Re-confirm the Forge production server's live PHP version is `^8.3`
   (user confirmed this during planning, but re-verify at deploy time in
   case the server config changed since — e.g. `php -v` via Forge's SSH
   access or the Forge dashboard's PHP version selector for the site).
2. Confirm Forge's deployment script doesn't hardcode a PHP binary path tied
   to the old version (check the site's deploy script in the Forge
   dashboard, or `.deploy.php`/deployment config if one exists in-repo).
3. Tag the pre-upgrade commit (`git tag pre-laravel-13-upgrade`) before
   merging this plan's changes to `main`, so a rollback has an exact
   reference point. Confirm with the user before pushing the tag (tagging
   itself is low-risk/reversible, but push it deliberately, not as a side
   effect of another command).
4. Write out the rollback procedure explicitly (as a `STATE.md` log entry,
   not a separate doc per project convention): what to `git revert`/
   `checkout`, whether `composer.lock`/`package-lock.json` need to roll
   back together with code, and whether any database migrations in this
   plan's diff need a down-migration (expected: none, since this is a
   dependency/framework upgrade, not a schema change — confirm no migration
   files were added across Phases 1–5).
5. If a staging environment exists on Forge separate from production,
   deploy there first and run the same manual smoke tests as Phase 3/4/5
   against staging before touching production. If no staging environment
   exists, flag this explicitly to the user as a real risk rather than
   deploying straight to production silently.
6. Deploy to production (only after explicit user go-ahead — this is a
   production deployment, not a reversible local action, per the standing
   "confirm before risky actions" guidance).
7. **Explicitly verify the deploy script clears compiled views (and
   OPcache if enabled) after the deploy.** Phase 4 found and root-caused a
   real bug where stale compiled Blade views survived a `filament/filament`
   version bump and rendered a completely broken, invisible MFA
   code-entry UI (six `0x0`-sized digit boxes) — `php artisan view:clear`
   fixed it locally, but this must not be assumed to happen automatically
   in production. Check Forge's actual deploy script for this site (most
   Forge zero-downtime scripts include an `artisan optimize:clear` or
   equivalent step, but confirm it explicitly rather than assuming) before
   considering the deploy safe.
8. **Verify production's `APP_URL` and/or `SANCTUM_STATEFUL_DOMAINS` match
   its real serving domain.** Phase 5 found — and fixed in-repo via
   `$middleware->statefulApi()` in `bootstrap/app.php` — a pre-existing
   bug (present since Dec 2025, unrelated to this upgrade) where the
   storefront checkout (`/api/v1/store/checkout`, gated by `auth:sanctum`)
   401s for any real browser session unless Sanctum's stateful-domain list
   includes the actual domain the app is served from. The code fix alone
   isn't sufficient — if Forge's production `.env` has an `APP_URL` that
   doesn't match the real production domain (the same class of drift found
   locally, where `APP_URL=http://localhost` didn't match the actual
   `colorado-supply.test` serving domain), checkout will 401 for real
   customers regardless of this fix. Confirm Forge's `.env` explicitly
   before considering the deploy safe — do not assume it's already correct.
9. Post-deploy smoke test against production: homepage load, `/admin`
   login (including completing the actual MFA challenge — not just
   reaching the challenge screen, given Phase 4's finding), one storefront
   page including a real checkout attempt through to the Stripe redirect
   (given Phase 5's finding — reaching the checkout form isn't enough,
   confirm the order-creation POST actually succeeds), `/up` health check
   route.

## Files to Create/Modify

None expected in-repo beyond what Phases 1–5 already produced. This phase
is process/verification, not code.

## Tests

- Post-deploy smoke test (Step 8) is the test for this phase — there's no
  automated substitute for confirming production actually serves traffic
  correctly after the deploy.

## Success Criteria / Definition of Done

- Production PHP version confirmed `^8.3` at deploy time.
- Pre-upgrade git tag exists.
- Rollback procedure documented in `STATE.md`.
- Staging smoke test passed (if staging exists) before production deploy.
- Deploy script confirmed to clear compiled views/OPcache post-deploy
  (Step 7 — not assumed, verified).
- Production `APP_URL`/`SANCTUM_STATEFUL_DOMAINS` confirmed to match the
  real serving domain (Step 8 — not assumed, verified).
- Production deployed and post-deploy smoke test passed, including a full
  MFA login completion (not just reaching the challenge screen) and a real
  storefront checkout attempt succeeding through to the Stripe redirect.

**STOP. Await phase approval.**

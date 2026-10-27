---
plan: laravel-13-upgrade
phase: 2
title: Core framework + first-party package upgrade
status: not_started
last_updated: 2026-07-09
depends_on: [phase-1]
checkpoint: none
---

# Phase 2 — Core Framework + First-Party Package Upgrade

## Goal

Bump `laravel/framework`, `laravel/tinker`, `laravel/boost`, and
`pestphp/pest` (+ `pestphp/pest-plugin-laravel`) to their Laravel
13-compatible versions, run `composer update`, and apply every High/Medium/
Low impact code change from the official upgrade guide
(https://laravel.com/docs/13.x/upgrade) that actually applies to this
codebase.

## Steps

0. **Scope correction from Phase 1's live dry run** (see
   `STATE.md`'s Phase 1 log entry — these are real composer-resolution
   findings, not planning-time guesses, and they change this phase's
   original scope):
   - Target `laravel/framework: ^13.12`, **not** `^13.0` — two CVEs
     ([GHSA-5vg9-5847-vvmq](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq),
     [GHSA-crmm-hgp2-wgrp](https://github.com/laravel/framework/security/advisories/GHSA-crmm-hgp2-wgrp))
     affect 13.0.0–13.11.x; composer's advisory policy blocks the whole
     `^13.0` range as a result. `^13.12` resolves cleanly against the
     latest stable `v13.19.0`.
   - `inertiajs/inertia-laravel` and `livewire/volt` **must** be bumped in
     this phase (not deferred to Phase 3) — both are hard-locked to
     `laravel/framework <=^12.x` and block composer from resolving
     anything otherwise. Bump `inertiajs/inertia-laravel` to `^3.0` here;
     Phase 3 still owns the *code-level* Vue/Inertia migration work. Bump
     `livewire/volt` to whatever version `composer update` resolves as
     Laravel-13-compatible (check it doesn't drag in a Livewire 4 jump —
     cross-check against Phase 4's constraint that Livewire stays on `^3.5`).
1. In `composer.json`:
   - `"laravel/framework": "^12.0"` → `"^13.12"`
   - `"laravel/tinker": "^2.10.1"` → `"^3.0"`
   - `"laravel/boost": "^1.6"` → `"^2.0"`
   - `"pestphp/pest": "^3.8"` → `"^4.0"`
   - `"pestphp/pest-plugin-laravel": "^3.2"` → whatever `^4.0`-compatible
     version `composer update` resolves (check Pest's own compat matrix if
     the dry run flags it).
   - `"inertiajs/inertia-laravel": "^2.0"` → `"^3.0"` (composer-level only;
     see Phase 3 for the actual code migration)
   - `"livewire/volt": "^1.7.0"` → whatever `composer update` resolves for
     Laravel 13 compatibility
2. Run `composer update`. Confirm `spatie/laravel-permission` and
   `spatie/laravel-activitylog` did not move past their Phase 1 pins
   (`composer show spatie/laravel-permission` / `spatie/laravel-activitylog`
   after the update — must show `6.x` / `4.x`).
3. **High impact — CSRF middleware rename.** In `bootstrap/app.php`:
   - `use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;` →
     `use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;`
   - Both `->withoutMiddleware(VerifyCsrfToken::class)` call sites (the
     `routes/ai.php` group registration, appears twice per the file as read
     during planning) → `PreventRequestForgery::class`.
   - Also check `app/Providers/Filament/AdminPanelProvider.php`,
     `config/sanctum.php`, and `app/Providers/Filament/AdminPanelPanelProvider.php`
     — these were flagged in a broad grep for `VerifyCsrfToken` during
     planning; confirm whether each is a real reference needing the same
     rename or a false-positive match (e.g. a comment or unrelated string).
4. **Medium impact — cache `serializable_classes`.** No code change expected
   (planning confirmed `SamOpportunitiesCache` only caches arrays), but
   after the upgrade, grep the codebase again for any `Cache::put`/
   `Cache::forever`/`->remember(` call that stores a non-array/non-scalar
   object, and add it to `config/cache.php`'s `serializable_classes` list if
   found.
5. **Medium impact — database `upsert()`.** Planning found no `->upsert(`
   calls. Re-grep after the framework bump in case new code landed since
   planning; if any exist, confirm they pass a non-empty `uniqueBy`.
6. **Low impact items** — apply only if the guide's described pattern is
   actually present (planning found none of these in this codebase, but
   verify once more against the post-upgrade code, not just planning-time
   grep results):
   - MySQL `DELETE ... JOIN ... ORDER BY ... LIMIT` queries
   - Pagination Bootstrap view name references (`pagination::default`,
     `pagination::simple-default`)
   - `Js::from` usage relying on escaped-unicode output
   - Custom `array_first()`/`array_last()` global helpers (symfony
     polyfill conflict)
   - `Manager::extend()` closures relying on a different `$this` binding
   - `Str::createUuidsUsing()`/`createUlidsUsing()`/etc. factories expected
     to persist across test methods
7. Clear all caches: `php artisan optimize:clear`.
8. Run `vendor/bin/pint --dirty` on every file touched this phase.
9. Run `php artisan test` — this phase's own test run doesn't need to be
   the full green-suite bar (that's Phase 5), but any *new* failure
   introduced by this phase's changes must be root-caused and fixed here,
   not deferred.

## Files to Create/Modify

- `composer.json`, `composer.lock`
- `bootstrap/app.php`
- `config/cache.php` (only if step 4 finds a real object-caching call site)
- Any file surfaced by steps 5–6 (expected: none, per planning audit)

## Tests

- Existing test suite must not regress from this phase's changes
  specifically (isolate: run `php artisan test` before starting this phase
  to get a baseline, then again after, and diff the two).
- No new tests are expected to be *written* in this phase — it's a
  framework-version bump, not new functionality — but if step 3's CSRF
  rename touches `routes/ai.php`'s MCP group, manually verify (or add a
  quick regression test if none exists) that MCP endpoint requests still
  bypass CSRF correctly under the new middleware class.

## Success Criteria / Definition of Done

- `composer.json` shows `laravel/framework: ^13.0`, and `composer show
  laravel/framework` confirms a `13.x` version is actually installed.
- `spatie/laravel-permission` and `spatie/laravel-activitylog` are still on
  their pinned majors after the update.
- `VerifyCsrfToken` no longer appears anywhere in the codebase (confirm via
  grep) except as a deprecated-alias mention in a comment, if any.
- `vendor/bin/pint --dirty` clean.
- No new test failures relative to the pre-phase baseline.

**STOP. Await phase approval.**

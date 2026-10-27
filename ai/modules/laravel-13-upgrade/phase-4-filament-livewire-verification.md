---
plan: laravel-13-upgrade
phase: 4
title: Filament/Livewire verification
status: not_started
last_updated: 2026-07-09
depends_on: [phase-2]
checkpoint: none
---

# Phase 4 — Filament/Livewire Verification

## Goal

Confirm `filament/filament` (already `^4.1`, resolves to `4.11.8+` which
planning verified supports Laravel 13 without requiring a Filament v5 or
Livewire 4 jump) actually resolves cleanly post-upgrade, and manually verify
the 70+ Filament resources and the MFA login flow still work — this is the
single largest surface area in the app and the freshest Laravel 13 support
(Filament 4.11.8 was published one week before this plan was written), so
it carries more regression risk than a change that's been stable for
months.

## Steps

1. After Phase 2's `composer update`, run `composer show filament/filament`
   and `composer show livewire/livewire` — confirm `filament/filament` is
   on `4.x` (not silently pulled to `v5.x`) and `livewire/livewire` is on
   `^3.5` (not pulled to `4.x`). If either jumped majors unexpectedly, stop
   and treat it the same as the Phase 1 pin-violation case — flag back
   rather than silently accepting it.
2. Run `php artisan filament:upgrade` (already wired into
   `composer.json`'s `post-autoload-dump` script, so this should run
   automatically — confirm it did, and re-run manually if needed).
3. Manual browser verification (start `composer run dev`, log into
   `/admin`):
   - MFA login flow end-to-end: log in as an existing admin, confirm the
     TOTP/email-OTP challenge still appears and accepts a valid code
     (this exercises `AdminPanelProvider`'s
     `multiFactorAuthentication([...], isRequired: true)` config from the
     `admin-account-security-complete` module — a regression here would be a
     cross-module breakage, not just a Filament issue).
   - Load at least one resource from each major CRM/store/federal area:
     `OrderResource` (CRM), `ProductResource` (store), `CompanyResource`,
     `RepairRequestResource`, `StripeEventResource` — confirm tables load,
     filters/search work, and at least one relation manager (e.g.
     `OrderResource\RelationManagers\PaymentsRelationManager`) renders.
   - Exercise one write action (e.g. edit a record, or the Stripe refund
     action on a test payment) to confirm Livewire form submission still
     round-trips correctly.
   - Check the browser console for JS errors on at least two different
     resource pages — Livewire/Alpine version mismatches often manifest as
     silent JS errors rather than server errors.
4. While already verifying the admin panel in a real browser, this is a
   good opportunity to also close out `admin-account-security-complete` Phase 2's
   still-owed real-browser CSP-violation check (see that module's
   `STATE.md` — "Manual verification gap" entry) — not required by this
   plan, but flag it to the user as free additional coverage if the browser
   session is already open. Do not silently fold that check's *sign-off*
   into this module's `STATE.md`; it belongs to `admin-account-security-complete`'s
   own tracking.

## Files to Create/Modify

None expected — this phase is verification-only. If step 1 finds an
unexpected major-version jump, treat any resulting fix as an addendum to
this phase's log, not a silent scope change.

## Tests

- `php artisan test --filter=Filament` (or the equivalent path-based
  filter for `tests/Feature/Filament/`) — full Filament test suite must
  pass.
- `tests/Feature/Filament/AdminMfaSmokeTest.php` specifically, given the
  MFA cross-module risk called out in Step 3.

## Success Criteria / Definition of Done

- `filament/filament` confirmed on `4.x`, `livewire/livewire` confirmed on
  `^3.5.x` (no unplanned major bump).
- MFA login flow manually verified working end-to-end in a real browser.
- At least the five listed Filament resources manually verified loading
  and interactive without console errors.
- Filament-related automated test suite passes.

**STOP. Await phase approval.**

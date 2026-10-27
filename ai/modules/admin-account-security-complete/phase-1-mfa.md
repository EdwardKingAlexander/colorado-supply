# Phase 1 — Filament Native MFA for the Admin Panel

## Goal

Enable and require multi-factor authentication for the `admin` guard using
Filament v4's built-in support — no third-party MFA plugin.

## First Step (before writing any code)

Read the installed package's actual source/docs for the exact API at
**v4.3.1** specifically:
`vendor/filament/filament/src/Panel/Concerns/*MultiFactorAuthentication*`
(exact path to confirm on read) and the officially versioned docs at
`https://filamentphp.com/docs/4.x/users/multi-factor-authentication`. Research
during planning found panel-level configuration via a
`multiFactorAuthentication([...], isRequired: true)`-shaped API and two
built-in methods (app/TOTP, email-OTP), plus mention that some
convenience traits were added in a later 4.5 point release — confirm
whether 4.3.1 requires implementing those interface methods manually on the
`Admin` model, or whether the trait already exists at this version, before
committing to an implementation approach.

## Files to Create/Modify

- `app/Providers/Filament/AdminPanelProvider.php` — add
  `->multiFactorAuthentication([...enabled methods...], isRequired: true)`
  (exact method list and required-interface implementation to be finalized
  per the version confirmation above).
- `app/Models/Admin.php` — implement whatever contract/trait Filament's MFA
  feature requires at the confirmed version (e.g., storing a TOTP secret,
  recovery codes) — schema/migration changes as needed.
- `database/migrations/xxxx_add_mfa_columns_to_admins_table.php` (if the
  confirmed API requires new columns rather than a separate table).
- Recovery path: confirm whether Filament's native recovery-codes feature
  is available at 4.3.1. If not, add a `php artisan admin:reset-mfa
  {email}` Artisan command as a manual fallback so a locked-out admin isn't
  permanently stuck — this is a hard requirement before `isRequired: true`
  ships to any real user.

## Out of Scope

- No MFA for the customer-facing `web` guard in this module (see
  00-overview's scope decision).
- No security headers yet (Phase 2).

## Tests

- `tests/Feature/Filament/AdminMfaEnrollmentTest.php`: an admin without MFA
  enrolled is prompted to enroll on first login post-deploy; an admin with
  MFA enrolled is prompted for a code on subsequent logins; a wrong code is
  rejected; the recovery path (native or the Artisan command fallback)
  successfully resets enrollment.

## Success Criteria

- Logging into `/admin` as any admin-guard user requires a second factor
  after enrollment.
- A locked-out admin has a documented, tested recovery path.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

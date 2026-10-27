# Phase 5 — Passkeys on the Website (Customers + Admins)

## Goal

Offer passkeys, a phishing-resistant authenticator, to customers and admins
as NIST SP 800-63B-4 AAL2 requires. A user-verified passkey is a
multi-factor cryptographic authenticator, so it is a complete sign-in on its
own; password + authenticator app remains available.

Depends on: Phases 1, 2 and 4 (session timestamps on passkey sign-in).

## Sub-phases

### 5a. Library choice (decision recorded before code)
- Evaluate on current docs/changelogs:
  - `laravel/passkeys` v0.2.1 (first-party, pre-1.0; used by
    `happenv-com/filament-passkeys` 1.4.2, which supports Filament
    ^4.13.3 — server runs 4.15.3).
  - `spatie/laravel-passkeys` 1.8.1 (stable; Filament v4 plugin
    `jeffersongoncalves/filament-multifactor-passkeys` 1.x lists Laravel
    11/12 only — check Laravel 13 support).
- Criteria: Laravel 13 + Filament 4.15 support, API (non-browser) ceremony
  support for the mobile app, user-verification enforcement, maintenance,
  open security advisories.
- Recommend one library for both customers and admins; owner approves.

### 5b. Relying party configuration
- RP ID `cogovsupply.com`, RP name "Colorado Supply", origins from config.
  Local dev uses the `.test` domain over Herd HTTPS (passkeys require a
  secure context).
- Owner confirms the RP ID; it cannot be changed later without
  invalidating every passkey.

### 5c. Customer passkeys
- Profile security section: add, name, list, remove passkeys (removal
  requires password + TOTP re-confirmation).
- Login page: "Sign in with a passkey" (conditional UI / autofill where
  supported). User verification `required`.
- Passkey sign-in sets `mfa.passed` and `auth.authenticated_at`.

### 5d. Admin passkeys
- Filament plugin (or equivalent) registered in `AdminPanelProvider`
  alongside `AppAuthentication`; admins can add passkeys on their profile
  page and use them at the MFA step or as sign-in.

## Files to Create/Modify

- Per library chosen in 5a: migrations, config, `User`/`Admin` model traits,
  `app/Http/Controllers/Auth/PasskeyController.php` (customer),
  Vue components in `resources/js/Pages/Profile/Partials/` and
  `resources/js/Pages/Auth/Login.vue`, `AdminPanelProvider.php`.

## Tests (Pest)

- Registration and assertion ceremonies with the library's test helpers or
  recorded fixtures; assertion with user-verification flag false rejected;
  replayed challenge rejected; passkey belongs to its owner only.
- Passkey sign-in counts as MFA satisfied and starts the 12-h window.
- Removing a passkey requires re-authentication.
- Admin can register and use a passkey; customer passkey cannot sign into
  the admin panel and vice versa.

## Success Criteria

- Customers and admins can register and sign in with passkeys in current
  Chrome, Safari and Edge, verified manually on desktop and phone.
- Full suite green; Pint and build clean.

## Risks

- Pre-1.0 library API changes; pin the version.
- Account recovery: a customer who loses all passkeys still has password +
  authenticator app + recovery codes.

STOP. Await phase approval.

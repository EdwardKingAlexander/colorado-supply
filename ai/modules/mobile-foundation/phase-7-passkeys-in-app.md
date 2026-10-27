# Phase 7 — [both] Passkeys in the App

## Goal

Customers can create a passkey in the app and sign in with it, satisfying
NIST SP 800-63B-4's requirement that AAL2 verifiers offer a
phishing-resistant option. App passkeys are the same credentials as website
passkeys (relying party `cogovsupply.com`), so a passkey made on one works on
the other.

Depends on: Phases 1, 4 and 6 (development builds), and
`nist-auth-hardening` Phase 5 (website passkeys, library choice, relying
party configuration).

## Sub-phases

### 7a. [server] Domain association files
- Serve `/.well-known/apple-app-site-association` (JSON, no redirect,
  `webcredentials.apps: ["<TeamID>.com.cogovsupply.mobile"]`) and
  `/.well-known/assetlinks.json` (package `com.cogovsupply.mobile`, release
  and EAS signing SHA-256 fingerprints, relation
  `delegate_permission/common.get_login_creds`).
- Values from config/env (`APPLE_TEAM_ID`, Android fingerprints), not
  hard-coded.

**Done when:** both files return 200 with `application/json` on production
domain; Apple and Google validators pass.

### 7b. [server] Passkey API for the app
- Using the library chosen in `nist-auth-hardening` Phase 5:
  - `POST v1/mobile/auth/passkey/options` → assertion options (challenge
    stored server-side, 5 min).
  - `POST v1/mobile/auth/passkey/verify` → verifies assertion with user
    verification required; success starts a mobile session exactly like
    password + TOTP (Phase 1 service), since a user-verified passkey is a
    multi-factor cryptographic authenticator.
  - `POST v1/mobile/passkeys/options|store` (ability `app`) → register a new
    passkey for the signed-in customer; `GET`/`DELETE v1/mobile/passkeys`.
- Throttled; same `{message, code}` errors.

### 7c. [mobile] Passkey screens
- `npx expo install react-native-passkeys` (verify current version and SDK
  58 compatibility first); `ios.associatedDomains:
  ["webcredentials:cogovsupply.com"]` in `app.json`.
- Sign-in screen: "Sign in with a passkey" button.
- Account: "Passkeys" list with add/remove; after first sign-in with
  password + TOTP, offer to create a passkey.
- Biometric unlock (Phase 6) is unchanged; a passkey sign-in is a full
  sign-in and starts a new 12-hour session.

## Files to Create/Modify

- Server: `routes/web.php` (`.well-known` routes), a small controller for
  the two files, `config/passkeys.php` or the chosen library's config,
  `app/Http/Controllers/Api/V1/Mobile/PasskeyController.php`, tests.
- Mobile: `app.json`, `src/lib/api/passkeys.ts`, `(auth)/sign-in.tsx`,
  `(app)/passkeys.tsx`, tests.

## Tests

- Server: association files' content and content type; assertion with UV
  flag false rejected; valid assertion starts a mobile session; replayed
  challenge rejected; registration requires `app` ability; customer cannot
  delete another customer's passkey.
- Mobile: passkey module mocked; success enters `(app)`; user cancel shows
  no error banner; unsupported device hides the button.

## Success Criteria

- On a physical iPhone and Android phone: create a passkey in the app, sign
  out, sign in with the passkey; a passkey created on the website works in
  the app and vice versa.
- All server and mobile checks clean.

## Risks

- Requires the Apple Team ID and the final Android signing fingerprints;
  EAS-managed keys must be read from EAS (`eas credentials`).
- The relying party ID is effectively permanent; it is fixed in
  `nist-auth-hardening` Phase 5 and must not change here.
- Local development cannot use passkeys against `colorado-supply.test`
  (association needs the real HTTPS domain); test against staging or
  production-like hosting.

STOP. Await phase approval.

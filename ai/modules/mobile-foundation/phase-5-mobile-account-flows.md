# Phase 5 — [mobile] Registration, MFA Setup, Email Verification, Forgot Password, Delete Account

## Goal

A new customer can create an account entirely in the app, is required to set
up MFA, is guided through email verification, can request a password reset,
and can delete their account (Apple Guideline 5.1.1(v)).

Depends on: Phases 2 and 4.

## Sub-phases

### 5a. Registration
- `(auth)/register`: name, email, password, confirm; server password rules
  surfaced as field errors; link to/from sign-in. Terms/privacy links to
  the website.

### 5b. MFA setup (mandatory)
- `(auth)/mfa-setup`: explains why MFA is required; TOTP flow:
  - "Open in authenticator app" button using the `otpauth://` URI
    (`Linking.openURL`), plus the setup key shown with a copy button (the
    phone can't scan its own screen). QR shown too, for setting up a second
    device.
  - Confirm with a 6-digit code.
- Recovery codes screen: shown once, copy/share actions, user must tick
  "I saved these" before continuing.
- Replaces the Phase 4 `mfa_setup_required` fallback.

### 5c. Email verification
- When `me.email_verified` is false and the server requires verification:
  `(app)` shows a verify-email screen with Resend (`throttle` message) and
  "I've verified" (re-fetch `me`); polls on app foreground.

### 5d. Forgot password
- `(auth)/forgot-password`: email → same confirmation message regardless of
  account existence; reset is completed via the emailed website link.

### 5e. Delete account
- `(app)/account` → Delete account: explains consequences, requires password,
  confirmation step, calls `DELETE account`, clears storage, returns to
  sign-in with a confirmation message.

## Files to Create/Modify

- Create: `(auth)/register.tsx`, `(auth)/mfa-setup.tsx`,
  `(auth)/recovery-codes.tsx`, `(auth)/forgot-password.tsx`,
  `(app)/verify-email.tsx`, `(app)/delete-account.tsx`,
  `src/lib/api/account.ts`, tests.
- Modify: `SessionProvider.tsx`, `(app)/account.tsx`, `(auth)/_layout.tsx`.
- Dependency: QR rendering library chosen per Expo SDK 58 compatibility
  (verify with `npx expo install`); add only if needed.

## Tests

- Register: field errors mapped; success goes to MFA setup.
- MFA setup: confirm valid code → recovery codes → `(app)`; cannot continue
  without acknowledging codes; invalid code error.
- Verify email: unverified user is held on the screen; verified passes.
- Forgot password: identical message for any email.
- Delete account: wrong password shows error and keeps session; success
  clears storage and signs out.

## Success Criteria

- On real iOS and Android devices: brand-new account → MFA setup with a real
  authenticator app → verify email → app; forgot password email received;
  delete account removes the user on the server.
- Lint, typecheck, tests clean.

STOP. Await phase approval.

# Phase 6 — [mobile] Session Lock, Privacy Screen, Biometric Unlock

## Goal

Meet NIST SP 800-171 3.1.10 (session lock with pattern-hiding) and the
agreed policy: the app hides its content when backgrounded, locks after 15
minutes without interaction or on return from background, and unlocks with
Face ID / fingerprint, which the server verifies via the unlock secret. After
12 hours, a full sign-in is always required.

Depends on: Phases 1 and 4. Requires the owner prerequisites (Apple
Developer Program, Expo account) — development builds replace Expo Go from
here on.

## Sub-phases

### 6a. Development builds
- `npx expo install expo-dev-client expo-local-authentication`.
- `NSFaceIDUsageDescription` via the `expo-local-authentication` /
  `expo-secure-store` config plugins in `app.json` (wording approved by
  owner).
- EAS development builds for iOS and Android installed on test devices;
  README updated.

**Done when:** both builds launch and connect to the dev server.

### 6b. Biometric-protected unlock secret
- Move `unlock_secret` into SecureStore with `requireAuthentication: true`.
  Confirm against SDK 58 docs how iOS/Android fall back to device passcode;
  if a device has no biometrics or passcode, store nothing and unlock always
  means full sign-in.
- Biometric enrollment change → key invalidated → treat as full sign-in.

### 6c. Lock behavior
- `LockGate` component in the root layout:
  - `AppState` → `background`/`inactive`: render an opaque branded privacy
    cover immediately (hides content in the app switcher), mark locked.
  - Interaction timer: 15 minutes without touches → locked.
  - Locked screen: "Unlock with Face ID / fingerprint" → read secret
    (biometric prompt) → `POST auth/unlock` → new access token → unlocked.
    Cancel or failure → stay locked, offer "Sign in with password".
  - `reauth_required` (12 h reached) → clear storage → sign-in.
- Lock state survives app restart (cold start always starts locked if a
  session exists).

## Files to Create/Modify

- Create: `src/lib/session/LockGate.tsx`, `src/lib/session/biometrics.ts`,
  `src/lib/session/useInactivityTimer.ts`, `(auth)/locked.tsx` (or modal),
  tests.
- Modify: `app.json`, `eas.json`, `storage.ts`, `SessionProvider.tsx`,
  `src/app/_layout.tsx`, `README.md`.

## Tests

- Inactivity timer locks at 15 min (fake timers), resets on interaction.
- Background → cover rendered synchronously; foreground → locked screen.
- Unlock success swaps token; biometric cancel stays locked;
  `reauth_required` signs out.
- Cold start with stored session starts locked.
- Biometrics module mocked in Jest; real behavior verified on devices.

## Success Criteria

- On a physical iPhone (Face ID) and Android phone (fingerprint):
  backgrounding hides content in the app switcher; 15-min idle locks; Face ID
  unlock works; after server idle > 60 min unlock still works (renews token)
  while under 12 h; at 12 h a full sign-in is required.
- Lint, typecheck, tests clean.

## Risks

- Simulators do not enforce biometrics; physical devices required.
- iOS `requireAuthentication` may not allow passcode-only fallback; if so,
  passcode-only devices use full sign-in (acceptable under the policy).

STOP. Await phase approval.

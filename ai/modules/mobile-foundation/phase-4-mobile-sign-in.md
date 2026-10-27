# Phase 4 — [mobile] Sign-in, MFA Challenge, Session Storage, Protected Routes, Sign-out

## Goal

An existing customer with MFA can sign in on the phone, land in the app, stay
signed in within the server's limits, and sign out. Nothing in `(app)` is
reachable without a session.

Depends on: Phases 1 and 3.

## Sub-phases

### 4a. Secure session storage
- `npx expo install expo-secure-store`.
- `src/lib/session/storage.ts`: `access_token`, `session_id`,
  `expires_at` stored with `keychainAccessible: WHEN_UNLOCKED_THIS_DEVICE_ONLY`
  (never synced or restored to another device). `unlock_secret` stored now
  without biometrics; Phase 6 moves it behind biometrics.
- Clear-all helper used by every sign-out path.

### 4b. Session state & routing
- `SessionProvider` (React context) with states: `loading`, `signedOut`,
  `challenge` (holding challenge token + status), `signedIn`.
- Root layout uses `Stack.Protected` guards (Expo Router SDK 58 docs):
  `(auth)` when not signed in, `(app)` when signed in. Splash screen held
  until storage is read.
- API client gets the access token from the provider; any
  `session_expired` / `reauth_required` / `unauthorized` response clears
  storage and returns to sign-in with an explanatory message.

### 4c. Screens
- `(auth)/sign-in`: email, password; device name from `expo-device`;
  platform from `Platform.OS`. Field errors, throttle message, no account
  enumeration.
- `(auth)/mfa`: 6-digit code entry (numeric keyboard, auto-fill
  `oneTimeCode`), "Use a recovery code" toggle.
- `mfa_setup_required` routes to the Phase 5 setup screen (placeholder screen
  explaining setup is coming is NOT acceptable — if Phase 5 is not done,
  this path shows a clear message and link to the website security page, and
  is replaced in Phase 5).
- `(app)/account`: name, email, session expiry time, Sign out button
  (`POST auth/logout`, then clear storage even if offline).

## Files to Create/Modify

- Create: `src/lib/session/storage.ts`, `src/lib/session/SessionProvider.tsx`,
  `src/lib/api/auth.ts`, `src/app/(auth)/_layout.tsx`, `(auth)/sign-in.tsx`,
  `(auth)/mfa.tsx`, tests.
- Modify: `src/app/_layout.tsx`, `src/app/(app)/account.tsx`,
  `src/lib/api/client.ts`.

## Tests

- Session reducer: every state transition, including expiry and sign-out.
- Sign-in screen: validation errors shown per field; successful login moves
  to MFA; throttled message.
- MFA screen: valid code stores credentials and enters `(app)`; invalid code
  error; recovery code path.
- Client: 401 `session_expired` clears storage and signs out.
- Storage: uses `WHEN_UNLOCKED_THIS_DEVICE_ONLY`; clear removes every key.

## Success Criteria

- On a real iPhone and Android device (Expo Go): sign in with a test customer
  that has TOTP → MFA → Account shows the user; kill/reopen app stays signed
  in; after server-side revocation the next request returns to sign-in.
- Lint, typecheck, tests clean.

STOP. Await phase approval.

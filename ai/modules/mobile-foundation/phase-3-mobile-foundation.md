# Phase 3 — [mobile] App Foundation: Tooling, Tests, API Client, Branding, Dev Networking

## Goal

Replace the Expo template with a clean, branded app skeleton that has a test
runner, a typed API client pointed at the Colorado Supply server, and a
documented way for a real phone to reach the local server.

Independent of Phases 1–2 (may run in parallel if approved).

## Sub-phases

### 3a. Clean template & tooling
- Remove template sample screens/components (`npm run reset-project` or
  manual removal), fix the template's existing `tsc` errors.
- Add Jest via `jest-expo` and React Native Testing Library
  (`npx expo install jest-expo jest @testing-library/react-native`), `npm test`
  script, one smoke test.
- `npx expo-doctor` clean.

**Done when:** `npx expo lint`, `npx tsc --noEmit`, `npm test` all pass.

### 3b. Configuration
- `EXPO_PUBLIC_API_URL` read in `src/lib/config.ts` with validation (fails
  loudly if missing). `.env.example` committed; `.env` git-ignored.
- `app.json`: `ios.config.usesNonExemptEncryption: false`, iOS/Android
  bundle IDs already `com.cogovsupply.mobile`.

### 3c. API client
- `src/lib/api/client.ts` — `fetch` wrapper: base URL, JSON headers
  (`Accept: application/json`), bearer token injection hook, timeout,
  maps server `{message, code, errors}` into a typed `ApiError`
  (`network | validation | unauthorized | session_expired | reauth_required |
  throttled | server`).
- `src/lib/api/store.ts` — first real call: `GET /api/v1/store/categories`
  (public, already exists) to prove connectivity. Types match the server's
  response (read `CategoryController`).

### 3d. Branding & shell
- Colorado Supply colors, logo, app icon and splash taken from the server's
  brand assets (`resources/images`, `BrandedAuthLayout.vue` palette).
- Light/dark theme tokens in `src/theme/`.
- Shared UI primitives: `Screen`, `Button`, `TextField`, `ErrorBanner`,
  `LoadingState`.
- Placeholder `(app)` tabs: Home (shows categories from 3c) and Account.

### 3e. Dev networking & builds
- Document in `README.md` how the phone reaches the server: decide between
  PC LAN IP (Windows Firewall rule) and `herd share` (Expose) after testing
  both against Herd on this machine; Android emulator uses `10.0.2.2`.
- `eas.json` with `development`, `preview`, `production` profiles (no build
  run yet; builds start in Phase 6).

## Files to Create/Modify

- Create: `src/lib/config.ts`, `src/lib/api/client.ts`,
  `src/lib/api/errors.ts`, `src/lib/api/store.ts`, `src/theme/*`,
  `src/components/ui/*`, `src/app/(app)/_layout.tsx`, `(app)/index.tsx`,
  `(app)/account.tsx`, `jest.config.js`, `.env.example`, `eas.json`,
  `__tests__/…`.
- Modify: `package.json`, `app.json`, `src/app/_layout.tsx`, `README.md`,
  `.gitignore`, assets.
- Delete: template demo files under `src/`.

## Tests

- `client.test.ts`: success JSON, 422 → validation errors by field, 401 codes
  mapped, network failure, timeout.
- `config.test.ts`: missing URL throws.
- Home screen renders categories from a mocked client; shows error banner on
  failure.

## Success Criteria

- App launches on an Android emulator/phone and an iPhone (Expo Go),
  branded, showing real categories from the local server.
- Lint, typecheck, tests clean; `expo-doctor` clean.

## Risks

- Herd serves HTTPS with a local certificate the phone won't trust; LAN
  access may need plain HTTP for dev or the Expose tunnel. Decide in 3e.

STOP. Await phase approval.

# Phase 1: Vite Dev-Server Image Fix

## Goal

With `composer run dev` running, images imported through Vite (e.g.
`@images/logo-cleansed.svg`, `@images/pipeline.jpg`) load correctly in Chrome
on `http://colorado-supply.test`, instead of failing with empty 503s.

## Success Criteria

- Home page in Chrome with the dev server running shows the logo and all
  hero/section images (network panel: 200s from the dev-server origin).
- `npm run build` + site without dev server still works exactly as today.
- No regression to HMR (editing a Vue file still hot-reloads).

## Sub-Phases

### 1.1 Capture the discriminating difference (diagnosis)

- With the dev server running, reproduce the 503 in Chrome; use DevTools
  "Copy as cURL" on the failed image request and replay it from the shell.
  - If the replayed request also 503s → diff its headers against the earlier
    known-good curl (which returned 200) header-by-header to isolate the
    trigger; the trigger determines the fix.
  - If the replayed request returns 200 → the discriminator is not in the
    HTTP request at all (points at connection-level or browser-level
    handling, e.g. Chrome's local-network/IPv6 treatment) → proceed on the
    IPv4 hypothesis (1.2).
- Also check the Vite dev-server terminal output while reproducing — Vite
  logs middleware denials (e.g. host/allowedHosts rejections) that would name
  the cause outright.

### 1.2 Apply the fix

Leading candidate (matches all current evidence):

- `vite.config.js`: add
  ```js
  server: {
      host: '127.0.0.1',
  },
  ```
  so `laravel-vite-plugin` writes `http://127.0.0.1:5173` into `public/hot`
  and all dev asset URLs stay on IPv4 — same address family as
  `colorado-supply.test` (Herd → 127.0.0.1), eliminating the `[::1]`
  cross-address-space hop.

Fallbacks, in order, only if 1.1 disproves the IPv4 hypothesis:

- Explicit CORS allow-list: `server.cors: { origin:
  ['http://colorado-supply.test'] }` (if 1.1 shows an origin-check denial).
- Pin/adjust `server.allowedHosts` (if 1.1 shows a host-check denial).
- Document findings and escalate choice back to orchestrator if none of the
  above hold — do not stack speculative config.

### 1.3 Verify

- Restart `composer run dev`; confirm `public/hot` now contains the IPv4 URL.
- Chrome hard reload on the home page: all images 200, logo visible.
- Touch a Vue component; confirm HMR still fires.
- Stop dev server, `npm run build`, confirm built-asset serving unaffected.

## Files to Create/Modify

| File | Change |
|------|--------|
| `vite.config.js` | Add `server.host` (and only-if-proven fallback settings) |

## Tests to Write

None (dev-environment browser behavior; not reachable from Pest). Acceptance
is the manual verification evidence in 1.3, captured as screenshots/network
logs in the STATE.md log entry.

## Known Risks / Blockers

- If the root cause is Chrome-version behavior (local network access
  handling), the IPv4 pin is still the right mitigation, but other machines/
  browsers may behave differently — note outcome in STATE.md for future
  reference.
- `[::1]` may be preferred again if `host: true` or `--host` is ever passed on
  the CLI; the config pin wins only when nothing overrides it.

## Definition of Done

All three success criteria observed and logged; `vendor/bin/pint --dirty`
clean (no PHP touched, expected no-op); STATE.md updated.

STOP. Await phase approval.

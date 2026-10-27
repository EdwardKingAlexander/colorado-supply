# STATE — vite-dev-images-and-logo-dark-variant

## Status Table

| Item | Status |
|------|--------|
| Module plan | Approved |
| Phase 1 — Vite dev-server image fix | Complete |
| Phase 2 — Logo dark-mode variant | Complete |

## Log

- **2026-07-10** — User validated both phases; marked Complete.

- **2026-07-10** — Follow-up UI polish validated. After phase 2, the logo work
  extended into: near-solid nav (`bg-*/95` + border) replacing the too-
  transparent 80% header; auth-layout (BrandedAuthLayout) alignment with the
  home nav — light logo variant directly on the dark hero (white chip
  removed), matching size, redundant company-name text dropped, nav
  full-width with links held over the dark column (`lg:pr-[552px]`) and
  dark-text styling below `lg` where the header sits on white. Further
  touch-target/focus-ring/safe-area refinements were applied to
  Navigation.vue and BrandedAuthLayout.vue (custom utilities `safe-*` in
  app.css, `max-w-drawer` in tailwind.config.js — verified defined). Verified
  in-browser at desktop and mobile widths, including the mobile menu dialog
  (light logo, 120×40) and mobile register page (navy logo on white).
  Production build clean.

- **2026-07-09 (later)** — Phases 1 and 2 implemented and verified.
  **Phase 1 root cause revision:** diagnosis disproved both the IPv4 and
  header-based hypotheses. Echo-server experiment proved the image requests
  never leave Chrome (my own server on 127.0.0.1:5199 received curl requests
  but not the page's `<img>` requests), and a clean extension-free headless
  Chrome reproduced the block — it is **Chrome 142+ Local Network Access
  (LNA)**: image subresources to IP-literal loopback URLs are blocked and the
  LNA permission cannot be granted to non-secure contexts
  (http://colorado-supply.test). Fix shipped: `herd secure colorado-supply`
  (+ `APP_URL=https://…` in `.env`) — laravel-vite-plugin then auto-detects
  the Herd TLS cert and serves the dev server at
  `https://colorado-supply.test:5173` (no IP literal, secure context), and
  `vite.config.js` pins `server.host: '127.0.0.1'` so that hostname reaches
  the listener. Verified: all dev images 200, HMR hot-update observed over
  TLS websocket, `npm run build` clean.
  **Test fallout fixed:** pinned `APP_URL=http://localhost` in `phpunit.xml`
  (the .env https change had leaked into 3 tests) and made
  `SecurityHeaders::usesViteDevServer()` ignore the hot file in unit tests
  (pre-existing flake whenever the dev server runs). Suite: 74 pre-existing
  failures → 71 (the 3 affected by this work now green; remaining 71 predate
  this module — SAM services, Filament, quotes, factories).
  **Phase 2:** created `logo-cleansed-light.svg` (navy → `#f1f5f9`), wired
  into both `dark:block` slots in `Navigation.vue`, fixed stale 48×48 hints
  in `BrandedAuthLayout.vue`. Verified both color schemes in-browser: light
  variant on dark header, navy original on white header, 193×64 box in both
  (no layout shift). Both phases await orchestrator validation.
- **2026-07-09** — Module created. Both issues discovered during the nav-logo
  viewBox fix (committed separately): (1) all images from the Vite dev server
  fail with empty 503s in Chrome while JS/CSS load fine — dev-only,
  reproduced with `pipeline.jpg` too, so pre-existing and asset-wide;
  (2) navy logo artwork is illegible on the dark header now that it renders
  at full size. Plan written; awaiting plan approval.

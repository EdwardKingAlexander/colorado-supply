# Vite Dev-Server Image Failures + Logo Dark-Mode Variant

## Problem Statement

Two issues surfaced while fixing the tiny nav logo (2026-07-09):

1. **Vite dev-server images broken in Chrome.** When the Vite dev server is
   running (`composer run dev` / `npm run dev`), every `<img>` whose asset is
   served by the dev server fails to load in Chrome with an empty **503**
   response — confirmed for both `logo-cleansed.svg` and `pipeline.jpg`, so it
   is asset-wide, not file-specific. JS and CSS modules from the same server
   load fine, and direct navigation to the same image URL renders fine. The
   `public/hot` file advertises `http://[::1]:5173` (IPv6 loopback) while the
   site itself is served at `http://colorado-supply.test` (resolves to IPv4
   loopback via Herd). The failure is specific to *cross-origin image
   subresource* requests from the page to `[::1]:5173`; identical requests via
   curl (same headers, incl. `Origin` and `Sec-Fetch-*`) return 200.

2. **Logo illegible on dark navigation.** Now that the logo renders at full
   size, its navy (`#16334c`) gear/wordmark nearly vanishes against the dark
   header (`dark:bg-gray-900/80`). `Navigation.vue` already has separate
   light/dark `<img>` slots (`dark:hidden` / `dark:block`) — both currently
   point at the same file. A light-on-dark variant is needed for the
   `dark:block` slot.

## Constraints

- No behavior change to production asset serving (`npm run build` output and
  `@vite` tags must be unaffected beyond what fixes issue 1).
- Logo artwork itself is brand-approved: recolor only (navy → light), never
  redraw. Gold (`#e9c251`) stays.
- No layout shift: nav bar height (80px) and logo slot (`h-12 sm:h-14
  lg:h-16`) stay exactly as they are after the viewBox fix.

## Current Architecture (relevant slice)

- `vite.config.js` — no `server` block today; `laravel-vite-plugin` writes the
  auto-detected dev URL into `public/hot`, currently picking `[::1]:5173`.
- `resources/images/logo-cleansed.svg` — cropped master
  (`viewBox="66 356 867 288"`), two fills only: gold `#e9c251`, navy
  `#16334c`.
- Logo `<img>` usages:
  - `resources/js/Components/Navigation.vue` — desktop nav (light+dark slots)
    and mobile dialog (light+dark slots). **Only dark-background usage.**
  - `resources/js/Layouts/BrandedAuthLayout.vue:37` — logo on a `bg-white/95`
    chip; contrast fine, but carries stale `width="48" height="48"` hints from
    the square-canvas era (true ratio is now 3.01:1).
  - `GuestLayout.vue` / `AuthenticatedLayout.vue` use `ApplicationLogo.vue`
    (separate inline component) — out of scope.

## Data Flow

```
Issue 1:  page @ colorado-supply.test → <img src="http://[::1]:5173/resources/images/*"> → Vite dev server → 503 (broken)
          page @ colorado-supply.test → <img src="/build/assets/*">                       → Herd/Laravel  → 200 (works)

Issue 2:  prefers-color-scheme / .dark → dark:block <img> → needs logo-cleansed-light.svg
                                        → dark:hidden <img> → keeps logo-cleansed.svg
```

## Risks

- **Phase 1 root cause is a hypothesis.** IPv4 pinning (`server.host:
  '127.0.0.1'`) is the leading fix because every observed discriminator points
  at the `[::1]` origin, but the 503's true source (Chrome local-network
  handling vs. Vite middleware vs. something in between) was not conclusively
  identified. Phase 1 therefore leads with a capture/diagnosis sub-phase and
  has an explicit fallback path.
- Phase 1 is dev-environment-only behavior; no automated test can cover it.
  Acceptance is manual browser evidence.
- Phase 2 recolor must be checked against *both* header states (light header
  `bg-white/80` keeps the navy original; dark header gets the light variant)
  and the mobile dialog panel.

## Phase Table

| Phase | File | Goal | Depends on |
|-------|------|------|------------|
| 1 | `phase-1-vite-dev-image-fix.md` | Images served by the Vite dev server load in Chrome during local dev | — |
| 2 | `phase-2-logo-dark-variant.md` | Legible light-on-dark logo variant wired into the dark-mode slots | — (independent of phase 1) |

# Phase 2 - Static Delivery and Missing Manifest

## Goal

Fix low-risk best-practice and delivery issues first: missing web manifest and
static asset caching.

## Scope

- Add or correct `/site.webmanifest` so the existing manifest link no longer
  returns 404.
- Ensure icon references in the manifest match files that exist under
  `public/`.
- Determine where static cache headers should be owned:
  Laravel middleware, Apache `.htaccess`, deployment config, or hosting panel.
- Add long-lived immutable caching for Vite-built assets where fingerprinted.
- Add conservative cache policy for public fixed assets such as favicons/docs
  if appropriate.

## Likely Files to Create/Modify

- `public/site.webmanifest`
- `resources/js/Pages/Welcome.vue` if duplicate or incorrect manifest/favicon
  links need cleanup.
- `public/.htaccess` or Laravel middleware/config if this repo owns production
  headers.
- Deployment notes in `STATE.md` if cache headers must be configured outside
  the repo.

## Tests to Write / Run

- Request `/site.webmanifest` locally and assert HTTP 200.
- Verify manifest JSON is valid.
- Verify built asset responses include the intended `Cache-Control` header in
  the environment where headers are owned by the repo.
- Run targeted app smoke checks for the landing page.

## Success Criteria

- Lighthouse no longer reports the manifest 404 console error.
- First-party fingerprinted assets have appropriate cache lifetimes.
- Any hosting-only cache setting is documented in `STATE.md` with owner and
  status.

**STOP. Await phase approval.**

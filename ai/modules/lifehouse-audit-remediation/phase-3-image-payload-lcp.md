# Phase 3 - Image Payload and LCP

## Goal

Reduce the largest performance cost: oversized first-party images and LCP image
loading behavior.

## Scope

- Audit actual rendered image dimensions for hero, about, compliance, and
  industries sections.
- Create appropriately sized/compressed image variants or replace imports with
  optimized assets.
- Prefer modern formats where the Vite/Laravel toolchain supports them without
  adding fragile complexity.
- Replace remote Unsplash background usage in `Compliance.vue` with local,
  optimized assets or remove it if it is purely decorative.
- Ensure above-the-fold hero/LCP imagery is discoverable early and receives
  priority where appropriate.
- Add explicit dimensions/aspect ratios to image elements to prevent layout
  shifts.

## Likely Files to Create/Modify

- `resources/js/Components/FrontEnd/Hero.vue`
- `resources/js/Components/FrontEnd/About.vue`
- `resources/js/Components/FrontEnd/Compliance.vue`
- `resources/js/Components/FrontEnd/IndustriesServed.vue`
- `resources/images/**`
- Possibly `vite.config.js` only if existing tooling supports image handling
  there and a repo-level change is justified.

## Tests to Write / Run

- Build frontend assets with `npm run build`.
- Visually verify landing page desktop and mobile.
- Re-run Lighthouse performance after this phase.
- Confirm no broken image requests in the browser console/network panel.

## Success Criteria

- First-party image transfer size is materially reduced from the roughly
  6.6 MiB reported in screenshots.
- Lighthouse image-delivery and enormous-network-payload findings are resolved
  or substantially reduced.
- LCP remains visually correct and is not lazy-loaded.
- No content images lose meaningful quality.

**STOP. Await phase approval.**

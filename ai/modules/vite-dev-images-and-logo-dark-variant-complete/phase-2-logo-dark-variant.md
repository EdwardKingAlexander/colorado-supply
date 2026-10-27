# Phase 2: Logo Dark-Mode Variant

## Goal

The logo is legible on the dark navigation header. Dark-mode `<img>` slots get
a light-on-dark recolor of the logo; light mode keeps the existing navy
original.

## Success Criteria

- Dark header: gear + "COLORADO" wordmark clearly visible (light strokes),
  gold accents unchanged.
- Light header: unchanged (navy original).
- No layout shift in either mode (same 3.01:1 box, same size hints).
- Mobile menu dialog shows the correct variant per scheme too.

## Sub-Phases

### 2.1 Create the light variant asset

- Copy `resources/images/logo-cleansed.svg` →
  `resources/images/logo-cleansed-light.svg`.
- In the copy, recolor the navy path fill `#16334c` → near-white (`#f1f5f9`,
  slate-100; softer than pure white against gray-900). Gold path `#e9c251`
  unchanged. The file has exactly two `<path>` fills, so this is a single
  attribute change.
- Keep the identical `viewBox="66 356 867 288"` so both variants are
  drop-in interchangeable.

### 2.2 Wire into Navigation.vue

`resources/js/Components/Navigation.vue`:

- Add `import logoLight from '@images/logo-cleansed-light.svg'`.
- Desktop nav: `dark:block` img `:src="logoLight"`; `dark:hidden` img keeps
  `:src="logo"`. Size hints stay `width="193" height="64"`.
- Mobile dialog: same swap on its `dark:block` img; hints stay
  `width="120" height="40"`.

### 2.3 Consistency cleanup (same file family, trivial)

- `resources/js/Layouts/BrandedAuthLayout.vue:37`: logo sits on a white chip
  so it keeps the navy original, but its intrinsic hints are stale from the
  square-canvas era — update `width="48" height="48"` → `width="145"
  height="48"` (3.01:1).

### 2.4 Verify

- `npm run build`, load home page in Chrome:
  - OS/browser in dark mode → light variant, legible.
  - Toggle to light mode (DevTools emulate `prefers-color-scheme: light`) →
    navy original on white header.
  - Mobile dialog (narrow viewport) both modes.
- Measure logo box (`getBoundingClientRect`) in both modes → identical
  dimensions, no shift.
- Spot-check `/login` (BrandedAuthLayout) → unchanged appearance, no CLS
  warning.

## Files to Create/Modify

| File | Change |
|------|--------|
| `resources/images/logo-cleansed-light.svg` | New: recolored copy (navy → `#f1f5f9`) |
| `resources/js/Components/Navigation.vue` | Import + point both `dark:block` imgs at the light variant |
| `resources/js/Layouts/BrandedAuthLayout.vue` | Fix stale 48×48 intrinsic hints → 145×48 |

## Tests to Write

None automated (pure visual asset swap; no logic). Acceptance is the visual
verification evidence in 2.4.

## Known Risks / Blockers

- Color choice (`#f1f5f9`) is a taste call — flag a screenshot to the user at
  validation; trivially adjustable.
- If the user has an official reversed/white brand asset, it should replace
  the generated recolor; the wiring in 2.2 is unchanged either way.

## Definition of Done

All success criteria observed in both color schemes; `npm run build` clean;
STATE.md updated with screenshots referenced in the log.

STOP. Await phase approval.

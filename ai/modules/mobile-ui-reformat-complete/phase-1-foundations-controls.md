# Phase 1 - Mobile Foundations and Shared Controls

## Goal

Create one mobile sizing, typography, spacing, focus, and safe-area foundation
and apply it to shared controls without changing route or form behavior.

## Files to Create/Modify

- `tailwind.config.js`
  - Add named project tokens for mobile type/line-height, 48 px control sizing,
    page gutters, and focus colors where Tailwind defaults are not expressive.
- `resources/css/app.css`
  - Add base body typography and overflow safeguards.
  - Add component-layer utilities for 48 px targets, mobile page gutters,
    safe-area padding, focus-visible rings, and reduced-motion handling.
  - Do not create a global rule that enlarges hidden/native controls or every
    inline link indiscriminately.
- `resources/views/app.blade.php`
  - Add `viewport-fit=cover` while retaining user zoom.
- Shared controls:
  - `resources/js/Components/PrimaryButton.vue`
  - `SecondaryButton.vue`
  - `DangerButton.vue`
  - `TextInput.vue`
  - `Checkbox.vue`
  - `InputLabel.vue`
  - `InputError.vue`
  - `Modal.vue`
  - `Dropdown.vue`
  - `DropdownLink.vue`
- Frontend test foundation:
  - `package.json` / lockfile: add Playwright and axe Playwright integration.
  - `playwright.config.ts`
  - `tests/Browser/support/` for public and authenticated test fixtures.
  - `tests/Browser/mobile-foundations.spec.ts`

## Implementation Requirements

- Default buttons and inputs use 16 px text and `min-height: 48px`.
- Icon-only controls provide an accessible name and a 48 px target without
  forcing the glyph itself above 24 px.
- Checkbox/radio labels own the full 48 px row hit area.
- Disabled, loading, invalid, hover, active, and focus-visible states remain
  distinct with adequate contrast.
- Inputs retain correct `type`, `autocomplete`, `inputmode`, and browser-native
  semantics supplied by callers.
- Modal focus trapping, escape close, return focus, scroll lock, labeling, and
  safe-area behavior are verified before page-specific modals depend on it.

## Tests

- Browser component/page harness assertions at 320, 390, and 430 px:
  - shared interactive targets are at least 48 px in both dimensions where
    width is not content-driven;
  - input computed font size is at least 16 px;
  - focus indicators are visible and not clipped;
  - 200% text zoom does not clip labels or buttons.
- Existing auth/profile feature tests continue to pass because form contracts
  are unchanged.
- `npm run build` and `npm run ssr-build` pass.

## Success Criteria

- Shared controls have one documented mobile baseline instead of page-specific
  sizing overrides.
- No horizontal page overflow at 320 CSS px from the shared styles.
- User zoom remains enabled.
- No non-admin page behavior changes yet beyond shared control dimensions.

**STOP. Await phase approval before implementation.**

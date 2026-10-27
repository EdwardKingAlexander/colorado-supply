# Phase 7 - Cross-Device Accessibility and Regression Hardening

## Goal

Validate the complete non-admin surface across representative mobile sizes,
zoom/text settings, orientation, themes, input methods, and accessibility rules;
fix only issues found within this module's scope.

## Files to Create/Modify

- Browser specs under `tests/Browser/` from Phases 1-6.
- `playwright.config.ts` and test fixtures as needed for stable CI execution.
- Any non-admin Vue/CSS file from prior approved phases when a verified defect
  requires correction.
- `ai/modules/mobile-ui-reformat-complete/STATE.md` with final validation results.

## Verification Matrix

### Viewports

- Portrait widths: 320, 360, 375, 390, 412, and 430 CSS px.
- Representative heights: 568, 640, 667, 780, 844, 852, and 932 CSS px.
- Landscape: at least 667x375 and 932x430.
- Tablet/desktop regression: 768x1024, 1024x768, 1280x800, and 1440x900.

### Accessibility and Adaptation

- Browser zoom/text resize at 200% with no lost content/functionality.
- Reflow equivalence at 320 CSS px and desktop 400% zoom, allowing horizontal
  scrolling only for genuinely two-dimensional data.
- WCAG text-spacing override values with no clipping or overlap.
- Keyboard-only operation, visible focus, logical tab order, no traps, and no
  focus hidden by headers/sticky actions.
- Screen-reader spot checks for menus, dialogs, form errors/status, filters,
  cart updates, checkout status, and SAM async results.
- Light/dark contrast, forced-colors/high-contrast spot check, and 200% OS text
  scaling where available.
- `prefers-reduced-motion` and touch/coarse-pointer behavior.
- Safe-area checks for notched iOS-sized viewports and bottom home indicators.

### Automated Checks

- Playwright assertions for no page-level horizontal overflow, minimum target
  boxes, computed input/body font sizes, and hidden duplicate controls.
- axe scans on one page per family plus high-risk dialogs/forms.
- Build and SSR build.
- All targeted Pest suites for auth, profile, contact/repair, store/checkout,
  dashboard/reports, SAM, Stripe, and PayPal.
- Full `php artisan test`; document any pre-existing failures separately.
- `vendor/bin/pint --dirty`.

### Manual Checks

- At least one current iOS Safari device and one Android Chrome device:
  one-handed menu use, form keyboard/zoom behavior, filter sheet, cart steppers,
  sticky checkout action, rotation, back navigation, and modal scroll lock.
- Confirm long real-world product names, addresses, agency names, filenames,
  prices, and validation messages wrap without overlapping controls.

## Success Criteria

- All route-reachable non-admin pages pass the matrix with no critical/serious
  axe violations introduced by the module.
- All primary/standalone mobile targets meet the 48 px project standard; any
  intentional exception is documented and still meets WCAG 2.5.8.
- No non-admin page loses information or functionality at 200% text sizing or
  320 CSS px reflow.
- No admin/Filament file was changed.
- Test/build/format results and any unrelated baseline failures are recorded in
  `STATE.md` before requesting validation.

**STOP. Await phase approval before implementation.**

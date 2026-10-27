# Phase 5 - Accessibility Fixes

## Goal

Resolve the remaining automated accessibility findings while preserving the
landing page design.

## Scope

- Fix low contrast elements found in the screenshots:
  - Hero eyebrow text.
  - Amber CTA/button text combinations.
  - Footer body/resource/compliance copy.
  - "Website by" footer line.
- Remove redundant alt text on decorative or adjacent-logo imagery.
- Add explicit dimensions to logo/image elements reported by Lighthouse if not
  already completed in Phase 3.
- Perform the listed manual checks where practical:
  keyboard focusability, visible focus indicators, logical tab order, modal
  focus behavior, landmarks, and labels.

## Likely Files to Create/Modify

- `resources/js/Components/FrontEnd/Hero.vue`
- `resources/js/Components/FrontEnd/Footer.vue`
- `resources/js/Layouts/AppLayout.vue`
- Header/navigation/logo component files discovered during implementation.
- Shared CSS only if the repo already centralizes these utilities there.

## Tests to Write / Run

- Run frontend build with `npm run build`.
- Perform keyboard-only navigation through the landing page and modal.
- Re-run Lighthouse accessibility audit.
- Add focused component or browser tests only if existing test patterns make
  this practical.

## Success Criteria

- Lighthouse accessibility returns 100 or the only remaining items are manual
  checks.
- All interactive controls have visible focus.
- The capabilities modal can be opened, closed, and exited by keyboard without
  trapping focus accidentally.
- No text contrast regressions in light or dark mode.

**STOP. Await phase approval.**

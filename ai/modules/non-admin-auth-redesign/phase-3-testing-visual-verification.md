# Phase 3 — Testing, Build, and Visual Verification

## Goal

Validate that the redesigned auth pages are correct across server rendering,
client build, auth behavior, and responsive visual states.

## Files to Modify

- `tests/Feature/Auth/AuthenticationTest.php`
  - Keep existing behavior tests.
  - Add render assertions only if Phase 2 introduced stable branded text worth
    protecting.

- `tests/Feature/Auth/RegistrationTest.php`
  - Keep existing behavior tests.
  - Add render assertions only if Phase 2 introduced stable branded text worth
    protecting.

- No production code changes unless verification exposes a bug.

## Verification Commands

- `npm run build`
- `npm run ssr-build`
- `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php`
- `vendor/bin/pint --dirty`

## Browser Verification

Use a local dev server and inspect both pages in browser:

- Desktop `/login`
- Desktop `/register`
- Mobile-width `/login`
- Mobile-width `/register`
- Dark-mode class behavior if the app defaults to dark mode through the root
  HTML class.

Check:

- No console errors.
- No hydration errors.
- No text overlap or clipped fields.
- Tab order is logical.
- Form submit buttons show disabled/processing state.
- Error messages remain readable.
- Homepage and alternate auth links work.

## Success Criteria

- Auth behavior tests pass.
- Production client and SSR builds pass.
- Visual check confirms both pages are on-brand with the homepage.
- No changes to Filament admin auth.
- Any remaining visual or test risk is documented in `STATE.md`.

**STOP. Await phase approval.**

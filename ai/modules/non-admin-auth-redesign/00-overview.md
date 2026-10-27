# Non-Admin Auth Redesign — Overview

## Problem Statement

The public login and register pages for normal users still look like default
Laravel Breeze screens: centered logo, plain gray background, and a generic
white form card. They do not match the Colorado Supply public site, which uses
industrial imagery, a fixed branded navigation bar, dark/navy surfaces, amber
accent states, and government/procurement-focused messaging.

Redesign the non-admin `/login` and `/register` pages so they feel like part of
the public Colorado Supply experience while preserving the existing auth
behavior, form field names, validation, redirects, and tests.

## Current State

- `resources/js/Pages/Auth/Login.vue` and `Register.vue` use
  `resources/js/Layouts/GuestLayout.vue`.
- `GuestLayout.vue` is generic Breeze styling and does not reuse the public
  navigation, logo treatment, imagery, or brand voice from the homepage.
- The homepage (`Welcome.vue`) uses `AppLayout`, `Navigation.vue`, public
  sections, optimized industrial images, and a mix of gray/navy surfaces with
  amber/primary accents.
- `/login` is shared by normal users and admins at the request-handling level,
  but the admin panel has its own Filament login route (`/admin/login`). This
  redesign is for the public/non-admin auth screens only and must not change
  authentication logic.

## Constraints

- No backend auth behavior changes:
  - Keep existing routes, form posts, field names, `remember` behavior, and
    redirects.
  - Keep the shared `/login` controller behavior that can authenticate a normal
    user or admin if credentials match.
  - Do not alter the Filament admin login page.
- Reuse existing brand assets and frontend patterns:
  - `@images/logo-cleansed.svg`
  - optimized industrial imagery already in `resources/images/optimized/`
  - `Navigation.vue` public header behavior where practical
  - existing `InputError`, `TextInput`, `InputLabel`, `Checkbox`,
    `PrimaryButton` components unless a local styling wrapper is clearly
    simpler.
- Keep forms accessible:
  - `<label>`/input associations remain intact.
  - Autofocus/autocomplete attributes remain correct.
  - Error messages remain adjacent to fields.
  - Keyboard-only flow remains straightforward.
- Keep SSR compatibility:
  - No unguarded browser-only globals in module setup.
  - Route links use existing Ziggy patterns.
- No new dependencies.
- Do not create a marketing landing page; the first viewport must be the
  usable auth form plus brand context.

## Design Direction

Use a branded split-auth composition:

- Full-height public auth shell with the existing top navigation.
- Industrial procurement visual panel using existing pipeline/grinding imagery
  with a dark overlay.
- Compact auth panel, not nested cards, with:
  - Colorado Supply logo/name as a strong brand signal.
  - Page-specific heading: "Sign in to your account" / "Create your account".
  - Short support copy tied to procurement, quotes, ordering, and account
    access.
  - Existing form controls restyled to match the public site: amber focus
    rings, primary button treatment, gray/navy text surfaces.
- Mobile layout stacks the brand panel and form cleanly, with no text overlap
  and no excessive hero-scale typography inside the form panel.

## Architecture (textual)

```
/login or /register
   -> existing Laravel auth routes/controllers
   -> Inertia::render('Auth/Login' or 'Auth/Register')
   -> Auth pages use new branded auth shell/layout
      -> shared public navigation/logo/imagery
      -> page-specific form slot
      -> existing useForm submissions unchanged
```

## Risks

- **Accidental admin-login impact** — `/login` can authenticate admins today.
  The UI copy should be normal-user focused, but code must not break the shared
  authentication flow or Filament `/admin/login`.
- **Navigation anchor behavior** — `Navigation.vue` uses homepage hash links
  such as `#about`; on `/login`, those may point nowhere. Phase 1 must decide
  whether to reuse `Navigation.vue` as-is, adapt it to absolute homepage links,
  or build a small auth-specific header using the same brand assets.
- **Brand mismatch by overusing the hero pattern** — auth pages need brand
  continuity, not a full homepage hero. Keep the form immediately usable.
- **Mobile overflow** — auth forms have several fields, especially register.
  Layout must avoid fixed heights that clip form content on small screens.
- **SSR/Ziggy regressions** — recent Inertia SSR work made route payload shape
  important. Any layout-level route usage must be covered by render/build
  checks.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Auth design system and shared shell | `phase-1-auth-shell.md` |
| 2 | Login and register page redesign | `phase-2-page-redesign.md` |
| 3 | Testing, build, and visual verification | `phase-3-testing-visual-verification.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the overall
plan is approved and that phase is explicitly approved.

**STOP. Await approval before any implementation begins.**

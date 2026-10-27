# Phase 1 — Auth Design System and Shared Shell

## Goal

Create the shared frontend structure needed for branded public auth pages
without changing login/register behavior yet.

## Files to Create/Modify

- `resources/js/Layouts/BrandedAuthLayout.vue` (new)
  - Public-auth-specific shell for `/login`, `/register`, and potentially
    future auth pages.
  - Uses existing logo and optimized imagery.
  - Provides slots/props for:
    - page title
    - short page subtitle
    - form content
    - secondary action link text/href
  - Includes a compact branded header:
    - link logo/name back to `/`
    - optional right-side link back to public site or alternate auth page
    - avoid reusing homepage-only `#about`/`#contact` links unless converted
      to absolute `/#about` style links.
  - Uses responsive layout:
    - desktop: visual brand panel plus form area
    - mobile: stacked layout with form first or form immediately visible after
      a compact brand header
  - No browser-only global access in module setup.

- `resources/js/Layouts/GuestLayout.vue`
  - Leave in place for reset/forgot/confirm screens unless Phase 2 decides to
    migrate those too.
  - Do not delete or broadly refactor in this phase.

## Design Requirements

- Brand colors should align with existing public site:
  - dark gray/navy surfaces (`gray-900`, `slate-*`)
  - amber accents for link/focus/secondary highlights
  - primary blue for main action buttons where existing components already use
    it
- Use existing real site imagery, not a new generated illustration.
- Avoid nested cards. If a form container is needed, use one focused panel
  against a full-width shell.
- Text sizing must remain compact inside the auth panel; no homepage-scale H1
  inside a form surface.
- Form panel must fit on common mobile widths without horizontal scrolling.

## Tests

No dedicated automated tests required in this phase unless a component import
or route helper requires a small render smoke test. Manual checks happen in
Phase 3.

## Success Criteria

- New layout compiles.
- Layout can host arbitrary auth form content without changing form state or
  submissions.
- Header links work correctly from `/login` and `/register`.
- No existing auth page behavior changes yet.

**STOP. Await phase approval.**

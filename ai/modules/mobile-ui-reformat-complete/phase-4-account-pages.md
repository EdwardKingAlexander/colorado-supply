# Phase 4 - Authentication, Profile, and Company Pages

## Goal

Apply the mobile system to every non-admin account entry and maintenance flow,
starting from the validated branded-auth redesign.

## Files to Create/Modify

- `resources/js/Layouts/BrandedAuthLayout.vue`
- `resources/js/Layouts/GuestLayout.vue` where still used
- `resources/js/Pages/Auth/*.vue`
- `resources/js/Pages/Profile/Edit.vue`
- `resources/js/Pages/Profile/Partials/*.vue`
- `resources/js/Pages/Company/Index.vue`
- `resources/js/Components/ApplicationLogo.vue` only if its mobile bounds need
  correction
- `tests/Browser/mobile-account-pages.spec.ts`
- Existing auth/profile feature tests.

## Implementation Requirements

- Do not begin until `non-admin-auth-redesign` Phase 2 is validated; preserve
  its brand composition and all form behavior.
- Keep the usable auth form as the first mobile task, with compact brand context
  and no oversized marketing block above it.
- Auth headers use 48 px destinations; long Home/Create account labels wrap or
  adapt without colliding with the logo at 320 px.
- All auth fields and actions use 16 px text and 48 px targets. Remember-me and
  policy rows make the full label clickable.
- Long registration/reset forms scroll naturally with no fixed-height clipping
  and retain keyboard-visible submit actions.
- Profile sections use an unframed page layout or individual task panels with
  16 px padding; modal destructive confirmation uses the shared mobile dialog.
- Company location links render as 48 px mobile list rows rather than small
  inline links.
- Error, status, saved, verification, and destructive-action feedback is
  programmatically announced.

## Tests

- 320/390/430 px coverage for login, register, forgot/reset, verify, confirm,
  profile edit, delete confirmation, and company location lists.
- Verify tab order, autofocus behavior, password-manager autocomplete tokens,
  checkbox label activation, modal focus trapping, and loading/disabled states.
- At 200% text size, header links and form actions do not overlap or clip.
- Existing authentication, registration, password reset, verification, and
  profile Pest suites pass; build, SSR build, and Pint pass.

## Success Criteria

- Every account flow is comfortably usable one-handed at phone widths.
- No auth/backend contract or redirect changes.
- The in-flight auth redesign is extended, not reverted or replaced.

**STOP. Await phase approval before implementation.**

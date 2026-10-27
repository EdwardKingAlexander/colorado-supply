# Phase 2 — Login and Register Page Redesign

## Goal

Move `Login.vue` and `Register.vue` onto the branded auth shell and restyle
their forms so they match the public Colorado Supply experience while
preserving existing auth behavior.

## Files to Modify

- `resources/js/Pages/Auth/Login.vue`
  - Replace `GuestLayout` with `BrandedAuthLayout`.
  - Keep existing `useForm` object:
    - `email`
    - `password`
    - `remember`
  - Keep `form.post(route('login'))` and password reset on finish.
  - Preserve `canResetPassword`, `status`, autocomplete, autofocus, and
    validation display.
  - Add on-brand copy for normal users, e.g. account access for quotes,
    ordering, and procurement support.
  - Keep a clear link to registration when public registration is enabled.

- `resources/js/Pages/Auth/Register.vue`
  - Replace `GuestLayout` with `BrandedAuthLayout`.
  - Keep existing `useForm` object:
    - `name`
    - `email`
    - `password`
    - `password_confirmation`
  - Keep `form.post(route('register'))` and password reset on finish.
  - Preserve autocomplete/autofocus and validation display.
  - Add on-brand copy that positions registration as access to customer
    account workflows, not a generic app signup.
  - Keep link back to login.

- Optional, only if needed:
  - `resources/js/Components/PrimaryButton.vue`
  - `resources/js/Components/TextInput.vue`
  - Prefer passing classes from the auth pages/layout first. Do not globally
    restyle shared controls unless the existing component API prevents a
    clean result.

## Out of Scope

- Password reset, forgot password, email verification, and confirm password
  redesigns unless the approved phase is expanded.
- Backend auth controller changes.
- Filament admin login page.
- New fields, terms checkbox, company approval workflow, or invitation logic.

## Tests

Existing tests must keep passing:

- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/RegistrationTest.php`

Add focused assertions only if the render output needs protection, for
example:

- login page includes a Colorado Supply branded heading or landmark text
- register page includes a Colorado Supply branded heading or landmark text

## Success Criteria

- `/login` is visibly on-brand and still authenticates users/admins exactly as
  before.
- `/register` is visibly on-brand and still registers a user exactly as
  before.
- Existing validation errors appear in the right places.
- Alternate auth links and password reset link remain available.
- `npm run build` succeeds.

**STOP. Await phase approval.**

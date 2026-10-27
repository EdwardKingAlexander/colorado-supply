# Phase 4 - Fonts and Third-Party Loading

## Goal

Reduce render-blocking and unused JavaScript impact from fonts, analytics, and
reCAPTCHA without breaking forms or tracking.

## Scope

- Review current Google Fonts usage in `resources/views/app.blade.php`.
- Decide between self-hosting Inter or improving current Google Fonts preload,
  preconnect, and stylesheet behavior.
- Fix any incorrect preconnect/crossorigin setup.
- Review Google Tag Manager/gtag loading and keep it async/non-blocking.
- Review reCAPTCHA loading:
  - Keep contact and repair forms functional.
  - Avoid loading reCAPTCHA earlier than necessary if a safe lazy strategy is
    feasible.
- Record third-party cookie findings as accepted/external unless a product
  decision is made to replace reCAPTCHA.

## Likely Files to Create/Modify

- `resources/views/app.blade.php`
- `resources/js/Components/FrontEnd/Contact.vue`
- `resources/js/Components/FrontEnd/RepairRequestForm.vue`
- Possibly `public/fonts/` or `resources/fonts/` if self-hosting Inter.

## Tests to Write / Run

- Build frontend assets with `npm run build`.
- Submit or mock contact and repair form flows enough to verify reCAPTCHA token
  acquisition still works.
- Re-run Lighthouse performance and best-practices sections.
- Verify no new console errors.

## Success Criteria

- Render-blocking/font-display/preconnect findings are resolved or reduced.
- Contact and repair forms still pass reCAPTCHA behavior.
- Third-party cookie warnings are either reduced or explicitly accepted in
  `STATE.md` as external to app code.

**STOP. Await phase approval.**

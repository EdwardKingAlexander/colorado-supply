# Phase 3 — Frontend Page & Repair Request Form

## Goal

Build the public `/repair-services` landing page: on-brand with
`Welcome.vue` (same `AppLayout`, header/footer, color system), summarizing
the repair program (categories, value props, simplified process) and ending
in a lead-capture form wired to Phase 1's endpoint.

## Files to Create/Modify

- `resources/js/Pages/RepairServices/Index.vue`
  - Uses `AppLayout` + `<Head>` block with its own title/meta description
    (distinct from `Welcome.vue`'s), e.g. "Industrial Repair Services |
    Colorado Supply & Procurement".
  - Composes new `FrontEnd` components (below) in order: hero → value
    props → service categories → how-it-works → form → reuse existing
    `Footer.vue`.

- `resources/js/Components/FrontEnd/RepairHero.vue`
  - Same visual treatment as `Hero.vue` (gradient decoration, `primary-700`
    accents, dark/light backgrounds) but its own headline/copy — no PDF
    modal needed. Headline: something like "Industrial Electronics, Servo
    Motor & Motor Repair — Up to 50% Less Than New." CTA scrolls to `#repair-form`.

- `resources/js/Components/FrontEnd/RepairValueProps.vue`
  - Card grid (reuse `Capabilities.vue`'s card styling for consistency):
    Save up to 50% vs. new · 2-Year In-Service Warranty · Free, Risk-Free
    Evaluation · No Upfront Cost & No Freight Cost · 3-5 Day Turnaround
    (Rush Available) · Unrepairable? We Source & Quote an Alternative.
  - Content adapted from the brochure's "Global Advantage" badges — no GES
    branding, icons, or logos copied in.

- `resources/js/Components/FrontEnd/RepairCategories.vue`
  - Grid of the 4 major categories from the brochure, each with a short
    description and a few representative items (not the full 15-page
    manufacturer list):
    - Industrial Electronics (circuit boards, drives, PLCs, HMIs, power
      supplies, process controls)
    - Servo Motors (AC/DC, continuous rotational, linear)
    - AC & DC Motors (3-phase, brushless, gear, stepper, spindle)
    - Hydraulics & Pneumatics (actuators, cylinders, pumps, valves,
      manifolds)

- `resources/js/Components/FrontEnd/RepairProcess.vue`
  - Simplified 4-step version of the brochure's 5-phase process: Submit
    Your Request → Free Evaluation & Quote → We Repair (or Source an
    Alternative) → Fast Return, No Cost Upfront.

- `resources/js/Components/FrontEnd/RepairRequestForm.vue`
  - Structurally modeled on `Contact.vue` (same reCAPTCHA v3 flow via
    `window.grecaptcha`, `recaptchaAction = 'repair_request_form'`, same
    loading/error/success UX, same Tailwind input styling for brand
    consistency).
  - Fields: `name`* , `email`*, `phone`, `company`, `equipment_type`*
    (select: Industrial Electronics, Servo Motor, AC Motor, DC Motor,
    Drive/Inverter, HMI/Touchscreen, Hydraulic Component, Pneumatic
    Component, PLC, Other), `manufacturer`, `model_number`*, `serial_number`,
    `issue_description`* (textarea), `urgency` (select: Standard, Rush).
    (`*` = required, matches Phase 1 validation.)
  - **Honeypot field must be named `website`** (hidden, `tabindex="-1"`,
    `autocomplete="off"`), *not* `company` — the repair form has a real,
    optional `company` field, unlike `Contact.vue` where `company` is the
    honeypot. Controller's honeypot check in Phase 1 must check
    `$request->filled('website')` accordingly.
  - POSTs to `/repair-services` (named route `repair-services.store`).

- `resources/js/Components/Navigation.vue` — add a "Repair Services" link
  (desktop + mobile menu) pointing to `route('repair-services.index')`,
  placed after "Capabilities" and before "Contact".

- `resources/js/Components/FrontEnd/Footer.vue` — check current content;
  add a "Repair Services" link if the footer has a site-links section.

- `resources/js/Components/FrontEnd/RepairServicesTeaser.vue` (new) — short
  homepage teaser section (headline + 1-2 sentence pitch + "Learn More"
  link to `route('repair-services.index')`), styled consistently with
  `Capabilities.vue`/`About.vue`.
- `resources/js/Pages/Welcome.vue` — insert `<RepairServicesTeaser />`
  between `<Capabilities />` and `<Contact />`.

## Out of Scope

- No file/photo upload on the form.
- No changes to `Welcome.vue`'s own sections.
- No GES name, logo, phone number, or brochure attribution anywhere.

## Tests

(Covered in Phase 4, but developer should manually verify in-browser:)
- Page renders at `/repair-services` without auth.
- Dark mode and mobile viewport both look correct (reuse of existing
  Tailwind dark: classes).
- Form submits successfully end-to-end against Phase 1's endpoint.

## Success Criteria

- `/repair-services` is reachable, unauthenticated, visually consistent
  with `Welcome.vue` (colors, spacing, typography, header/footer).
- Nav link present and working from the homepage.
- Homepage (`Welcome.vue`) shows the repair-services teaser section and
  links through correctly.
- Form submission round-trips to a real `RepairRequest` row (manually
  verified) with success/error states matching `Contact.vue`'s UX.
- `npm run build` succeeds with no new console errors.

**STOP. Await phase approval.**

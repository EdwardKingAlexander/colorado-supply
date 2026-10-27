# Phase 3 - Public Homepage and Repair Services

## Goal

Reformat the public marketing, contact, repair, and document-viewing experience
for readable mobile scanning and reliable touch/form interaction.

## Files to Create/Modify

- `resources/js/Pages/Welcome.vue`
- `resources/js/Pages/RepairServices/Index.vue`
- Route-reachable components under `resources/js/Components/FrontEnd/`:
  - `Hero.vue`, `About.vue`, `Capabilities.vue`, `Compliance.vue`,
    `IndustriesServed.vue`, `Contact.vue`, `RepairServicesTeaser.vue`
  - `RepairHero.vue`, `RepairValueProps.vue`, `RepairCategories.vue`,
    `RepairProcess.vue`, `RepairRequestForm.vue`
  - `Footer.vue` only for page-specific follow-up after Phase 2
- `tests/Browser/mobile-public-pages.spec.ts`
- Existing `tests/Feature/RepairServiceControllerTest.php` and contact tests
  only where accessibility/status behavior needs server assertions.

## Implementation Requirements

- Use 16/20/24 px gutters, 64-80 px mobile section spacing, 16 px body copy,
  and a controlled mobile heading scale.
- Homepage hero should occupy roughly 80-88 small-viewport-height units after
  the header, leave a cue of the next section, and stack full-width actions at
  320 px. Preserve its real industrial image and readable overlays.
- Remove decorative gradient/orb layers that do not carry content.
- Reflow repeated feature grids to one column or a deliberate two-column layout
  only when each tile remains readable at 320 px and 200% text.
- Contact phone/email links and all calls to action use full touch targets.
- Contact and repair forms use Phase 1 controls or equivalent shared classes:
  48 px fields, 16 px values, one column on phones, label/help/error spacing,
  `inputmode` and autocomplete appropriate to email/phone/name/address-like
  data, and 48 px submit buttons.
- Error summaries/status messages use live regions without duplicating noisy
  announcements; success moves focus or announces completion.
- Capabilities PDF modal becomes a safe-area-aware mobile sheet/full-screen
  dialog with 48 px close/download actions and no clipped iframe content.

## Tests

- Public screenshots and overflow assertions at 320, 360, 390, and 430 px in
  portrait plus 667x375 landscape.
- Assert hero actions, all form controls, modal controls, phone/email links, and
  footer links meet the target standard.
- Keyboard and focus checks for the capabilities modal.
- Mock contact/repair responses to verify loading, field errors, CAPTCHA error,
  and success announcements without sending submissions.
- axe checks on `/` and `/repair-services` in light/dark modes.
- Existing repair/contact feature tests, build, SSR build, and Pint pass.

## Success Criteria

- Public pages are readable and operable at 320 px with no two-dimensional page
  scrolling.
- The next homepage section is visibly discoverable on common phone heights.
- Forms do not trigger layout zoom from undersized input text and do not hide
  errors or submit status.

**STOP. Await phase approval before implementation.**

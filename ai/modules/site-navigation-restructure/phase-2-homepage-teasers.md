# Phase 2 — Homepage Teaser Consolidation

## Goal

Turn `Welcome.vue` into a landing page: `Hero` + one short teaser card per
section (About, Capabilities, Repair Services, Contact) linking to the
dedicated pages from Phase 1, plus the existing `Compliance` and
`IndustriesServed` sections (unchanged, stay inline), plus `Footer`.

## Files to Create/Modify

- `resources/js/Components/FrontEnd/SectionTeaser.vue` (new) — generic
  presentational component replacing the one-off
  `RepairServicesTeaser.vue`. Props: `icon` (component), `title`,
  `description`, `href`, `ctaLabel` (default `"Learn More →"`). Visually
  identical to the existing `RepairServicesTeaser.vue` card (icon, heading,
  paragraph, amber CTA button) so the homepage doesn't visually change,
  just becomes data-driven.
- `resources/js/Components/FrontEnd/RepairServicesTeaser.vue` — **deleted**;
  replaced by a `<SectionTeaser>` usage in `Welcome.vue`.
- `resources/js/Pages/Welcome.vue`:
  - Remove `<About />`, `<Capabilities />`, `<Contact />` imports/usage.
  - Add four `<SectionTeaser>` instances (About, Capabilities, Repair
    Services, Contact) with copy summarizing each section and linking to
    `route('about.index')`, `route('capabilities.index')`,
    `route('repair-services.index')`, `route('contact.index')`
    respectively.
  - Keep `<Hero />`, `<Compliance />`, `<IndustriesServed />`, `<Footer />`
    as-is.
- Teaser copy (short, 1-2 sentences each, written fresh — not copy-pasted
  from the full sections):
  - **About** — "Colorado-based, over a decade of industrial supply chain
    experience serving federal, state, and local agencies nationwide."
  - **Capabilities** — "Industrial supplies, procurement & sourcing, custom
    manufacturing, and government contracting support under one roof."
  - **Repair Services** — reuse the existing teaser copy already written
    for `RepairServicesTeaser.vue`.
  - **Contact** — "Have a question or need a quote? Reach our team
    directly or send a message."

## Out of Scope

- Nav/footer changes (Phase 3) — `Navigation.vue`'s `#about`/`#capabilities`/
  `#contact` anchors still point nowhere on the homepage after this phase
  until Phase 3 fixes them. Acceptable as an intermediate state since
  phases land together in one review cycle; call this out explicitly if
  phases 2 and 3 end up shipping separately.

## Success Criteria

- Homepage renders with `Hero`, four teaser cards, `Compliance`,
  `IndustriesServed`, `Footer` — no leftover full About/Capabilities/Contact
  content inline.
- Each teaser's CTA correctly links to its Phase 1 route.
- `npm run build` succeeds; `vendor/bin/pint --dirty` clean (no PHP touched).

**STOP. Await phase approval.**

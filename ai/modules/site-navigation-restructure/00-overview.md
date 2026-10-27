# Site Navigation Restructure — Overview

## Problem Statement

The public marketing site is currently a single scrolling page (`Welcome.vue`)
with `About`, `Capabilities`, and `Contact` as scroll-anchored sections
(`#about`, `#capabilities`, `#contact`). `Repair Services` already broke out
into its own page/route (`/repair-services`) in a prior module. This is
inconsistent and has a real bug: `Navigation.vue`/`Footer.vue`'s anchor links
only work while already on the homepage — clicking "About" from
`/repair-services` does nothing, because there's no `#about` element on that
page.

We're extending the same "own page" treatment to `About`, `Capabilities`, and
`Contact`, turning the site into a proper multi-page site, and using this as
the opportunity to also:
- Add a "Store" link to the main nav for **all** visitors (not just
  authenticated ones) — logged-out clicks redirect to `/login` via the
  existing `auth.web_or_admin` middleware, which is acceptable per-user
  decision.
- Relocate the store on/off toggle out of Filament admin's "Automation → MCP
  Control Panel" (non-obvious location) into a small always-visible switch in
  the admin panel's own sidebar navigation.

## Constraints

- `Compliance` and `IndustriesServed` were **not** named for extraction —
  they stay as homepage sections.
- Homepage becomes a landing page: `Hero` + short teaser cards (About,
  Capabilities, Repair Services, Contact) each linking to their full page,
  rather than duplicating full content in two places (per user decision —
  avoids duplicate-content SEO issues and double-maintenance).
- Reuse the existing `RepairServicesTeaser.vue` pattern, but consolidate the
  four teasers (About/Capabilities/Repair Services/Contact) into one
  reusable `SectionTeaser.vue` presentational component instead of four
  near-identical files — this is real, current duplication, not a
  hypothetical future need.
- Reuse existing section components (`About.vue`, `Capabilities.vue`,
  `Contact.vue`) unchanged inside their new dedicated pages — no content
  rewrite, just relocation. (The Gemini-informed hero copy pass is a
  separate, already-tracked follow-up on `repair-services`, not part of this
  module.)
- New `/contact` page route is distinct from the existing `POST /contact`
  form-submission route (`contact.store`) — name it `contact.index`.
- Store toggle: single source of truth. The existing `toggleStore` header
  action on `McpDashboard` is removed once the new sidebar switch lands, so
  there's exactly one place to flip it.
- No changes to `EnsureStoreEnabled` middleware or the `McpSettings`
  storage mechanism — only how the toggle is surfaced in the UI.

## Architecture (textual)

```
Public site:
  GET /              -> Welcome.vue (Hero, SectionTeaser x4, Compliance, IndustriesServed, Footer)
  GET /about          -> About/Index.vue (About.vue + Footer)          [new]
  GET /capabilities   -> Capabilities/Index.vue (Capabilities.vue + Footer) [new]
  GET /contact        -> Contact/Index.vue (Contact.vue + Footer)      [new]
  GET /repair-services -> (already exists, unchanged)
  POST /contact       -> ContactController@store (already exists, unchanged)

Navigation.vue / Footer.vue:
  Home / About / Capabilities / Repair Services / Contact / Store
  all resolve to real named routes (no more homepage-only anchors),
  Store link now visible to every visitor.

Filament admin panel:
  AdminPanelProvider registers a render hook (sidebar nav area) rendering a
  new Livewire component, StoreAvailabilityToggle, which reads/writes
  McpSettings::for('store-settings') directly — no page navigation needed
  to flip it. The old toggleStore action on McpDashboard is removed.
```

## Risks

- **Duplicate route/component naming collisions** — `Contact.vue` (component)
  vs. `Contact/Index.vue` (page) must stay clearly distinguishable in
  imports; follow the same naming Phase pattern already used for
  `RepairServices/Index.vue`.
- **Nav anchor fallout** — once About/Capabilities/Contact move to real
  routes, any other remaining in-page anchor (e.g. `#home`) must still
  resolve correctly when already on `/`; anchors pointing to sections that
  no longer exist on the homepage (e.g. `#about`) must be replaced
  everywhere, not just in `Navigation.vue`.
- **Filament render hook API surface** — Filament v4's exact
  `PanelsRenderHook` constant for "inside the sidebar nav" needs confirming
  against the installed Filament version during Phase 4; if the ideal hook
  isn't available, fall back to a topbar hook rather than blocking the
  phase.
- **Removing the old toggle** — confirm no other code/tests depend on
  `McpDashboard`'s `toggleStore` action before deleting it.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Dedicated pages for About, Capabilities, Contact | `phase-1-dedicated-pages.md` |
| 2 | Homepage teaser consolidation (`SectionTeaser.vue`) | `phase-2-homepage-teasers.md` |
| 3 | Navigation & Footer routing + Store link | `phase-3-navigation-footer.md` |
| 4 | Admin sidebar store-availability toggle | `phase-4-admin-store-toggle.md` |
| 5 | Testing & hardening | `phase-5-testing-hardening.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per `PROJECT_CANNON.md`.

**STOP. Await approval before any implementation begins.**

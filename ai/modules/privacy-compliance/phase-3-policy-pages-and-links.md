---
plan: privacy-compliance
phase: 3
title: Policy Pages & Required Links
status: not_started
last_updated: 2026-07-10
depends_on: [phase-1, phase-2]
checkpoint: none
---

# Phase 3 — Policy Pages & Required Links

## Goal

Publish the legally required disclosures: a privacy policy and cookie policy
whose cookie tables are generated from `config/privacy.php` (so disclosure
never drifts from reality), plus the footer links every state law expects —
including the CCPA "Your Privacy Choices / Do Not Sell or Share My Personal
Information" entry point.

## Success Criteria

- `/privacy-policy` and `/cookie-policy` render on desktop and mobile, indexed
  in the sitemap, linked from every customer-facing footer.
- Cookie policy table lists exactly the cookies in `config/privacy.php`.
- "Your Privacy Choices" opens the preferences modal (Phase 2) from any page;
  a `/privacy/your-choices` fallback page exists for direct links.
- Draft policy text is explicitly marked for counsel review and covers the
  state-law disclosure checklist (categories collected, purposes, sharing,
  consumer rights incl. appeal, GPC honoring statement, DSR contact channel).

## Sub-Phases

### 3.1 Policy content drafting (`content` checkpoint)
- Draft privacy policy sections from the Phase 1 data inventory: data
  collected, purposes, sharing (GA4/Google), consumer rights per state
  baseline (access, correct, delete, portability, opt-out, appeal), GPC
  statement, retention summary, contact + DSR form link, effective date +
  version.
- Draft cookie policy: what cookies are, category explanations, config-driven
  table, how to change choices.
- Deliverable includes a `COUNSEL-REVIEW.md` checklist of claims requiring
  legal sign-off before production.

### 3.2 Pages + routes (`pages` checkpoint)
- Inertia pages `resources/js/Pages/Legal/PrivacyPolicy.vue` and
  `Legal/CookiePolicy.vue`; controller passes policy version + cookie registry.
- Routes `GET /privacy-policy`, `GET /cookie-policy` (public, cached).
- Mobile-compliant typography (16px body, proper heading scale).

### 3.3 Footer & entry links (`links` checkpoint)
- `Components/FrontEnd/Footer.vue`: add Privacy Policy, Cookie Policy, and
  **Your Privacy Choices** (with the required "Do Not Sell or Share My
  Personal Information" wording) — link row meets 44px touch targets.
- `AuthenticatedLayout.vue` currently has no footer: add a minimal legal
  footer so portal pages also expose the links.
- Auth pages (`BrandedAuthLayout`): small legal-links row under the form.
- Registration/checkout forms: "By continuing you agree to the Privacy
  Policy" line where data is submitted.

## Files to Create/Modify

| File | Action |
|---|---|
| `app/Http/Controllers/LegalPageController.php` | create |
| `routes/web.php` | modify |
| `resources/js/Pages/Legal/PrivacyPolicy.vue` | create |
| `resources/js/Pages/Legal/CookiePolicy.vue` | create |
| `resources/js/Components/Privacy/LegalFooterLinks.vue` | create (shared link row) |
| `resources/js/Components/FrontEnd/Footer.vue` | modify |
| `resources/js/Layouts/{AuthenticatedLayout,BrandedAuthLayout}.vue` | modify |
| `ai/modules/privacy-compliance/COUNSEL-REVIEW.md` | create |

## Tests to Write

- `tests/Feature/Privacy/LegalPagesTest.php` — routes 200, cookie table
  content matches config registry, policy version rendered.
- Footer link presence asserted on a representative page from each layout.

## Risks / Blockers

- **Policy text is not legal advice** — production launch gate is counsel
  sign-off (tracked in COUNSEL-REVIEW.md); engineering completion ≠ legal done.
- Wording of the CCPA link is prescriptive; keep exact phrase available even
  if the visible label is "Your Privacy Choices".

## Definition of Done

Pages live locally with config-driven cookie table; links present on all
customer-facing layouts; counsel-review checklist written; tests green vs
baseline; pint clean.

STOP. Await phase approval.

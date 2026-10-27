---
plan: privacy-compliance
phase: 2
title: Consent Management (Banner, GPC, Consent Mode v2)
status: not_started
last_updated: 2026-07-10
depends_on: [phase-1]
checkpoint: none
---

# Phase 2 — Consent Management

## Goal

Ship the visitor-facing consent system: the banner and preferences UI, the
consent endpoint that writes the cookie + receipt, automatic GPC honoring, and
GA4 rewired through Google Consent Mode v2 so analytics never runs without an
affirmative basis.

## Success Criteria

- First visit: banner appears; GA4 Consent Mode defaults are `denied` and no
  `_ga*` cookies are set until "Accept all".
- Visit with GPC enabled: banner is replaced by a dismissible "we honored your
  Global Privacy Control signal" notice; analytics stays denied even if a prior
  accept exists (GPC wins per CO/CA guidance); a `gpc_applied` receipt is stored.
- Opt-out after opt-in takes effect immediately (consent update + `_ga*`
  cookies expired).
- Banner meets the mobile standards used elsewhere in the app (≥44px touch
  targets, 16px text) and renders under Inertia SSR without errors.

## Sub-Phases

### 2.1 Consent endpoint (`endpoint` checkpoint)
- `ConsentController@store` — POST `/privacy/consent` (web, throttled):
  validates categories against `config/privacy.php`, generates/reuses visitor
  UUID, queues `cs_privacy_consent` cookie (JSON: uuid, categories, version,
  timestamp), persists a `PrivacyConsent` receipt (attaches `user_id` when
  authenticated, `gpc_applied` when the middleware flagged the request).
- Route in `routes/web.php`; no auth required.
- Share the configured category labels/descriptions/locked flags through the
  existing Inertia `privacy` prop so the client never maintains a second
  category registry.

### 2.2 Banner + preferences UI (`ui` checkpoint)
- `resources/js/Components/Privacy/CookieConsentBanner.vue`:
  - Renders from the shared `privacy` prop; hidden when a valid same-version
    consent exists.
  - Buttons: **Accept all**, **Essential only**, **Preferences** — equal visual
    weight (dark-pattern avoidance), `h-12` / `text-base`, full-width stacked
    on mobile, safe-area padded.
  - GPC variant: informational notice, single dismiss button. On first GPC
    visit, submit one essential-only decision so the cookie prevents repeated
    receipt creation while `gpc_applied` provides the audit trail. GPC still
    overrides any earlier analytics grant on every page load.
- `resources/js/Components/Privacy/PrivacyPreferencesModal.vue` — per-category
  toggles (essential locked on), descriptions from config-fed props, Save.
- Mount in `AppLayout.vue`, `AuthenticatedLayout.vue`, `GuestLayout.vue`,
  `BrandedAuthLayout.vue`. No mount in Filament.
- Re-prompt when stored consent `version` ≠ current `policy_version`.

### 2.3 GA4 Consent Mode v2 gating (`ga4` checkpoint)
- Rewrite the gtag block in `resources/views/app.blade.php` (extract to
  `resources/views/partials/analytics.blade.php`):
  1. `gtag('consent', 'default', { analytics_storage: 'denied', ad_storage:
     'denied', ad_user_data: 'denied', ad_personalization: 'denied' })`
  2. Read `cs_privacy_consent` cookie + `navigator.globalPrivacyControl`
     inline; fire `gtag('consent', 'update', …)` only when analytics was
     accepted **and** GPC is absent.
  3. `gtag('config', …)` last.
- Client util `resources/js/Utils/consent.js` — apply consent updates on
  banner actions without reload; expire `_ga*` cookies on opt-out.
- Ensure the partial is included by any other layout that loads gtag (audit
  `partials/head.blade.php` and Filament — currently only `app.blade.php`).

### 2.4 SSR + browser verification (`verify` checkpoint)
- SSR build boots with banner mounted (`npm run ssr-build` + smoke).
- Manual browser matrix (Chrome devtools): no-consent, accept-all, essential-
  only, GPC-on — assert cookie behavior for each; record results in STATE log.

## Files to Create/Modify

| File | Action |
|---|---|
| `app/Http/Controllers/ConsentController.php` | create |
| `routes/web.php` | modify (consent route) |
| `resources/js/Components/Privacy/CookieConsentBanner.vue` | create |
| `resources/js/Components/Privacy/PrivacyPreferencesModal.vue` | create |
| `resources/js/Utils/consent.js` | create |
| `resources/views/partials/analytics.blade.php` | create |
| `resources/views/app.blade.php` | modify (replace inline gtag) |
| `resources/js/Layouts/{AppLayout,AuthenticatedLayout,GuestLayout,BrandedAuthLayout}.vue` | modify (mount banner) |

## Tests to Write

- `tests/Feature/Privacy/ConsentEndpointTest.php` — valid/invalid categories,
  cookie queued, receipt persisted, user attach, GPC flag, throttle.
- `tests/Feature/Privacy/AnalyticsGatingTest.php` — rendered HTML contains
  consent-default block before any `gtag('config')`; page with GPC header
  never renders an eager update to `granted`.
- Banner visibility logic unit-tested via shared-prop permutations.

## Risks / Blockers

- Inline blade JS must stay CSP-friendly if a CSP is later added — keep it a
  single partial.
- GPC-wins-over-prior-consent is the conservative reading; note for counsel.
- Cookie size: keep consent payload compact (<1KB).

## Definition of Done

All success criteria demonstrated in a real browser; tests green (no new
failures vs baseline); pint clean; STATE log updated with the verification
matrix results.

STOP. Await phase approval.

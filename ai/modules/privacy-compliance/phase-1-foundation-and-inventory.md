---
plan: privacy-compliance
phase: 1
title: Foundation & Data Inventory
status: in_progress
last_updated: 2026-07-10
depends_on: []
checkpoint: gpc
---

# Phase 1 — Foundation & Data Inventory

## Goal

Establish the single source of truth for the privacy program: a complete cookie
and data inventory, the `config/privacy.php` registry, the consent-receipt
persistence layer, and server-side GPC detection. Everything later phases
consume is defined here.

## Success Criteria

- `config/privacy.php` exists and enumerates every cookie the app sets, with
  category, purpose, provider, and lifetime — verified against a real browser
  session.
- `privacy_consents` table stores consent receipts keyed by visitor UUID with
  optional `user_id`, categories JSON, `gpc_applied`, and policy version.
- Requests carrying `Sec-GPC: 1` are detected by middleware and the flag is
  available to every Inertia page via shared props.
- All new code covered by Pest tests; no new failures vs the ~71-failure baseline.

## Sub-Phases

### 1.1 Cookie & data inventory (`inventory` checkpoint)
- Crawl a local browser session (guest + authenticated) and record every cookie:
  expected set today — Laravel session, `XSRF-TOKEN`, `remember_web_*`
  (essential); `_ga`, `_ga_*` from GA4 (analytics). Check Pinia/localStorage
  usage (`useCartStore`, `useQuoteStore`) and document as local storage, not
  cookies.
- Document data collected by forms (contact, repair request, registration,
  checkout) and by GA4.
- Deliverable: `data-inventory.md` in this module directory — feeds the cookie
  policy table and privacy policy disclosures.

### 1.2 Privacy config registry (`config` checkpoint)
- Create `config/privacy.php`:
  - `categories`: `essential`, `analytics`, `marketing` (labels + descriptions).
  - `cookies`: registry entries from 1.1.
  - `policy_version` (date string), `consent_cookie` (name
    `cs_privacy_consent`, lifetime 365 days).
  - `dsr`: response deadline days (45), extension days (45), verification link
    TTL (72h).
  - `retention`: consent receipts (5 years), completed DSRs (24 months),
    unverified DSRs (30 days).
  - `legal_hold`: model/field map of records that are anonymized rather than
    deleted (orders, payments, invoices).

### 1.3 Consent persistence (`persistence` checkpoint)
- `php artisan make:model PrivacyConsent -mf --no-interaction`.
- Migration: `id`, `visitor_uuid` (indexed), nullable `user_id` FK, `categories`
  JSON, `gpc_applied` bool default false, `policy_version` string,
  `ip_hash` (sha256, never raw IP), `user_agent` (truncated), timestamps.
- Model: casts, `user()` relation, scope `latestForVisitor()`. **No
  CompanyScope** — consent is per-person, not per-tenant.

### 1.4 GPC middleware + Inertia share (`gpc` checkpoint)
- `DetectGpcSignal` middleware (alias `gpc`, appended to `web` group in
  `bootstrap/app.php`): sets a request attribute when `Sec-GPC: 1` is present.
- Extend `HandleInertiaRequests::share()` with a `privacy` prop:
  `{ gpc: bool, consent: {categories, version} | null, policyVersion }` —
  consent parsed from the `cs_privacy_consent` cookie (unencrypted-cookies list
  so JS can read it; contains no secrets).

## Files to Create/Modify

| File | Action |
|---|---|
| `ai/modules/privacy-compliance/data-inventory.md` | create (deliverable of 1.1) |
| `config/privacy.php` | create |
| `database/migrations/*_create_privacy_consents_table.php` | create |
| `app/Models/PrivacyConsent.php` | create |
| `database/factories/PrivacyConsentFactory.php` | create |
| `app/Http/Middleware/DetectGpcSignal.php` | create |
| `app/Http/Middleware/HandleInertiaRequests.php` | modify (share `privacy` prop) |
| `bootstrap/app.php` | modify (register middleware, cookie encryption exception) |

## Tests to Write

- `tests/Feature/Privacy/GpcDetectionTest.php` — request with/without `Sec-GPC`
  header exposes correct shared prop.
- `tests/Feature/Privacy/PrivacyConsentModelTest.php` — factory, casts,
  visitor/user lookup scopes.
- Config sanity test: every registered cookie has a category that exists.

## Risks / Blockers

- Cookie inventory may surface unexpected third-party cookies (e.g., picsum
  placeholder images are third-party requests but not cookie-setters — verify).
- `cs_privacy_consent` must be excluded from Laravel cookie encryption or the
  front end can't read it.

## Definition of Done

Inventory doc committed; config registry complete; migration runs; middleware
and shared props verified by tests; `php artisan test` no new failures;
`vendor/bin/pint --dirty` clean.

STOP. Await phase approval.

# Data & Cookie Inventory — Colorado Supply

Compiled 2026-07-10 (Phase 1.1). Source of truth for `config/privacy.php` and
the cookie policy table. Update this file **and** the config whenever a cookie
or tracker is added.

## Cookies

| Cookie | Set by | Category | Purpose | Lifetime |
|---|---|---|---|---|
| `colorado_supply_session` | Laravel (`config/session.php`) | essential | Session state (auth, CSRF pairing, flash) | 120 min, rolling |
| `XSRF-TOKEN` | Laravel | essential | CSRF protection for SPA/axios requests | 120 min |
| `remember_web_*` | Laravel auth ("remember me", `web` guard) | essential | Persistent login | 5 years (framework default) |
| `remember_admin_*` | Laravel auth (`admin` guard, Filament) | essential | Persistent admin login (employee-facing) | 5 years |
| `cs_privacy_consent` | This module (Phase 2) | essential | Stores the visitor's consent choices + GPC honor state | 365 days |
| `_ga` | Google Analytics 4 (`gtag.js`) | analytics | Distinguishes visitors (client ID) | 2 years |
| `_ga_RZ06XS51X0` | Google Analytics 4 | analytics | Session state for GA4 property `G-RZ06XS51X0` | 2 years |

Notes:
- GA4 cookies load **unconditionally** today from `resources/views/app.blade.php`;
  Phase 2 gates them behind Consent Mode v2.
- No marketing/advertising cookies exist today; the `marketing` category is
  registered for future use and stays empty in the registry.
- Filament admin uses the same session/XSRF cookies (employee context).
- `https://picsum.photos` placeholder product images are third-party *requests*
  (IP/user-agent exposure) but set no cookies; flagged for replacement with
  first-party images before production regardless.

## Browser storage (not cookies — disclose in cookie policy "local storage" section)

| Key | Type | Purpose |
|---|---|---|
| `colorado-supply.cart` | localStorage | Guest/authed cart line items (product id, qty, location) |

## Personal data collected server-side

| Source | Data | Where stored |
|---|---|---|
| Registration (`/register`) | name, email, password hash, company association | `users` |
| Contact form (`FrontEnd/Contact.vue`) | name, email, message | mail / CRM activities |
| Repair request form | name, email, phone, company, equipment details | repair request records |
| Quotes & checkout | name, email, billing/shipping address, order contents | `quotes`, `orders`, `payments` (card data handled by Stripe, not stored locally) |
| CRM (staff-entered) | customer contact details, opportunity notes | `customers`, `opportunities`, `activities` |
| Consent receipts (this module) | visitor UUID, categories, GPC flag, hashed IP, truncated user agent | `privacy_consents` |
| DSR intake (Phase 4) | requester name, email, request details | `data_subject_requests` |

## Third-party data recipients

| Recipient | What | Basis / notes |
|---|---|---|
| Google (GA4) | Pageviews, client ID, device/geo coarse data | "Sharing" under CCPA definitions when ad features active → consent-gated in Phase 2; GA4 property settings checklist in Phase 5 |
| Stripe | Payment card data, billing identity | Payment processing (service provider); PCI handled by Stripe |
| Mailgun | Email addresses + message content for transactional mail | Service provider |
| Sentry | Error traces (may incidentally contain user identifiers) | Service provider; scrubbing config reviewed in Phase 5 hardening |
| SAM.gov API | Outbound queries only (no visitor data sent) | Not a data recipient |

## Employment / B2B context (exempt in CO, covered in CA)

- Filament admin users = employees → excluded from the consumer-facing consent
  UI; CCPA employee-notice handled as an internal HR document, out of module scope.
- CRM business contacts are B2B data — exempt under CPA, **covered** under
  CCPA; DSR workflow (Phase 4) therefore accepts requests from any individual,
  including business contacts.

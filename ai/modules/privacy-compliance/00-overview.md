# Privacy & Cookie Compliance Module — Overview

## Problem Statement

Colorado Supply's public site and customer portal have **no privacy compliance
infrastructure**: GA4 loads unconditionally with no consent gating, there is no
privacy policy, no Global Privacy Control (GPC) handling, no "Your Privacy
Choices" link, and no way to receive or track data subject requests. As a
Colorado-based government contractor, the site must comply with the 2026
landscape of **20 US state privacy laws** — most critically GPC/universal
opt-out recognition (required in 11+ states including Colorado, and the subject
of a coordinated CA/CO/CT enforcement sweep since Sept 2025) — and should
present a credible privacy posture to government procurement customers.

Full legal research and audit findings: `research-privacy-legal.md`.

## Confirmed Design Decisions (user-approved 2026-07-10)

1. **In-house consent management** — no third-party CMP. Laravel/Vue-native
   banner, GPC detection, consent-gated GA4 via Google Consent Mode v2.
2. **US state law scope only** — strictest-common-denominator baseline
   (CCPA/CPRA + Colorado Privacy Act + GPC states). No GDPR/EU opt-in model.
3. **Full in-app DSR workflow** — public request form, identity verification,
   Filament admin queue with 45-day deadline tracking, automated export/delete.

## Constraints

- Opt-out model (US), not opt-in: cookies may set by default **except** analytics/
  marketing for GPC visitors and prior opt-outs. Essential cookies always allowed.
- Consent state must work for **anonymous visitors** (cookie-keyed) and
  **authenticated users** (persisted, survives devices), and must be recorded
  server-side for demonstrable compliance.
- Must work with **Inertia SSR** (banner renders server-side safely) and not
  regress the SSR gateway fixes.
- Filament admin panel is employee-facing (employment-context data, exempt) —
  no banner there, but the DSR queue lives there.
- Analytics must never receive FCI/CUI or SAM-workspace data (federal contractor
  posture; see research §1).
- No new paid services.

## Architecture (textual)

**Consent core**
- `config/privacy.php` — single source of truth: cookie registry (name, provider,
  purpose, category, lifetime), consent categories (`essential`, `analytics`,
  `marketing`), policy version, DSR deadlines, retention periods.
- `privacy_consents` table — consent receipts: visitor UUID (from consent
  cookie), nullable `user_id`, categories JSON, `gpc_applied` bool, policy
  version, timestamps.
- `cs_privacy_consent` first-party cookie (12 mo) — categories + version +
  visitor UUID; read by middleware and the front-end bootstrap.
- `DetectGpcSignal` middleware — reads `Sec-GPC: 1`; shares GPC + consent state
  to all Inertia pages via `HandleInertiaRequests::share()`.
- `ConsentController` — POST endpoint to accept/decline categories; writes
  cookie + receipt row.

**Front-end**
- `CookieConsentBanner.vue` — bottom banner, Accept all / Essential only /
  Preferences; auto-suppressed with an "honored your GPC signal" notice when GPC
  is present; meets 44px touch-target and WCAG AA contrast standards.
- `PrivacyPreferencesModal.vue` — per-category toggles, reachable any time from
  the footer "Your Privacy Choices" link.
- GA4 bootstrap rewritten in `app.blade.php` to Google Consent Mode v2:
  defaults **denied**, `update` fired from stored consent/GPC state.

**Policy pages**
- `/privacy-policy` and `/cookie-policy` Inertia pages; cookie table rendered
  from `config/privacy.php` so the disclosure never drifts from reality.
- Footer links on all layouts: Privacy Policy, Cookie Policy, **Your Privacy
  Choices** (incl. "Do Not Sell or Share My Personal Information").

**DSR workflow**
- `data_subject_requests` table + `DataSubjectRequest` model; enums
  `DsrType` (access, deletion, correction, portability, opt_out, appeal) and
  `DsrStatus` (pending_verification, verified, in_progress, completed, denied,
  appealed) following the existing `app/Enums/` pattern.
- Public form at `/privacy/requests`; email verification via signed URL
  (no account required); throttled.
- Filament resource `DataSubjectRequestResource` — queue with due-date
  countdown (45 days statutory + one 45-day extension), status workflow,
  denial-with-appeal path, action audit log (integrates with existing
  audit-logging module).
- `UserDataExportService` — compiles user + orders + quotes + activities into
  JSON/CSV download. `UserDataDeletionService` — anonymizes personal fields
  while preserving order/tax records under legal-hold rules.

**Retention**
- Scheduled purge jobs driven by `config/privacy.php` retention map
  (consent receipts ≥ proof window, completed DSRs, stale unverified requests).

## Data Flow

```
Visitor request → DetectGpcSignal middleware → Inertia share (gpc, consent)
  → CookieConsentBanner (or suppressed: GPC/no prior choice needed)
  → ConsentController POST → cs_privacy_consent cookie + privacy_consents row
  → blade GA4 bootstrap: Consent Mode v2 default denied → update per consent

Consumer → /privacy/requests form → signed email verification → verified DSR
  → Filament queue (due_at countdown) → export/delete/correct services
  → completion email → receipt + audit log → retention purge
```

## Phases

| Phase | Name | Depends on | Summary |
|---|---|---|---|
| 1 | `phase-1-foundation-and-inventory.md` | — | `config/privacy.php`, cookie/data inventory, consent receipt table + model, GPC middleware |
| 2 | `phase-2-consent-management.md` | 1 | Banner + preferences UI, consent endpoint, Consent Mode v2 GA4 gating |
| 3 | `phase-3-policy-pages-and-links.md` | 1, 2 | Privacy/cookie policy pages, footer + "Your Privacy Choices" links |
| 4 | `phase-4-dsr-workflow.md` | 1 | DSR model/enums, public form + verification, Filament queue, export/delete services |
| 5 | `phase-5-retention-and-hardening.md` | 1–4 | Retention purge jobs, throttling/abuse hardening, compliance checklist mapping, full test pass |

## Known Risks

| Risk | Mitigation |
|---|---|
| Plan is engineering compliance, not legal advice | Flag policy text for counsel review before launch (explicit sub-phase in Phase 3) |
| CPA/CCPA thresholds may not currently apply | Build anyway (research §4); low cost now vs retrofit under enforcement |
| GA4 console settings (data retention, Google Signals) live outside the repo | Phase 5 includes a documented manual checklist for the GA4 property |
| Deletion vs. legal retention conflict (orders, tax, federal contract records) | Anonymize-not-delete strategy with explicit legal-hold list in `config/privacy.php` |
| Email verification depends on mail delivery (Mailgun flaky in local dev) | Known local-only issue; tests use `Mail::fake()` |
| SSR gateway sensitivity | Banner reads shared props only; no browser-only APIs at setup time; SSR smoke test in Phase 2 |
| ~71 pre-existing test failures in the suite | Phases assert **no new failures** relative to that baseline |

## Success Criteria (module-level)

1. With GPC enabled, GA4 never sets analytics cookies and the site records the
   honored signal — verifiable in browser devtools.
2. Without consent action, Consent Mode v2 defaults stay denied; after "Accept
   all", GA4 operates; after opt-out, it stops within the same session.
3. Privacy policy, cookie policy, and "Your Privacy Choices" reachable from
   every customer-facing page footer.
4. A DSR can be submitted, verified, worked, and completed inside the statutory
   clock, with export/delete artifacts and an audit trail.
5. `php artisan test` shows no regressions vs baseline; `vendor/bin/pint --dirty` clean.

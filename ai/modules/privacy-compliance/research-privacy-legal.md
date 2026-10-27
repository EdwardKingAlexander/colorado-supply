# Privacy & Cookie Compliance Research — Legal Baseline (2026)

> Research performed 2026-07-10 via web search (Gemini CLI was quota-exhausted).
> **This is engineering research, not legal advice.** Policy text and final scope
> should be reviewed by counsel before production launch.

## 1. The regulatory landscape that applies to this site

Colorado Supply is a **Colorado-based B2B/government supply company** with a public
marketing site, an e-commerce store, and authenticated customer portals. The relevant
regimes, in order of practical importance:

### US state comprehensive privacy laws (the core of this feature)
- **20 states** have comprehensive privacy laws in effect as of January 2026
  (Indiana, Kentucky, Rhode Island activated January 2026).
- **All use an opt-out model** — no US state requires an EU-style opt-in cookie
  banner before setting cookies. What they require instead:
  - Honor **opt-out of sale / targeted advertising** requests.
  - **Recognize Global Privacy Control (GPC) / universal opt-out signals** —
    required in at least 11 states: **California, Colorado, Connecticut, Delaware,
    Maryland, Minnesota, Montana, New Jersey, New Hampshire, Oregon, Texas**.
  - Post a compliant **privacy policy** describing data collected, purposes,
    sharing, and consumer rights.
  - Provide **data subject rights**: access, correction, deletion, portability,
    opt-out of sale/share/targeted-ads/profiling; respond within **45 days**
    (one 45-day extension allowed); provide an **appeal process** (CO, VA, CT, etc.).
- **Enforcement is real**: on 2025-09-09 the California PPA, Colorado AG, and
  Connecticut AG launched a **coordinated investigative sweep** specifically
  targeting failure to honor opt-out preference signals (GPC). Multi-state,
  technical, and exactly the gap this site has today.

### Colorado Privacy Act (home-state law) — key specifics
- Applies to controllers doing business in CO that process personal data of
  **≥100,000 CO consumers/yr**, or **≥25,000** if any revenue/discount is derived
  from selling personal data. No general revenue threshold.
- **UOOM (GPC) recognition mandatory since 2024-07-01.**
- **B2B and employment data are exempt** ("consumer" = individual acting in a
  personal/household context) — unlike California.
- Colorado Supply may be **below these thresholds today**; see §4 for why we
  build compliance anyway.

### California CCPA/CPRA — strictest common denominator
- Covers **B2B contacts and employee data** (the temporary exemptions lapsed).
- Requires a **"Do Not Sell or Share My Personal Information"** link (or the
  unified "Your Privacy Choices" link) if personal info is sold/shared —
  **GA4 with default ad features/Google Signals is widely treated as "sharing"
  for cross-context behavioral advertising**.
- GPC must be honored as a valid opt-out request.

### Federal contractor obligations (adjacent, mostly NOT this feature)
- **FAR 52.204-21** (15 basic safeguarding controls for Federal Contract
  Information) and **NIST SP 800-171 / CMMC** (CUI) govern *information systems
  and security controls*, not public-website cookies. They are handled by the
  separate gov/b2b readiness workstream (audit logging, admin account security).
- Practical intersections this feature must respect:
  - **Never expose FCI/CUI or SAM-opportunity workspace data to third-party
    analytics.** Analytics must stay off authenticated federal-workspace pages
    or be consent-gated and data-minimized.
  - The privacy policy should accurately describe the government-contracting
    data handling posture (a credibility item for agency customers).
- There is **no federal law requiring a cookie banner**; the Privacy Act of 1974
  and OMB memos apply to federal agencies' own systems, not a contractor's
  commercial website.

## 2. What the site does today (audit findings, 2026-07-10)

| Finding | Compliance implication |
|---|---|
| GA4 (`gtag.js`, `G-RZ06XS51X0`) loads unconditionally in `resources/views/app.blade.php` | No consent gating, no GPC handling — the exact pattern in the 2025 tri-state sweep |
| No privacy policy or cookie policy routes exist | Required by every state law once thresholds/data-sale apply; baseline trust requirement for gov contracting |
| No GPC / `Sec-GPC` handling anywhere | Required in 11+ states incl. Colorado |
| No "Your Privacy Choices" / "Do Not Sell or Share" link | CCPA requirement given GA4 sharing |
| No DSR intake or tracking | 45-day statutory deadlines can't be evidenced |
| Sanctum session/XSRF cookies, Pinia cart state | Strictly-necessary category — allowed without consent, must be disclosed in cookie policy |
| Filament admin (employees) | Employment-context data — exempt in CO and most states; CA employee rights handled via internal policy, out of this feature's scope |

## 3. Consent architecture standard (opt-out model, in-house build)

- **Google Consent Mode v2** is the correct GA4 gating mechanism: set defaults to
  `denied` for `analytics_storage`, `ad_storage`, `ad_user_data`,
  `ad_personalization` before `gtag('config')`, then `update` on consent.
- **GPC detection**: server-side via the `Sec-GPC: 1` request header; client-side
  via `navigator.globalPrivacyControl === true`. A GPC signal = automatic opt-out
  of analytics/advertising categories for that visitor; must be honored without
  requiring an account or extra clicks, and recorded.
- **Consent record-keeping**: states expect demonstrable compliance — persist
  consent state (categories, timestamp, policy version, GPC flag) server-side.
- Cookie categories: `essential` (always on), `analytics`, `marketing`
  (empty today, future-proofing).

## 4. Why build this even if CPA thresholds aren't met yet

1. CCPA's B2B coverage + GA4 "sharing" can apply regardless of CO thresholds if
   California traffic/revenue thresholds are met ($26.63M revenue OR 100k CA
   consumers OR 50% revenue from selling data — likely not met today either, but:)
2. The GPC sweep shows regulators test websites directly; retrofits are costlier
   than building the gate now.
3. Government procurement customers evaluate vendor privacy posture; a visible,
   correct privacy program is a sales asset for federal contracting.
4. Thresholds are measured per calendar year and the store is growing.

## Sources

- [US State Privacy Laws in 2026: 20 States — Nixon Digital](https://www.nixondigital.io/blog/us-state-privacy-laws/)
- [US State Privacy Law Tracker 2026 — Consenteo](https://www.consenteo.com/knowledge-hub/legal/us_state_privacy_law_tracker_2026)
- [20 State Privacy Laws in Effect in 2026 — MultiState](https://www.multistate.us/insider/2026/2/4/all-of-the-comprehensive-privacy-laws-that-take-effect-in-2026)
- [US Cookie Laws: State-by-State Guide (2026) — Recording Law](https://www.recordinglaw.com/us-laws/data-privacy-laws/us-cookie-laws-by-state/)
- [Cookie Banner Requirements: US & EU Rules (2026) — Recording Law](https://www.recordinglaw.com/us-laws/data-privacy-laws/cookie-banner-requirements/)
- [Colorado Privacy Act — Colorado Attorney General](https://coag.gov/resources/colorado-privacy-act/)
- [Colorado Privacy Act: What you need to know in 2026 — Ketch](https://www.ketch.com/regulatory-compliance/colorado-privacy-act-cpa)
- [Colorado Privacy Act Requirements — Osano](https://www.osano.com/articles/colorado-privacy-act-cpa)
- [FAR 52.204-21 Basic Safeguarding — acquisition.gov](https://www.acquisition.gov/far/52.204-21)
- [FAR Council Proposes NIST SP 800-171 for Non-Defense Contractors — Holland & Knight](https://www.hklaw.com/en/insights/publications/2025/02/far-council-proposes-compliance-with-nist-sp-800-171)
- [Privacy Laws 2026: Enforcement & Compliance Guide — Secure Privacy](https://secureprivacy.ai/blog/privacy-laws-2026)

# Analytics Measurement and Observability — Overview

## Problem Statement

Colorado Supply currently loads GA4 behind the privacy-consent controls, but it
does not emit a complete ecommerce or lead funnel. Search Console is not wired
into the application, the existing customer dashboard is not an owner-facing
business analytics view, and the installed Sentry integration has not been
validated against a real Sentry project.

The goal is to implement four complementary capabilities without committing to
a paid analytics product:

1. GA4 customer-journey measurement.
2. Google Search Console organic-search measurement.
3. First-party owner reporting from Colorado Supply's own business records.
4. Sentry production error monitoring.

## Cost Boundary

| Capability | Starting cost | Boundary |
|---|---:|---|
| Google Analytics 4 | $0 | Google's standard Analytics product is free; Analytics 360 is explicitly out of scope. |
| Google Search Console | $0 | Requires a Google account and verified ownership of the production domain. |
| First-party reporting | $0 additional SaaS cost | Uses the existing Laravel application and database; normal hosting/database resource usage still applies. |
| Sentry Developer | $0 | One user, currently 5,000 errors/month and 5 million spans/month; paid upgrades and pay-as-you-go are out of scope. |

No paid account, trial conversion, advertising pixel, session replay product,
or pay-as-you-go setting may be enabled by implementation work. If a provider
changes its free-tier terms, pause deployment and ask the orchestrator.

## Constraints

- Comply with `PROJECT_CANNON.md` and the active privacy-compliance module.
- GA4 events may fire only when analytics consent is granted and GPC is not
  active. Marketing/ad consent remains denied.
- Do not send names, email addresses, phone numbers, postal addresses, company
  contact data, payment data, user IDs, raw site-search text, SAM.gov search
  text, solicitation contents, uploaded-document contents, or other CUI-like
  information to GA4 or Sentry.
- Authenticated SAM/federal workspace, admin, checkout payment, and other
  sensitive screens remain outside browser analytics unless a later privacy
  review explicitly approves a narrow event.
- GA4 `transaction_id` may use the non-secret public order number, solely for
  purchase deduplication. It must never use a payment processor token.
- First-party reports must enforce company/role authorization and must derive
  business facts from authoritative domain records rather than trusting
  browser events.
- Search Console and Sentry require owner-created external accounts. Code can
  prepare and validate integration points, but credentials/verification values
  are entered directly in deployment configuration, never committed.
- Preserve the heavily modified worktree and do not modify unrelated privacy,
  email-verification, vendor, or generated SSR assets.

## Architecture (Textual)

```text
Public store/repair interaction
  -> consent-aware analytics utility
  -> GA4 recommended event (only after analytics consent; no PII)

Production public URLs
  -> sitemap.xml + robots.txt + ownership verification
  -> Google Search Console
  -> query/page/impression/click/indexing reports in Google's console

Orders + order items + payments + quotes + repair requests
  -> authorized Laravel aggregation service
  -> owner/admin Filament analytics dashboard
  -> no third-party transmission

Unhandled Laravel exception/job failure
  -> existing Sentry Laravel integration
  -> existing recursive PII scrubber
  -> Sentry Developer project with low trace sampling + email alert
```

## Data Flow and Event Taxonomy

### GA4 events

- Discovery: `view_item_list`, `select_item`, `view_item`, `search`.
- Cart: `add_to_cart`, `remove_from_cart`, `view_cart`.
- Checkout: `begin_checkout`, `add_shipping_info`, `add_payment_info`.
- Outcomes: `purchase`, `refund`.
- Leads/accounts: `generate_lead`, `sign_up`, `login` where the route is
  approved and the payload contains no identity data.

Site search reports only a normalized search category and result-count band;
raw search terms remain first-party and are not transmitted. Lead events carry
only a coarse `lead_type` such as `quote` or `repair`.

### First-party KPIs

- Revenue, paid orders, order count, average order value, refunds.
- Product/category performance from order items.
- Quote and repair-request volume/status conversion.
- Payment failure rate by processor and period, without payment credentials.
- Funnel ratios where authoritative start/completion records exist.

The already-complete normal-user dashboard remains the customer purchasing
view. This module adds an owner/admin business view and reuses existing domain
records; it does not replace or duplicate customer reporting.

## External Owner Actions

- Search Console: sign in, add `https://cogovsupply.com` or a domain property,
  choose DNS verification where possible, add the verification record, and
  submit the generated sitemap.
- Sentry: create a free Developer organization/project, copy the Laravel DSN
  directly into the production environment as `SENTRY_LARAVEL_DSN`, and keep
  billing/pay-as-you-go disabled.
- GA4: retain the existing property, mark the approved business outcomes as key
  events, and verify them in DebugView/Realtime.

## Risks and Mitigations

- **Duplicate SPA events or purchases** — centralize dispatch, use Inertia
  navigation hooks deliberately, clear ecommerce state before events, and use
  stable transaction IDs with regression tests.
- **Consent regression** — the analytics utility must fail closed and automated
  tests must cover denied, granted, revoked, stale-policy, and GPC states.
- **PII/CUI leakage** — use allowlisted payload builders and test forbidden
  field/value patterns; do not pass arbitrary objects to third parties.
- **Misleading business reports** — define statuses and date semantics once,
  use server-side aggregates, and test fixtures across companies and refunds.
- **Free-tier overage** — keep Sentry trace sampling conservative, disable
  replay/profiling/log ingestion, and use quota alerts where the free plan
  permits them.
- **External-account blocker** — complete code/test preparation independently,
  then leave only documented owner actions for production validation.

## Phase List

| # | Phase | Outcome |
|---|---|---|
| 1 | Measurement foundation and Search Console | Configurable GA bootstrap, consent-safe dispatch, sitemap/robots/verification support |
| 2 | GA4 ecommerce and lead events | Complete public store/lead event coverage with deduplication and privacy tests |
| 3 | First-party business analytics | Authorized owner/admin KPI dashboard derived from application data |
| 4 | Sentry activation and end-to-end hardening | Existing integration finalized and validated on the free Developer plan |

Status tracking lives in `STATE.md`. Each phase requires separate approval and
validation under `PROJECT_CANNON.md`.

**STOP. Await plan approval before any implementation begins.**

# Phase 6 - Checkout, Dashboard, Reports, and SAM

## Goal

Reformat the densest authenticated workflows so checkout is thumb-friendly and
customer reporting/SAM data is readable without miniature desktop tables.

## Files to Create/Modify

- Store workflow pages:
  - `resources/js/Pages/Store/Checkout.vue`
  - `CheckoutPay.vue`, `CheckoutSuccess.vue`, `CheckoutCancel.vue`
- Dashboard pages/components:
  - `resources/js/Pages/Dashboard.vue`
  - `resources/js/Pages/Dashboard/Reports.vue`
  - route-reachable files under `resources/js/Components/Dashboard/`
- `resources/js/Pages/Sam/Favorites.vue`
- Optional focused mobile card/list components for orders, report rows, and SAM
  opportunities when they reduce breakpoint duplication.
- `tests/Browser/mobile-customer-workflows.spec.ts`
- Existing dashboard, report, SAM, checkout, Stripe, and PayPal tests.

## Implementation Requirements

- Checkout/contact/address fields use one column through 639 px, 48 px controls,
  appropriate `autocomplete`/`inputmode`, and a clear step/task hierarchy.
- Order summary follows the form on mobile or opens through an explicit summary
  disclosure; final total and primary submit remain visible without covering the
  keyboard or focused errors.
- Payment methods render as full-width 48-52 px choices with clear provider
  labels, disabled/loading/error states, and no gateway logic change.
- Success/cancel pages use 16 px body copy and 48 px next actions rather than a
  small centered desktop card.
- Dashboard filters become a mobile disclosure or stacked controls; KPIs use a
  stable one/two-column grid with readable labels and values.
- Recent orders and report preview use mobile cards/definition lists. Preserve a
  real table at larger breakpoints and retain every field/action in both views.
- Charts keep labels readable and expose the same data textually; no interaction
  may depend on hover.
- SAM favorites use opportunity cards on phones with grouped metadata and 48 px
  Favorite/Documents/Insights actions. Expanded document and insight areas
  remain within the opportunity card flow, with full-width upload/question
  controls and announced async states.
- Pagination and export controls meet target sizing and do not overflow.

## Tests

- Checkout: billing/shipping toggle, validation focus, order summary, submit,
  Stripe/PayPal loading/error paths, and success/cancel navigation at phone
  widths using mocked external redirects.
- Dashboard/report: filter disclosure, custom date fields, location dependency,
  card/table content parity, export action, chart textual data, empty states.
- SAM: favorite removal, document disclosure/upload/delete, insight question,
  async/error/empty states, and pagination using intercepted API responses.
- axe, screenshot, no-overflow, 48 px target, and focus-not-obscured assertions
  at 320/390/430 px and landscape.
- Existing targeted backend suites, build, SSR build, and Pint pass.

## Success Criteria

- Checkout can be completed at 320 px without horizontal scrolling, clipped
  fields, or obscured validation.
- Dashboard, reports, and SAM no longer require reading desktop-sized table text
  on a phone.
- Mobile and desktop presentations preserve identical data and actions.

**STOP. Await phase approval before implementation.**

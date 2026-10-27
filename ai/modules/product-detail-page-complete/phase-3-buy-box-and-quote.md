---
plan: product-detail-page
phase: 3
title: "Buy box: quantity, tiers, cart and Request a Quote"
status: completed
last_updated: 2026-10-10
depends_on: [phase-2]
checkpoint: sticky
---

# Phase 3 — Buy Box: Quantity, Tiers, Cart and Request a Quote

## Goal

A buy box that handles real B2B ordering: quantity with minimums, live tier
pricing, Add to Cart, and a Request a Quote action that creates a draft Quote.

## Success criteria

- Quantity cannot go below `minimum_order_quantity` (default 1) in the UI or
  the cart.
- Unit price and line total update as quantity crosses tier breaks.
- Unpriced products show only Request a Quote.
- Request a Quote creates a `draft` Quote owned by the user with a server-
  resolved price; it appears in Filament → Quotes.
- Sticky action bar on mobile.

## Sub-phases

### 3.1 — Price and quantity UI — `checkpoint: qty-ui`

- Price block: effective unit price "per {unit}", list price strikethrough
  when higher, "Your company price" label when company price applies, GSA /
  contract rows when present in the payload.
- Tier table ("1–24: $x · 25–99: $y · 100+: $z") only when tiers exist;
  current tier highlighted.
- Quantity stepper (−/input/+), min = MOQ, keyboard accessible; helper text
  "Minimum order: 25" only when MOQ > 1; line total.

**Done when:** component tests / manual checks at tier boundaries pass.

### 3.2 — Cart integration — `checkpoint: cart`

- `useCartStore.addItem(product, qty)` with the chosen quantity and the
  effective unit price; enforce MOQ when quantity is edited in the cart.
- Confirmation: inline "Added 25 to cart · View cart" (no fake timer-only
  feedback), announced via `aria-live`.

**Done when:** adding/editing respects MOQ; cart total matches the page.

### 3.3 — Request a Quote — `checkpoint: quote`

- `POST /store/product/{product}/quote-request` (web, auth, throttled):
  validates quantity ≥ MOQ and optional note (≤1000 chars).
- `app/Services/Store/ProductQuoteRequestService.php`: creates `Quote`
  (`status=draft`, `portal_user_id`, `company_id`, number `Q-YYYYMMDD-XXXX`,
  notes) + one `QuoteItem` with the resolved unit price (0 when unpriced) and
  `uom` = `unit_of_measure`; reuses the existing sales-rep resolution.
- Modal: quantity (prefilled), note, submit → success state with the quote
  number. Admin-guard users: allowed, quote has no `portal_user_id`
  (matches the admin checkout pattern from `stripe-integration-complete`).

**Done when:** feature tests cover create, MOQ rejection, unpriced item,
company-scoped product 404, throttle.

### 3.4 — Mobile sticky bar — `checkpoint: sticky`

- Below `lg`, a bottom bar with price + primary action appears once the main
  buy box scrolls out of view; respects safe-area insets.

**Done when:** verified at 390px in the browser.

## Tests

- `tests/Feature/Store/ProductQuoteRequestTest.php`
- Cart MOQ behaviour (JS logic covered by a small unit test if a JS test
  runner exists; otherwise browser verification documented in STATE).

## Risks

- `Quote` has a `CompanyScope`-style global scope; set `company_id`
  explicitly.
- No JS test runner may exist — confirm in phase; fall back to browser checks.

**STOP. Await phase approval.**

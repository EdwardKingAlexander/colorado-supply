# Store / Cart / Checkout Redesign — Overview

## Problem Statement

The customer-facing store currently treats the shopping cart as a "quote
builder":

- `useQuoteStore` (localStorage) holds cart items.
- The cart page (`resources/js/Pages/Store/QuoteView.vue`, route
  `store.quote`) is labeled "Quote Summary" / "Current Quote", with a
  "Generate Quote" button that calls `POST /api/v1/store/quote`. That
  endpoint creates a CRM `Quote` + `QuoteItem` rows and returns/emails a PDF.
  **No `Order` is created and no payment happens.**
- Product cards say "Add to Quote" (`ProductRow.vue`, `ProductDetail.vue`)
  instead of "Add to Cart".
- Stripe payments (from `ai/modules/stripe-integration-complete/`) are fully built but
  only reachable via `POST /api/v1/orders/{order}/checkout` — there is no UI
  path from the storefront cart to that endpoint. PayPal and Google Pay do
  not exist anywhere in the app.

We need a real **cart → checkout → order → payment** flow:

1. Rename "quote" → "cart" throughout the storefront UI (terminology only,
   per-component).
2. Replace the PDF-quote action on the cart page with a "Proceed to
   Checkout" flow that collects shipping/billing info and creates a real
   `Order` (+ `OrderItems`), exactly like `QuoteOrderingService::convert()`
   does for CRM quotes, but sourced from the cart.
3. Build a checkout page that lets the customer pay the new order via:
   - **Stripe** (card, plus Google Pay / Apple Pay wallets — Stripe Checkout
     Sessions support these automatically once enabled in the Stripe
     Dashboard for the account's domain — no extra app code beyond the
     existing `StripeCheckoutService`/webhook pipeline from
     `stripe-integration`).
   - **PayPal** (new gateway, built using the same
     "create → redirect → return/capture → webhook" shape as the Stripe
     module for consistency).

## Assumptions / Out of Scope

- The storefront (`/store/*`) is gated by `auth.web_or_admin` today (must be
  logged in on the `web` or `admin` guard — there is no guest checkout path
  currently). This module does **not** open the store to anonymous guests;
  every order created here has a `portal_user_id`. `Public.vue` (marketing
  page, not behind auth) gets the "Add to Cart" wording fix in Phase 1 only,
  since it doesn't render a real product catalog.
- "Google Pay" is delivered via Stripe Checkout's built-in wallet support.
  Enabling it requires a one-time **Stripe Dashboard** configuration (payment
  methods + domain registration for Apple Pay/Google Pay) — this is an
  external prerequisite, not app code, and will be called out again at the
  end of Phase 4.
- PayPal webhook signature verification requires a PayPal app
  (client id/secret) + webhook ID configured in the PayPal developer
  dashboard. The plan ships with `.env` placeholders
  (`PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_MODE`,
  `PAYPAL_WEBHOOK_ID`) and works end-to-end against PayPal's **sandbox**
  once those are filled in.
- The CRM "Quote" feature (`Quote`/`QuoteItem` models, `/api/v1/store/quote`,
  `QuoteOrderingService`) is untouched — it's a separate sales-pipeline
  concept (`ai/modules/.../quote`) from the storefront cart and continues to
  exist for that purpose. The storefront simply stops using it as the cart's
  "checkout" action.
- No new `PaymentStatus`/`OrderStatus` enum cases. Checkout creates the
  `Order` as `status = Draft`/`payment_status = Unpaid` (matching
  `QuoteOrderingService`'s convention for unpaid orders), and the existing
  Stripe webhook flow already calls `markAsPaid()` → confirms the order.

## Architecture (textual)

```
Storefront (Vue/Inertia, behind auth.web_or_admin)
  ProductRow / ProductDetail: "Add to Cart" -> useCartStore.addItem()
  Cart page (resources/js/Pages/Store/Cart.vue, route store.cart)
    - line items, quantities, locations (existing behavior, restyled)
    - "Proceed to Checkout" -> opens checkout address form

  Checkout page (resources/js/Pages/Store/Checkout.vue, route store.checkout)
    - shipping/billing address form (+ contact info, PO/job number, notes)
    - "Place Order" -> POST /api/v1/store/checkout
        -> StoreCheckoutController -> StoreCheckoutService::createFromCart()
        -> creates Order (draft/unpaid) + OrderItems from cart payload
        -> clears cart (useCartStore.clearCart())
        -> Inertia redirect to store.checkout.pay (order id)

  Payment step (resources/js/Pages/Store/CheckoutPay.vue, route store.checkout.pay)
    - shows order summary + two payment buttons:
      "Pay with Card / Google Pay / Apple Pay" (Stripe)
        -> POST /api/v1/orders/{order}/checkout (existing StripeCheckoutService)
        -> redirect to Stripe-hosted session.url
      "Pay with PayPal"
        -> POST /api/v1/orders/{order}/checkout/paypal (new PaypalCheckoutService)
        -> redirect to PayPal-hosted approval URL

Stripe -> existing /api/v1/stripe/webhook pipeline (unchanged)
PayPal -> POST /api/v1/paypal/webhook (new, mirrors Stripe webhook shape)
  -> PaypalWebhookController verifies signature via PayPal API
  -> PaypalEvent (idempotency, mirrors StripeEvent)
  -> ProcessPaypalWebhookEvent job -> PaypalPaymentSyncService
  -> Payment.markAsPaid() + Order.markAsPaid()

Return URLs (both gateways) -> store.checkout.success / store.checkout.cancel
  (existing pages from stripe-integration, reused as-is)
```

## Data Flow

```
Cart (localStorage, useCartStore) -> Checkout form -> Order (draft/unpaid) + OrderItems
   -> Stripe Checkout Session (Payment: pending, gateway=stripe)
        OR PayPal Order (Payment: pending, gateway=paypal)
   -> customer pays on gateway-hosted page
   -> webhook -> Payment.markAsPaid() + Order.markAsPaid() (confirms order)
   -> redirect -> store.checkout.success
```

## Risks

- **Renaming `useQuoteStore` / route `store.quote`**: touches several
  components (`StoreIndex.vue`, `ProductList.vue`, `ProductRow.vue`,
  `ProductDetail.vue`). Phase 1 enumerates every touch point found via
  grep so nothing is missed.
- **Guest vs. authenticated checkout**: addressed via the "Assumptions"
  section above — every order is tied to `portal_user_id` (the logged-in
  user), avoiding the larger architectural question of opening `/store` to
  guests.
- **PayPal SDK**: `paypal/paypal-server-sdk` (or raw HTTP via Guzzle against
  the v2 Orders API) needs to be added as a Composer dependency. Plan favors
  raw HTTP via Laravel's `Http` facade (mirrors `SamApiClient`'s pattern in
  this codebase) to avoid pulling in a heavy SDK, keeping the same
  "fakeable HTTP client in tests" pattern used for Stripe.
- **Two payment gateways racing**: a customer could start a Stripe session
  and a PayPal order for the same `Order`. Phase 2/4/5 ensure
  `createSessionForOrder`/`createOrderForOrder` both check
  `Order::canBePaid()` first and upsert a single `pending` `Payment` row
  *per gateway*; the webhook handlers already no-op once
  `payment_status = Paid`.

## Phase Table

| # | Phase | File |
|---|-------|------|
| 1 | Cart terminology & UI redesign | `phase-1-cart-terminology-and-ui.md` |
| 2 | Checkout order-creation backend | `phase-2-checkout-order-backend.md` |
| 3 | Checkout page UI (address + summary) | `phase-3-checkout-page-ui.md` |
| 4 | Stripe payment step (card + Google/Apple Pay wallets) | `phase-4-stripe-checkout-integration.md` |
| 5 | PayPal payment step (new gateway) | `phase-5-paypal-integration.md` |
| 6 | Testing & hardening | `phase-6-testing-hardening.md` |

See `STATE.md` for status tracking.

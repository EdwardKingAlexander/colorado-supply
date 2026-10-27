# Phase 4 — Stripe Payment Step (Card + Google Pay / Apple Pay)

## Goal

Wire a "Pay with Card" button into `Store/CheckoutPay.vue` that calls the
**existing** `POST /api/v1/orders/{order}/checkout` endpoint
(`StripeCheckoutController` from `ai/modules/stripe-integration-complete/`, already
production-ready) and redirects the browser to the returned Stripe Checkout
Session URL. No backend changes to the Stripe pipeline itself — this phase is
almost entirely the payment-method UI plus one small reusability check.

Google Pay / Apple Pay are **not** separate code paths: Stripe Checkout
Sessions automatically offer these wallets as payment methods on the hosted
page when (a) `automatic_payment_methods` / the account's enabled payment
methods include them, and (b) for Apple Pay, the domain is registered in the
Stripe Dashboard. This phase verifies the session is created in a way that
doesn't *prevent* wallets (i.e. doesn't hardcode `payment_method_types:
['card']` in a way that excludes them) and documents the Dashboard step as a
deployment prerequisite.

## Files to Create / Modify

- **`app/Services/Stripe/StripeCheckoutService.php`**: review
  `createSessionForOrder()` — if `payment_method_types` is explicitly set to
  `['card']`, either remove it (Stripe defaults to the methods enabled on the
  account, which includes wallets automatically) or confirm it's already
  absent. If present, removing it is a one-line change; re-run the existing
  `StripeCheckoutTest`/`StripeIntegrationFlowTest` suites afterward since they
  assert on session params via `FakeStripeHttpClient`.
- **`resources/js/Pages/Store/CheckoutPay.vue`** (from Phase 3): add a "Pay
  with Card" button inside the "Choose a payment method" section:
  - On click: `POST /api/v1/orders/{order.id}/checkout` via axios.
  - On success (`{ checkout_url }`): `window.location.href = checkout_url`
    (full browser redirect to Stripe's hosted page — matches the existing
    `success_url`/`cancel_url` round trip already implemented).
  - On error (Stripe `ApiErrorException` → `422` with `{ errors: { order: [...] } }`
    per `StripeCheckoutController`'s existing `ValidationException` handling):
    show the message inline, same pattern as Phase 3's form errors.
  - Loading state while the request is in flight (disable button, show
    spinner/text "Redirecting to Stripe…").
- No changes needed to `app/Http/Controllers/Api/V1/StripeWebhookController.php`,
  `ProcessStripeWebhookEvent`, or `StripePaymentSyncService` — the webhook
  pipeline already marks `Payment`/`Order` paid and the customer lands on
  `store.checkout.success` (existing page, reused).

## Tests to Write

- **`tests/Feature/Api/StripeCheckoutTest.php`** (existing, from
  stripe-integration): re-run as-is to confirm no regression if
  `payment_method_types` was changed. If a new assertion is warranted (e.g.
  asserting the param is *absent* from the request sent to
  `FakeStripeHttpClient`), add it here rather than creating a new file.
- **`tests/Feature/StoreCheckoutPageTest.php`** (from Phase 3): extend with a
  test that, given an `Order` owned by the user, the `CheckoutPay` page
  exposes enough order data (id, grand_total) for the frontend to call
  `/api/v1/orders/{order}/checkout` — i.e. assert the Inertia props include
  `order.id`.

## Manual Verification

- Per `CLAUDE.md`'s UI-testing guidance: run `composer run dev`, add an item
  to the cart, complete checkout, click "Pay with Card", and confirm the
  browser redirects to a Stripe-hosted Checkout page (test mode, using
  `STRIPE_KEY`/`STRIPE_SECRET` test keys already configured from
  stripe-integration Phase 1). Use Stripe's test card `4242 4242 4242 4242`
  to complete a payment and confirm redirect to `/store/checkout/{order}/success`
  with `payment_status = paid` shown.

## Deployment Prerequisite (document in STATE.md, not code)

- To actually surface Google Pay / Apple Pay buttons on the Stripe Checkout
  page in production, the Stripe Dashboard must have:
  - Apple Pay: domain verification file uploaded for the production domain.
  - Google Pay: enabled under Settings → Payment methods (enabled by default
    for most US accounts).
- This is an account-configuration step outside the codebase; note it in
  `STATE.md`'s log as a follow-up for whoever owns the Stripe account.

## Success Criteria

- "Pay with Card" button on `CheckoutPay.vue` successfully redirects to a
  real Stripe Checkout Session for the order created in Phases 2/3.
- Existing Stripe test suite (28 tests from `stripe-integration`) still
  passes unchanged or with the small addition noted above.
- `vendor/bin/pint --dirty` clean.
- `STATE.md` updated to `Awaiting Validation` with a log entry, including the
  Google Pay/Apple Pay Dashboard-prerequisite note.

STOP. Await phase approval.

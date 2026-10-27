# Phase 2 — Checkout Session Creation

## Goal

Allow an authenticated portal user (or guest, per existing order flow) to
start a Stripe Checkout Session for an `Order` and be redirected to Stripe's
hosted payment page.

## Files to Create/Modify

- `app/Services/Stripe/StripeCheckoutService.php`
  - `createSessionForOrder(Order $order): \Stripe\Checkout\Session`
  - Builds a single line item from `Order::getGrandTotalInCents()` and
    `Order::currency` (default `USD`), `mode = 'payment'`,
    `metadata = ['order_id' => $order->id]`,
    `success_url` / `cancel_url` pointing at new Inertia routes.
  - Guards: refuses to create a session if `! $order->canBePaid()`.
  - Creates/updates a `Payment` row (`method = card`, `status = pending`,
    `gateway = 'stripe'`, `gateway_session_id = $session->id`,
    `amount = $order->grand_total`, `currency`).
- `app/Http/Controllers/Api/V1/StripeCheckoutController.php`
  - `store(Order $order)` — authorizes the order belongs to the requesting
    user/company (respect `CompanyScope`), calls
    `StripeCheckoutService::createSessionForOrder`, returns
    `{ checkout_url: $session->url }` (frontend redirects via
    `window.location`).
- `routes/api.php` — add under the authenticated `v1` group:
  `POST /sam-opportunities/...` neighbors —
  `POST /orders/{order}/checkout` → `StripeCheckoutController::store`.
- `resources/js/Pages/Store/` — add/extend a checkout result page (or reuse
  existing order confirmation page) to handle `?session_id=` on
  `success_url`, and a cancel page/state for `cancel_url`.

## Out of Scope

- Webhook processing (Phase 3) — the success page should NOT mark the order
  paid client-side; it only shows a "processing" state until the webhook
  updates status.
- Refunds, subscriptions, saved payment methods.

## Tests

- `tests/Feature/Api/StripeCheckoutTest.php`
  - Authenticated user can create a checkout session for their own
    payable order → `Payment` row created with `gateway_session_id`.
  - Returns 403/404 for orders belonging to another company
    (`CompanyScope`).
  - Returns an error (no session created) if `! $order->canBePaid()`
    (e.g. already paid or refunded).
  - Mock `\Stripe\StripeClient` (or use Stripe's PHP test helpers) — no real
    network calls in tests.

## Success Criteria

- Hitting the checkout endpoint for a payable order returns a Stripe-hosted
  `checkout_url` and creates exactly one `pending` `Payment` row.
- Re-hitting the endpoint for the same order does not create duplicate
  `Payment` rows (update existing pending row instead).
- `php artisan test`, `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval before implementation.**

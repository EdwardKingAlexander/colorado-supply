# Phase 4 — Payment/Order Status Sync & Fulfillment

## Goal

Implement the actual state transitions triggered by webhook events from
Phase 3, using the existing `Payment` and `Order` model methods.

## Files to Create/Modify

- `app/Services/Stripe/StripePaymentSyncService.php`
  - `handleCheckoutCompleted(Session $session): void`
    - Finds `Payment` by `gateway_session_id`, sets
      `gateway_payment_intent_id` from `$session->payment_intent`.
  - `handlePaymentIntentSucceeded(PaymentIntent $intent): void`
    - Finds `Payment` by `gateway_payment_intent_id`, calls
      `Payment::markAsPaid()`, sets `gateway_charge_id`.
    - Calls `Order::markAsPaid()` (which also confirms the order per
      existing model logic).
  - `handlePaymentIntentFailed(PaymentIntent $intent): void`
    - Finds `Payment`, calls `Payment::markAsFailed($code, $message)`.
    - Calls `Order::markPaymentAsFailed()`.
  - `handleChargeRefunded(Charge $charge): void`
    - Finds `Payment` by `gateway_charge_id`, sets `status = refunded`,
      `gateway_refund_id`, `refunded_at`.
    - Sets `Order::payment_status = PaymentStatus::Refunded`.
- `app/Jobs/ProcessStripeWebhookEvent.php` — wire the four handler calls
  added in Phase 3 to this service.
- (If needed) `app/Notifications/OrderPaymentReceived.php` /
  `OrderPaymentFailed.php` — order confirmation email, **only if** an
  existing notification doesn't already cover this; check
  `app/Notifications/` first before adding.

## Out of Scope

- Refund *initiation* (admin-triggered) — that's Phase 5. This phase only
  reacts to `charge.refunded` webhooks (e.g. refunds issued directly in the
  Stripe dashboard).
- Shipment/fulfillment workflow beyond setting `fulfillment_status` if the
  existing `Order::markAsPaid()` doesn't already touch it — verify current
  behavior before adding new transitions.

## Tests

- `tests/Unit/Services/Stripe/StripePaymentSyncServiceTest.php`
  - Each handler transitions `Payment`/`Order` to the expected state from a
    realistic Stripe object fixture (use Stripe's PHP object constructors
    with fixture arrays, not live API calls).
  - Idempotent: calling the same handler twice with the same event doesn't
    double-apply (e.g. doesn't re-trigger confirmation emails).
- `tests/Feature/Api/StripeWebhookTest.php` (extend Phase 3 tests)
  - End-to-end: posting a `payment_intent.succeeded` webhook results in
    `Order::payment_status === Paid` and `Order::status === Confirmed`.

## Success Criteria

- Full webhook → order-paid flow verified via Pest feature test without
  hitting Stripe's real API.
- `php artisan test`, `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval before implementation.**

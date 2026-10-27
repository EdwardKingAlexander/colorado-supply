# Phase 3 — Webhook Handling & Idempotency

## Goal

Receive Stripe webhook events securely, verify signatures, dedupe via the
existing `StripeEvent` model, and dispatch processing without blocking the
HTTP response.

## Files to Create/Modify

- `routes/api.php` — add a public route (no `auth:sanctum`):
  `POST /api/v1/stripe/webhook` → `StripeWebhookController::handle`.
- `bootstrap/app.php` — exempt this route from CSRF, following the same
  pattern used for `routes/ai.php` (`withoutMiddleware(VerifyCsrfToken::class)`
  scoped to this route, or confirm `routes/api.php` is already stateless/CSRF
  free — verify before adding new exemptions).
- `app/Http/Controllers/Api/V1/StripeWebhookController.php`
  - Verifies `Stripe-Signature` header against
    `config('services.stripe.webhook_secret')` using
    `\Stripe\Webhook::constructEvent`.
  - Returns `400` on `\Stripe\Exception\SignatureVerificationException` or
    bad payload.
  - Checks `StripeEvent::isProcessed($event->id)`; if already processed,
    return `200` immediately (idempotent).
  - Persists a new `StripeEvent` row (`stripe_event_id`, `type`, `payload`).
  - Dispatches `App\Jobs\ProcessStripeWebhookEvent` with the `StripeEvent` id,
    returns `200` immediately.
- `app/Jobs/ProcessStripeWebhookEvent.php`
  - Loads the `StripeEvent`, dispatches to handler methods/classes based on
    `type`:
    - `checkout.session.completed`
    - `payment_intent.succeeded`
    - `payment_intent.payment_failed`
    - `charge.refunded`
  - Resolves the related `Order`/`Payment` via `metadata.order_id` /
    `gateway_session_id` / `gateway_payment_intent_id`, **bypassing
    `CompanyScope`** (`Order::withoutGlobalScopes()`).
  - Calls `StripeEvent::markAsProcessed()` after successful handling.
  - Unhandled event types: mark processed, log at `info` level, no-op.

## Out of Scope

- The actual `Payment::markAsPaid()`/`Order::markAsPaid()` business logic
  beyond what's needed to wire the job — full status-sync rules are detailed
  in Phase 4, but Phase 3 should call into those methods once they exist
  (Phase 4 can land the method bodies; Phase 3 can stub minimal versions if
  sequencing requires — confirm during phase approval).

## Tests

- `tests/Feature/Api/StripeWebhookTest.php`
  - Valid signature + new event → `200`, `StripeEvent` row created and
    marked processed, job dispatched (use `Bus::fake()` /
    `Queue::fake()`).
  - Invalid/missing signature → `400`, no `StripeEvent` row created.
  - Duplicate `stripe_event_id` → `200`, no second processing (job not
    dispatched again).
- `tests/Unit/Jobs/ProcessStripeWebhookEventTest.php`
  - Each event type routes to the correct handler.
  - Resolves `Order` across companies (bypassing `CompanyScope`).

## Success Criteria

- Webhook endpoint verifiable end-to-end with Stripe CLI
  (`stripe listen --forward-to`) in local dev.
- Replaying the same event id twice is a no-op the second time.
- `php artisan test`, `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval before implementation.**

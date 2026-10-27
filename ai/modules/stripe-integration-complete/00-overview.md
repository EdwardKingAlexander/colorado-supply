# Stripe Integration — Overview

## Problem Statement

Orders currently support `payment_method` (card/cash/check/wire/other) and the
`Payment` model already has gateway columns (`gateway`,
`gateway_payment_intent_id`, `gateway_charge_id`, `gateway_session_id`,
`gateway_refund_id`) plus a `StripeEvent` model/table for webhook idempotency.
None of this is wired up yet — `stripe/stripe-php` is installed but unused.

We need a Stripe Checkout-based payment flow so a customer can pay for an
`Order` online, have the result reliably reflected on `Order` and `Payment`
via webhooks, and give admins (Filament) visibility into payments/refunds.

## Constraints

- Must respect `CompanyScope` multi-tenancy — webhook processing runs without
  an authenticated user, so it must resolve/scope records explicitly (no
  reliance on `auth()`).
- Use Stripe **Checkout Sessions** (hosted), not custom Elements/PaymentIntent
  UI — minimal PCI burden, fastest to ship, matches "fastest implementation"
  guidance in the stripe-integration skill.
- Reuse existing schema (`Payment`, `StripeEvent`, `Order.getGrandTotalInCents()`)
  — no new tables unless a gap is found during a phase.
- Webhook endpoint must be CSRF-exempt and verify the Stripe signature.
- All Stripe API calls go through a single service class (no scattered
  `\Stripe\...` calls) so config/test mode is centralized.
- No code without an approved plan/phase, per `PROJECT_CANNON.md`.

## Architecture (textual)

```
Customer (Vue/Inertia Store) 
   -> POST /api/v1/store/orders/{order}/checkout
   -> StripeCheckoutService::createSessionForOrder(Order)
   -> Stripe Checkout Session created (mode=payment, metadata.order_id)
   -> Payment row created/updated (status=pending, gateway=stripe, gateway_session_id)
   -> 302 redirect customer to session.url

Stripe -> POST /api/v1/stripe/webhook (signature-verified, CSRF-exempt)
   -> StripeWebhookController
   -> StripeEvent::isProcessed() idempotency check
   -> dispatch ProcessStripeWebhookEvent job
   -> handlers update Payment + Order (markAsPaid / markAsFailed / refunded)
   -> StripeEvent::markAsProcessed()

Admin (Filament)
   -> Payment resource shows gateway refs + status
   -> Refund action -> StripeRefundService -> Stripe::refunds.create
   -> StripeEvent log page for debugging webhook activity
```

## Data Flow

```
Quote -> Order (status=draft, payment_status=unpaid)
   -> Checkout Session created -> Payment (pending, gateway_session_id)
   -> customer pays on Stripe-hosted page
   -> webhook: checkout.session.completed / payment_intent.succeeded
      -> Payment.markAsPaid() + Order.markAsPaid() (also confirms order)
   -> webhook: payment_intent.payment_failed
      -> Payment.markAsFailed() + Order.markPaymentAsFailed()
   -> webhook: charge.refunded
      -> Payment.status=refunded, gateway_refund_id set, Order.payment_status=refunded
```

## Risks

- **Webhook signature/secret misconfiguration** — wrong env var breaks all
  payment confirmation silently. Mitigate with config validation in Phase 1
  and explicit tests in Phase 6.
- **Multi-tenancy in webhooks** — must bypass `CompanyScope` deliberately
  (e.g. `Order::withoutGlobalScope(CompanyScope::class)`) when resolving by
  `metadata.order_id`, since there's no authenticated company context.
- **Duplicate/out-of-order webhook delivery** — handled via `StripeEvent`
  idempotency table (already exists) plus checking current `Payment`/`Order`
  status before transitioning.
- **Amount mismatches** — always derive Checkout line item amounts from
  `Order::getGrandTotalInCents()` at session-creation time; never trust
  client-supplied amounts.
- **Test mode vs live mode** — config must support both via `.env`; Phase 6
  covers Stripe test-card based Pest tests.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Foundation & configuration | `phase-1-foundation-config.md` |
| 2 | Checkout session creation | `phase-2-checkout-session.md` |
| 3 | Webhook handling & idempotency | `phase-3-webhook-handling.md` |
| 4 | Payment/order status sync & fulfillment | `phase-4-payment-order-sync.md` |
| 5 | Refunds & Filament admin tooling | `phase-5-refunds-admin.md` |
| 6 | Testing & hardening | `phase-6-testing-hardening.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per `PROJECT_CANNON.md`.

**STOP. Await approval before any implementation begins.**

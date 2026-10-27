# Phase 5 — Refunds & Filament Admin Tooling

## Goal

Give admins visibility into Stripe payments/events and a way to issue
refunds from the Filament admin panel.

## Files to Create/Modify

- `app/Services/Stripe/StripeRefundService.php`
  - `refund(Payment $payment, ?int $amountCents = null, ?string $reason = null): \Stripe\Refund`
  - Validates `$payment->status === Paid` and `gateway_charge_id` is set.
  - Calls Stripe refunds API, updates `Payment` (`status = refunded` only on
    full refund; partial refunds may need a `meta` note — confirm during
    phase approval whether `PaymentStatus` needs a `PartiallyRefunded` case,
    or if that's out of scope).
- `app/Filament/Resources/PaymentResource.php` (or wherever payments are
  currently surfaced — check for an existing resource first) — add a
  "Refund" `Action` visible only when `payment->status === Paid` and
  `gateway === 'stripe'`. Confirm modal collects amount (defaults to full)
  and reason.
- `app/Filament/Resources/StripeEventResource.php` (new, read-only) — table
  of `StripeEvent` rows (`type`, `stripe_event_id`, `processed_at`,
  `created_at`) for debugging. Add to an existing Filament navigation group
  (check `app/Filament/` for grouping conventions first).

## Out of Scope

- Subscriptions, saved cards, Stripe Customer Portal — not part of this
  module's scope (one-time order payments only).
- Dispute/chargeback handling beyond what `charge.refunded` already covers.

## Tests

- `tests/Feature/Filament/PaymentRefundActionTest.php`
  - Refund action visible/hidden based on `Payment::status` and `gateway`.
  - Triggering refund calls `StripeRefundService::refund` (mock the Stripe
    client) and updates `Payment` accordingly.
- `tests/Feature/Filament/StripeEventResourceTest.php`
  - Admin can list `StripeEvent` records (`livewire()` table assertions per
    existing CLAUDE.md testing patterns).

## Success Criteria

- Admin can view Stripe events and issue a refund for a paid order from
  Filament; `Payment.gateway_refund_id` populated afterward.
- `php artisan test`, `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval before implementation.**

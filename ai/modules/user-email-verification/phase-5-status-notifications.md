# Phase 5: Order Status-Change Notifications (Email + In-App Bell)

## Goal

When an order's status meaningfully changes, the buyer is notified by
email (always) and by an in-app notification bell (when they have an
account) — each notification linking to the phase-4 status tracker.

## Design Decisions

- **Channel choice (owner, 2026-07-10):** in-app bell via Laravel
  *database* notifications + email. No browser web-push in this module.
- **One parameterized notification** (`OrderStatusUpdated`) rather than a
  class per transition — payload carries the transition type, order
  number, and signed tracker URL.
- **Notifiable transitions** (confirmed against the current models and
  mutation paths on 2026-07-10):
  | Transition | Trigger source |
  |---|---|
  | Shipped (+ carrier/tracking) | Shipment `status` becomes `shipped`, or a shipment is created already shipped |
  | Delivered | Shipment `status` becomes `delivered` / `delivered_at` is set |
  | Fulfilled | Order `fulfillment_status` becomes `fulfilled` |
  | Cancelled / Refunded | Order `status` becomes `cancelled`; Stripe refund sync sets order `payment_status` to `refunded` |
  - Payment received/failed already have dedicated notifications
    (`OrderPaymentReceived` / `OrderPaymentFailed`) — extend those with the
    `database` channel rather than duplicating them; do NOT double-send.
    The Stripe/PayPal sync services currently use a mail-only anonymous
    route even for account orders, so they must delegate to the shared
    account-aware recipient resolver for the database channel to run.
  - Order placed is covered by the phase-4 confirmation mail — excluded.
- **Current admin workflow:** `CRM\OrderResource` is view-only and exposes no
  status mutation actions. Detection therefore belongs at the model/service
  layer and remains ready for future admin actions without coupling this
  phase to a Filament redesign.
- **Recipient resolution:** `$order->user` when present → standard
  `notify()` (mail + database). Guest/`customer_email`-only orders →
  `Notification::route('mail', ...)` (email only; no bell).

## Sub-Phases

### 5.1 Transition detection
- Observe order transitions from payment/refund services and any future
  admin edits; observe both shipment creation and shipment updates so
  shipped/delivered changes are not missed.
- `OrderObserver` (extend if one exists) watching `status`,
  `fulfillment_status`, `payment_status` dirty transitions +
  `ShipmentObserver` for `status`, `shipped_at`, and `delivered_at` changes;
  observers dispatch the notification. Guard against duplicate sends when
  several fields change in one save (single notification per save,
  most-significant transition).

### 5.2 Notification class
- `App\Notifications\OrderStatusUpdated implements ShouldQueue`;
  `via()` returns `['mail', 'database']` for users, `['mail']` for routed
  guests. Mail uses the phase-2 branded theme, subject carries the order
  number, CTA = signed tracker URL. Database payload: order id/number,
  transition, human label, tracker URL, timestamp.
- Add `database` channel to `OrderPaymentReceived`/`OrderPaymentFailed`
  `via()` with equivalent payloads.
- Replace the Stripe/PayPal services' mail-only routing with the shared
  account-aware order notifier so account holders actually receive both
  configured channels while guest orders remain mail-only.

### 5.3 Notification bell UI
- `notifications` table migration (framework standard) if not present.
- Bell component in `AuthenticatedLayout.vue`, which wraps the customer
  dashboard, reports, profile, and authenticated store pages: unread-count
  badge, dropdown of latest 10 (label, order
  number, relative time), click-through to tracker, "mark all as read".
- Endpoints: `GET /notifications` (latest + unread count) and
  `POST /notifications/read` — auth-gated, NOT verification-gated (an
  unverified account holder still sees their order updates).
- Data on page load + refresh on dropdown open; no websockets in scope.

### 5.4 Tests
- Each transition dispatches exactly one notification with correct
  channels (user vs guest matrix), `Notification::fake()`.
- Multi-field single save → single notification.
- Bell endpoints: unread count, list shape, mark-read; unverified user can
  access.
- Rendered mail contains order number + signed tracker URL.

## Files

| File | Change |
|------|--------|
| `app/Observers/OrderObserver.php`, `ShipmentObserver.php` | new/extended |
| `app/Notifications/OrderStatusUpdated.php` | new |
| `app/Notifications/OrderPaymentReceived.php` / `OrderPaymentFailed.php` | add database channel |
| `database/migrations/...create_notifications_table.php` | new (if absent) |
| `app/Http/Controllers/NotificationController.php` + routes | new |
| `resources/js/Components/NotificationBell.vue` + layout header | new |
| `tests/Feature/Orders/StatusNotificationsTest.php` | new |

## Success Criteria
- Local walkthrough: mark a test order shipped in Filament → bell badge
  increments for the buyer, email (log mailer) contains tracking info +
  tracker link; guest order → email only. Tests green; pint clean.

## Risks
- Observer-based dispatch fires on *any* save path including seeders/
  factories — guard with `syncOriginal` checks and disable in seed context.
- Queued notifications need the worker (same phase-2 dependency).
- Bell polling adds a query per page load — cheap (indexed, limit 10), but
  noted.

STOP. Await phase approval.

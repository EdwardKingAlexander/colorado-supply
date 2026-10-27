# Phase 4: Order Confirmation Email + Signed Status Tracker

## Goal

Every order — including one placed by an unverified user — produces a
confirmation email tied to the order number, containing a link to a status
tracker page that works **without login or email verification**.

## Existing Foundation (verified in code)

- `app/Mail/OrderConfirmationMail.php` exists and is already sent on the
  quote→order path (`QuoteOrderingService:84`, to `$order->customer_email`,
  with items loaded).
- `Order` has `order_number`, `OrderStatus`, `PaymentStatus`,
  `FulfillmentStatus` enums, and `Shipments` relation — everything a status
  timeline needs.
- Checkout success/cancel pages exist but are auth-gated snapshots, not a
  durable tracker.

## Sub-Phases

### 4.1 Coverage audit of order-creation paths
- Enumerate every place an `Order` is created (quote conversion, store
  checkout via Stripe/PayPal flows, any admin-created orders) and confirm
  `OrderConfirmationMail` is sent on each; add where missing. One shared
  dispatch point (observer or service method) preferred over scattered
  `Mail::to()` calls if more than one gap is found.

### 4.2 Signed tracker route + page
- Route `GET /orders/track/{order}` name `orders.track`, `signed`
  middleware (Laravel signed URL — unguessable, no auth), **no**
  `verified.enabled`, no `auth`. Signature does not expire (order tracking
  should outlive the 60-min verification window); revisit if abuse ever
  observed.
- Inertia page `resources/js/Pages/Store/OrderTracker.vue`:
  - Order number, placed date, line items, totals.
  - Status timeline: OrderStatus → PaymentStatus → FulfillmentStatus →
    shipment(s) with carrier/tracking number when present.
  - Reuses public Navigation + Footer (works logged-out).
- Controller returns only buyer-safe fields (no internal margins/notes).

### 4.3 Email integration
- `OrderConfirmationMail`: add prominent "Track your order" button using
  `URL::signedRoute('orders.track', $order)`; ensure order number is in the
  subject; confirm the phase-2 branded theme applies.
- Status-change updates (shipped, delivered) emails are **out of scope**
  for this module — noted as a natural follow-up module.

### 4.4 Tests
- Signed URL renders tracker for a guest (no auth), including for an order
  whose owner is unverified.
- Tampered/unsigned URL → 403.
- Order creation on each audited path sends `OrderConfirmationMail`
  (`Mail::fake()`), and the rendered mail contains the signed tracker URL
  and order number.

## Files

| File | Change |
|------|--------|
| `app/Http/Controllers/OrderTrackingController.php` | new |
| `routes/web.php` | signed `orders.track` route |
| `resources/js/Pages/Store/OrderTracker.vue` | new |
| `app/Mail/OrderConfirmationMail.php` | tracker link + subject audit |
| order-creation call sites (per 4.1 audit) | send confirmation where missing |
| `tests/Feature/Store/OrderTrackerTest.php` | new |

## Success Criteria
- Place a local test order as an unverified user → confirmation mail in
  `laravel.log` (MAIL_MAILER=log) contains order number + signed link →
  opening the link logged-out shows the tracker with correct status.
- Tests green; pint clean.

## Risks
- Signed URLs in email get forwarded — the tracker must never expose PII
  beyond what the buyer already knows (name on order, shipping city) nor
  any admin-only data. Controller field allow-list, reviewed at validation.
- Store checkout path may create orders before payment completes —
  confirmation timing (on creation vs. on payment) confirmed during 4.1
  audit with the existing PaymentReceived notification in mind (avoid
  double-mailing).

STOP. Await phase approval.

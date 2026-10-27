# Phase 2 — GA4 Ecommerce and Lead Events

## Goal

Instrument the public store and public lead journeys with GA4's recommended
event names and parameters, without sending identity, contact, payment, or
federal-workspace data.

## Scope

- Add typed/allowlisted payload builders for GA4 item, ecommerce, lead, and
  account events.
- Instrument public product lists/details, cart changes, checkout steps,
  successful purchases, eligible refunds, quote requests, repair requests,
  public site search, registration, and login.
- Ensure server-confirmed outcomes drive `purchase` and `refund`; never treat a
  client redirect alone as proof of payment.
- Deduplicate `purchase` across reloads, processor returns, and repeated webhook
  synchronization using the public order number as `transaction_id`.
- Send only SKU/product ID, public product name/category, price, quantity,
  currency, aggregate value, processor category, and coarse lead/search
  categories.
- Configure an explicit list of GA4 key events for the owner to mark in GA4.

## Expected Files to Create

- `resources/js/Analytics/ga4Events.js`
- `resources/js/Analytics/ga4Payloads.js`
- `app/Services/Analytics/PurchaseAnalyticsPayload.php` or equivalent
- `tests/Unit/Analytics/Ga4PayloadTest.php`
- `tests/Feature/Analytics/PurchaseEventTest.php`
- `tests/Feature/Analytics/LeadEventTest.php`
- `tests/Browser/ga4-store-events.spec.js`

## Expected Files to Modify

- Public store product/list/detail pages
- Cart store and cart page
- Checkout, processor return, success, and cancellation flows as appropriate
- Public quote and repair submission flows
- Public search flow
- Registration/login success handling only where it remains on an approved
  analytics surface
- Stripe/PayPal synchronization services only if needed to expose a safe,
  idempotent server-confirmed analytics outcome

The implementation must enumerate exact files before coding because several of
these files contain uncommitted work from other modules.

## Event Contract

- `view_item_list`, `select_item`, `view_item`
- `add_to_cart`, `remove_from_cart`, `view_cart`
- `begin_checkout`, `add_shipping_info`, `add_payment_info`
- `purchase`, `refund`
- `search` with normalized category/result band, never raw query text
- `generate_lead` with `lead_type` only
- `sign_up`, `login` with method category only and no user identifier

## Tests to Write

- Each event uses the GA4-prescribed parameter names and item array shape.
- Forbidden PII/CUI keys and representative values cannot enter a payload.
- Purchase/refund values, quantities, currency, and item IDs match fixtures.
- Purchase emits once for a completed order and never for failed/canceled
  payment or a success URL without a confirmed outcome.
- Reloads and repeated processor callbacks do not duplicate purchase events.
- Lead events emit after successful persistence, not merely form submission.
- All event paths remain blocked by denied consent/GPC and route exclusions.

## Owner Validation

- Use GA4 DebugView/Realtime with a test order and test leads.
- Mark `purchase`, `generate_lead`, and any approved lead outcome as key events.
- Confirm no Google Ads link, Google Signals, user-provided-data collection, or
  marketing consent was enabled.

## Success Criteria

- The public ecommerce and lead funnels are measurable end to end.
- Revenue events are authoritative and idempotent.
- No personal, payment, federal, or raw search data reaches GA4.
- Existing checkout, quote, repair, and consent tests remain green.
- Targeted tests, `npm run build`, `php artisan test`, and
  `vendor/bin/pint --dirty` pass.

**STOP. Await phase approval before implementation.**

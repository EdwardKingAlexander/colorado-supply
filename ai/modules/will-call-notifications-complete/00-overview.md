# Will Call & Notifications — Overview

## Problem statement

1. **No will call.** Checkout always ships: it asks for a shipping address and
   has no "pick up at our counter" option, even though a "Front Range Will
   Call" location already exists. Locations have no address, phone or hours,
   so a buyer couldn't be told where or when to pick up.
2. **Notifications are thin and slow.**
   - Buyers get emails for paid / shipped / delivered / fulfilled /
     cancelled / refunded (`OrderStatusUpdated`, `OrderPaymentReceived`,
     `OrderPaymentFailed` via `App\Support\OrderNotifier`).
   - The bell (`resources/js/Components/NotificationBell.vue`) only lives in
     the old `AuthenticatedLayout`, not the redesigned `StoreLayout`; only
     understands orders; loads once per page; has no per-item read state and
     no "see all" page.
   - Admins get nothing in the panel: the Filament panel has no database
     notifications, so nobody is told a will-call order needs pulling.
   - Nothing is real-time; there is no push or SMS.

## Goal

Buyers can choose **Will Call** at checkout and pick a pickup location. Both
buyers and admins get a Facebook/Instagram-style notification experience:
a live bell with an unread badge, a toast the moment something happens, a
full notification page, plus email, browser/phone push and SMS.

## Owner decisions (2026-10-10)

1. **Will call:** buyer picks **Ship** or **Will Call**; Will Call → choose a
   pickup location. No shipping address or shipping charge for will call.
2. **Audience:**
   - **Buyers** get their own order/quote updates (ready for pickup and all
     store activity on their orders).
   - **Staff alerts go to admins only** (`App\Models\Admin`, admin guard) —
     never to portal users, even users of the same company.
3. **Admin preferences:** every admin is subscribed to everything by default
   and can mute individual alert types or channels on a preferences page.
4. **Real-time:** Laravel Reverb websockets (instant badge + toast).
5. **Channels:** email + in-app bell, **plus** web push and SMS.

## Recommendations taken as defaults (change during plan review if wanted)

- **Pickup statuses:** `awaiting_pick` → `ready_for_pickup` → `picked_up`
  (plus existing `cancelled`). Staff move them with one-click actions in the
  admin order screen; each move notifies the buyer.
- **Pickup code:** each will-call order gets a short code (e.g. `WC-7KQ4`)
  shown in the "ready" email/SMS; staff confirm it at the counter.
- **Uncollected reminders:** buyer reminded 3 and 7 days after
  `ready_for_pickup`; admins alerted at 10 days. Configurable.
- **Buyer controls:** buyers can't turn off transactional order emails (they
  are receipts), but push and SMS are opt-in per buyer, and SMS requires an
  explicit consent checkbox (TCPA). Buyers get no per-type preference page
  in this module (admins do).
- **SMS scope:** only high-value moments — buyer "ready for pickup" and
  "reminder", admin "new will-call order". Everything else is bell/email/push.
  SMS ships behind `SMS_ENABLED=false` until Twilio A2P 10DLC registration is
  approved.
- **Guests:** guest (no account) orders keep email-only, as today.

## Architecture (textual)

- **Will call data:** `locations` gains address, phone, hours,
  `pickup_enabled`, `pickup_instructions`. `orders` gains
  `fulfillment_method` (`ship` | `will_call`), `pickup_location_id`,
  `pickup_status`, `pickup_code`, `ready_for_pickup_at`, `picked_up_at`,
  `picked_up_by`.
- **One notification catalogue:** `App\Notifications\NotificationType`
  enum lists every type with its audience (buyer/admin), default channels,
  label, icon and whether SMS is allowed. All new notifications extend one
  base class producing a uniform payload:
  `{type, title, body, url, icon, subject_type, subject_id}`. Existing order
  notifications move onto it (keeping their emails unchanged).
- **Routing:**
  - Buyer → existing `OrderNotifier` (portal user or routed guest email).
  - Admin → new `AdminNotifier::send(Notification)`: loops active admins,
    filters by each admin's `admin_notification_preferences`.
  - `via()` = type defaults ∩ recipient preferences ∩ channels configured
    (push subscription exists, verified phone + consent, SMS enabled).
- **Real-time:** Reverb + Laravel Echo. Notifications add the `broadcast`
  channel; private channels `App.Models.User.{id}` (web guard) and
  `App.Models.Admin.{id}` (admin guard) authorised per guard. Buyers: Vue
  bell in `StoreLayout` listens via Echo. Admins: Filament's native database
  notifications with Echo enabled (bell in the panel top bar).
- **Push:** `laravel-notification-channels/webpush` (v13, needs Laravel
  ≥ 13.13; we're on 13.19). Service worker + web manifest; subscriptions are
  polymorphic so both User and Admin can subscribe.
- **SMS:** `laravel-notification-channels/twilio` (v4.2, Laravel 13). Phone
  numbers stored E.164; inbound STOP/HELP webhook records opt-outs.

## Data flow

```
Checkout (Will Call, location) → Order(fulfillment_method=will_call, pickup_status=awaiting_pick)
  → AdminNotifier: NewWillCallOrder → admins (bell + toast + email + push + SMS)
  → OrderNotifier: OrderReceived → buyer (email + bell)
Staff clicks "Ready for pickup" → pickup_status=ready_for_pickup, code generated
  → buyer: ReadyForPickup (email + bell + push + SMS)
Scheduler (daily) → uncollected → buyer reminders (3d, 7d) / admin alert (10d)
Staff clicks "Picked up" (enters code/name) → buyer: PickedUp (email + bell)
Every notification → database row → broadcast → Echo → badge +1, toast
```

## Risks

- **Reverb hosting:** needs a Forge daemon, a websocket port/proxy and the
  queue worker running; if Reverb is down the bell must still work (falls
  back to load-on-open + 60s poll).
- **iPhone push:** only works when the site is added to the Home Screen
  (iOS 16.4+). Android/desktop work in the browser. Must be explained in the
  opt-in UI, not hidden.
- **SMS compliance and cost:** A2P 10DLC brand + campaign registration (days
  to approve, small one-time + monthly fees, per-message carrier fees);
  consent, STOP/HELP and quiet hours are legal requirements, not nice-to-haves.
- **Notification spam:** "all store activity" for every admin can get noisy;
  mitigated by preferences and grouping repeated events (e.g. 5 payments →
  one digest line in the bell is out of scope; preferences are the tool).
- **Two guards:** private-channel auth must use the right guard or one
  user type could subscribe to the other's channel — covered by tests.
- **Existing behaviour:** current order emails must not change wording or
  double-send when moved onto the new base class.

## Phase table

| # | Phase | File |
|---|-------|------|
| 1 | Will call: pickup locations, checkout option, order pickup workflow | `phase-1-will-call.md` |
| 2 | Notification core: catalogue, base class, admin routing and preferences | `phase-2-notification-core.md` |
| 3 | Real-time bell: Reverb, buyer bell + page, Filament admin notifications | `phase-3-realtime-bell.md` |
| 4 | Event coverage: every buyer and admin event, reminders, emails | `phase-4-event-coverage.md` |
| 5 | Web push: service worker, manifest, subscriptions, opt-in | `phase-5-web-push.md` |
| 6 | SMS: Twilio, phone + consent, STOP/HELP, feature flag | `phase-6-sms.md` |
| 7 | Testing, accessibility, browser QA and deploy runbook | `phase-7-testing-deploy.md` |

Phases 1 and 2 are independent; 3 needs 2; 4 needs 1–3; 5 and 6 need 2 and 4;
7 needs all.

## Research sources

- Laravel 13 broadcasting / Reverb: https://laravel.com/docs/13.x/broadcasting
- Filament v4 broadcast + database notifications:
  https://filamentphp.com/docs/4.x/notifications/broadcast-notifications,
  https://filamentphp.com/docs/4.x/notifications/database-notifications
- Web push channel (v13.0.1, Laravel ^12|^13.13):
  https://packagist.org/packages/laravel-notification-channels/webpush
- Twilio channel (v4.2.0, Laravel ^12|^13):
  https://packagist.org/packages/laravel-notification-channels/twilio
- A2P 10DLC fees/requirements:
  https://support.twilio.com/hc/en-us/articles/1260803965530

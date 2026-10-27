# Will Call & Notifications — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-11 | Approved by owner |
| 1 | Will call: pickup locations, checkout, pickup workflow | Complete | 2026-10-11 | Owner approved ("continue"); committed eb750e36. 28 new tests; full suite 1050 passed / 5 skipped / 0 failed; not yet browser-checked |
| 2 | Notification core: catalogue, admin routing, preferences | Complete | 2026-10-11 | Owner approved ("continue"); committed 92b1eeb4. 35 new tests; full suite 1085 passed / 5 skipped / 0 failed |
| 3 | Real-time bell: Reverb, buyer bell/page, Filament bell | Complete | 2026-10-11 | Owner approved ("continue"); committed 52653fca. 15 new tests; full suite 1100 passed / 5 skipped / 0 failed; live toast verified in a browser over Reverb (buyer side) |
| 4 | Event coverage, reminders, emails | Complete | 2026-10-11 | Owner approved ("continue"); committed 6674e535. 16 new tests; full suite 1116 passed / 5 skipped / 0 failed; BackorderAvailable not wired (no backorder records exist) |
| 5 | Web push | Complete | 2026-10-12 | Owner approved ("continue"); committed f6b355cd + ba8e2510. 21 new tests; full suite green; real push delivered to Chromium end to end |
| 6 | SMS (Twilio, consent, STOP/HELP) | Complete | 2026-10-12 | Owner approved ("continue"); committed fd1672b6. 15 new tests; full suite 1152 passed / 5 skipped / 0 failed. Ships OFF (SMS_ENABLED=false) until the owner's A2P 10DLC registration is approved |
| 7 | Testing, accessibility, deploy runbook | Complete | 2026-10-23 | Owner approved; committed b7866a12 and pushed with the 21 earlier commits. Clean-checkout suite at fd1672b6: 997 passed / 5 skipped; notification suite 111 passed. 39/39 Playwright (incl. live two-browser will-call flow over Reverb); 13 new hardening tests; full suite 1165 passed / 5 skipped / 0 failed; RUNBOOK.md written, not yet followed on production |

## Log

- 2026-10-10 — Plan written. Owner decisions: will call with location choice;
  buyers get their own order/quote updates, staff alerts go to admins only;
  each admin can mute types/channels (subscribed by default); real-time via
  Reverb; channels email + bell + web push + SMS. Awaiting plan approval.
- 2026-10-11 — Owner approved the plan ("approved. continue"); phase 1 started.
- 2026-10-11 — Phase 1 built (uncommitted):
  - Migration `2026_10_11_000000_add_will_call_fields` (locations: pickup_enabled,
    address json, phone, hours, pickup_instructions; orders: fulfillment_method,
    pickup_location_id, pickup_status, pickup_code, ready_for_pickup_at,
    picked_up_at, picked_up_by). Enums `FulfillmentMethod`, `PickupStatus`.
    `LocationFactory` (new, with `pickup()` state).
  - Checkout: Ship / Will Call choice + pickup location cards (remembered per
    device); server validates pickup-enabled, same-company, not archived;
    will call stores no shipping address. `WillCallOrderPlaced` event.
    Confirmation email names the pickup counter.
  - `Order::pickupDetails()` on checkout pay, success and tracker pages via
    `PickupDetails.vue`; code shown only once ready.
  - Admin: Locations "Will call" section + column/filter; Orders method and
    pickup columns/filters, "Will call queue" tab, actions Ready for pickup
    (stock-short warning, code `WC-XXXX`), Picked up (collector name + code or
    ID-checked override), Not ready. `WillCallPickupService` enforces
    transitions; events `OrderReadyForPickup`, `OrderPickedUp`.
  - **Bug fixed on the way:** `Order` creating/updating stamped
    `created_by`/`updated_by` (FK → users) with `auth()->id()`, which is an
    Admin id inside the admin panel — any admin-side order save could violate
    the FK in production. Now only stamped when the actor is a `User`.
  - Picking up sets fulfillment Fulfilled, which still sends today's generic
    "order complete" email (single send); phase 4 replaces it for will call.
  - Follow-ups: other models with the same `auth()->id()` stamping
    (e.g. `OpportunityObserver`) may have the admin-id problem; not changed.
- 2026-10-11 — Owner approved phase 1 ("continue"); committed eb750e36. Phase 2 started.
- 2026-10-11 — Phase 2 built (uncommitted):
  - `App\Notifications\NotificationType` (13 buyer + 7 admin types, default
    channels, label, description, icon) and `NotificationChannel`
    (Email, Bell, Push, Sms; Push/Sms report not ready until phases 5–6).
  - `NotificationChannels::for()` resolves Laravel channels: type defaults,
    audience (admin types → Admin only; buyer types → User or routed guest),
    receivability, admin mutes. Canonical order keeps `['mail', 'database']`.
  - `AppNotification` base: uniform payload `{type, title, body, url, icon,
    label}` + type-specific keys. `OrderStatusUpdated`, `OrderPaymentReceived`,
    `OrderPaymentFailed` moved onto it; class names, emails and old bell keys
    unchanged.
  - `admin_notification_preferences` (missing row = on) +
    `AdminNotificationPreference`; `App\Support\AdminNotifier::send()`.
  - Filament page **My notifications** (user menu): per-type channel
    toggles, Push/SMS shown disabled "Coming soon", "Turn off all email".
  - `notifications:prune` (read > 90 days, unread > 180) scheduled daily
    03:30 America/Denver in `routes/console.php`.
  - Deviation: `OrderReceived` defaults to Bell only — the checkout
    confirmation email already covers email.
  - No admin alerts are sent yet; phase 4 wires the events. Admin rows land in
    the `notifications` table but the Filament bell is enabled in phase 3.
- 2026-10-11 — Owner approved phase 2 ("continue"); committed 92b1eeb4. Phase 3 started.
- 2026-10-11 — Phase 3 built (uncommitted):
  - `laravel/reverb` ^1.0 (v1.12.0) via `install:broadcasting --reverb
    --without-node`; `routes/channels.php` registered in `bootstrap/app.php`;
    `config/broadcasting.php`, `config/reverb.php`. npm: `laravel-echo` 2.5.0
    (2.6.1 was 4 days old), `pusher-js` 8.6.0. `composer run dev` also starts
    `reverb:start`.
  - Local: `.env` REVERB_HOST=colorado-supply.test, REVERB_SCHEME=https,
    port 8080; Reverb picks up Herd's certificate automatically, so the
    https site connects over `wss://colorado-supply.test:8080` (verified
    101 handshake). `phpunit.xml` sets BROADCAST_CONNECTION=null.
  - Channels `App.Models.User.{id}` (web guard) and `App.Models.Admin.{id}`
    (admin guard), each checked against its own guard.
  - Bell channel adds `broadcast` when a real broadcaster is configured.
    `AppNotification::toDatabase/toBroadcast`: admins get Filament's format
    (panel bell + toast with a View action); buyers get the shared payload,
    event name = type. `RefreshAdminBell` listener dispatches Filament's
    `DatabaseNotificationsSent` after an admin row is stored.
  - Admin panel: `databaseNotifications()` + 60s polling fallback; Echo
    configured in `config/filament.php` only when BROADCAST_CONNECTION=reverb.
  - Buyer: `useNotifications` store, `Notifications/NotificationBell` in the
    store header, `NotificationToasts` (polite live region, 6s, pause on
    hover/focus, max 3, reduced motion), `/notifications` page (All/Unread,
    Today/This week/Earlier, load more, mark read/unread, delete).
    `NotificationController` gains mark-read/unread/destroy (scoped, 404 for
    others, throttled); shared prop `notificationBell` {unread, live}.
  - CSP `connect-src` adds the Reverb origin when broadcasting is Reverb.
  - Live check: throwaway buyer on https://colorado-supply.test at 390px,
    subscription succeeded, "Your order has shipped" toast + badge 1 arrived
    after the queue ran; records removed. Fixed: mobile toasts sat under the
    "Privacy choices" button (now lifted).
  - Not browser-checked: the admin panel live bell (admin login requires
    MFA); covered by feature tests. Old `Components/NotificationBell.vue` in
    `AuthenticatedLayout` left as is (still works, not live).
- 2026-10-11 — Owner approved phase 3 ("continue"); committed 52653fca. Phase 4 started.
- 2026-10-11 — Phase 4 built (uncommitted):
  - Checkout now dispatches `StoreOrderPlaced` for every order (replaces
    phase 1's `WillCallOrderPlaced`). `SendOrderPlacedNotifications`: buyer
    `OrderReceivedNotification` (bell), admins `NewOrderAlert` (will call →
    AdminNewWillCallOrder, else AdminNewOrder; body shows buyer, items,
    total, pickup counter or ship-to city, payment status) and
    `BackorderAlert` when `StockShortfall` finds short lines.
  - `SendPickupNotifications`: `ReadyForPickupNotification` (code, address,
    hours, phone, instructions) and `PickedUpNotification`. `OrderObserver`
    skips the generic "order complete" for picked-up will call orders.
  - `will-call:remind` (daily 09:00 America/Denver; `config/will_call.php`
    3 & 7 days buyer, 10 days admin `PickupUncollectedAlert`). Sent markers
    in `orders.meta.pickup_reminders`; cleared when marked ready again; a
    late first run sends only the latest due reminder.
  - Admin `QuoteRequestedAlert` from product-page quote requests; buyer
    `QuoteReadyNotification` when a quote with a portal user changes to
    `sent` (`QuoteObserver`). Admin `PaymentAlert` from Stripe (paid/failed)
    and PayPal (paid) sync.
  - `AppNotification::toMail()` default layout (title, lines, button); admin
    emails end with a link to My notifications. `StockShortfall` extracted
    and reused by `WillCallPickupService::shortLines()`.
  - Decision: admins are alerted when an order is placed, before payment;
    the alert states the payment status and a separate payment alert follows.
  - Not wired / follow-ups: `BackorderAvailable` (no backorder records to
    trigger from); buyers have no quote page, so the quote-ready message has
    no link; the public legacy `/api/v1/store/quote` endpoint sends no admin
    alert (spam risk; it is already on the follow-up list).
- 2026-10-11 — Owner approved phase 4 ("continue"); committed 6674e535. Phase 5 started. (Reverb background process was stopped by Claude Code for low memory; restart with composer run dev.)
- 2026-10-12 — Phase 5 built (uncommitted):
  - `laravel-notification-channels/webpush` 13.0.1 (+ minishlink/web-push 11);
    published `push_subscriptions` migrations and `config/webpush.php`
    (VAPID subject defaults to APP_URL). VAPID keys generated into `.env`.
    `HasPushSubscriptions` on `User` and `Admin`.
  - Push channel is ready only with VAPID keys; delivered only to people
    with a saved device; admins can mute it per type. `toWebPush()` on
    `AppNotification` (title, body, icon, per-notification tag, data.url).
  - `public/sw.js` (show push, open/focus same-origin URL on click; no
    caching). `app.blade.php` links `site.webmanifest`, apple-touch-icon and
    theme-color (needed for iPhone Home Screen push).
  - Buyer: `PushSubscriptionController` + `App\Support\PushDevices`
    (validate https endpoint/keys, save, list with device label, remove by
    id or endpoint, own devices only; a shared device moves to the latest
    account). `Support/push.js` + `PushOptIn.vue`: full card on
    /notifications (on/off, device list, iOS "Add to Home Screen" steps,
    blocked state), compact "Turn on / Not now" prompt on the thank-you
    page after a will call order. Permission is only requested on click.
  - Admin: "Push on this device" section on My notifications (Alpine +
    Livewire `savePushSubscription` / `removePushDevice`); Push toggles live.
  - Live check: persistent Chromium profile, buyer turned push on, a
    "shipped" notification went through the queue and Chrome's push service
    and the service worker displayed it with the tracker link. Fixed the
    device label for current Chrome endpoints (`*.google.com`).
  - **Local Windows note:** Herd's PHP can't find an OpenSSL config, so
    `webpush:vapid` and push *sending* fail unless the process has
    `OPENSSL_CONF=C:/Users/babyd/.config/herd/openssl.cnf` (set it as a
    Windows user environment variable for the queue worker). Linux/Forge is
    unaffected. Headless Chromium shell and incognito contexts can't use push.
- 2026-10-12 — Owner approved phase 5 ("continue"); committed f6b355cd, plus ba8e2510 adding the Admin import my partial-staging script dropped. Phase 6 started.
- 2026-10-12 — Phase 6 built (uncommitted):
  - `laravel-notification-channels/twilio` 4.2.0 (twilio/sdk 8.12.2);
    `config/twilio-notification-channel.php` published (21610 "replied STOP"
    added to ignored errors). `config/sms.php`: SMS_ENABLED (default false),
    quiet hours 21:00–08:00 America/Denver, consent text + version, HELP reply,
    verification limits (10 min, 5 tries, 3 sends/hour).
  - `sms_recipients` (one per user/admin: E.164 number, consent time/IP/
    version, verified_at, opted_out_at, hashed code). `SmsRecipient`,
    `ReceivesTextMessages` trait on User + Admin (`routeNotificationForTwilio`
    returns a number only when verified, consented, not opted out).
  - `SmsSubscriptions` (start: US number + unchecked consent → 6-digit code
    via `SmsVerificationCode`; verify; stop; status). Buyer endpoints
    `/sms-subscription` + `SmsOptIn.vue` card on /notifications; admin
    "Text alerts" section on My notifications (Livewire).
  - Channel: Sms ready only with SMS_ENABLED + Twilio SID/token + sender;
    used only for ReadyForPickup, PickupReminder, AdminNewWillCallOrder;
    admins can mute. `toTwilio()`: "Colorado Supply: ..." ASCII-only, ≤160
    chars, ends "Reply STOP to opt out."; `withDelay()` holds texts during
    quiet hours (other channels unaffected).
  - Webhook `POST /api/v1/twilio/sms` (`webhooks.twilio.sms`): Twilio
    signature verified against the raw body (Laravel's trimmed input broke
    signatures on replies with spaces — caught by tests); STOP words opt out
    every matching number, START words opt back in, HELP replies with
    `sms.help_reply`. Twilio itself sends the STOP confirmation.
  - Public `/sms-terms` page (program description, frequency, rates,
    STOP/HELP, quiet hours, no sharing of mobile numbers) for the campaign
    review.
  - Deviation: the plan's "Text me when it's ready" box at Will Call checkout
    was not built — a number must be verified by code first, which doesn't
    fit mid-checkout. Sign-up lives on the Notifications page (reached from
    the bell's "See all notifications"); revisit after launch.
  - Not live-tested against Twilio (no account/credentials yet).
- 2026-10-12 — Owner approved phase 6 ("continue"); committed fd1672b6. Phase 7 started.
- 2026-10-12 — Phase 7 built (uncommitted):
  - Browser: `tests/Browser/will-call.spec.ts` + `support/will-call-fixture.php`
    (token-scoped buyer, MFA-enrolled admin, counter, product; teardown also
    removes the orders and every alert that mentions them). Checkout will call
    and the notifications page/bell at 320/390/1280px: axe clean, 44px
    targets, no sideways scroll, bell aria-expanded + Escape returns focus.
    Live flow: phone buyer places a will-call order → desktop admin gets the
    Filament toast → admin marks Ready (buyer toast with WC- code, unread
    count) → Picked up (buyer toast).
  - `playwright.config.ts`: app served by `php -S` + Laravel's router so env
    reaches it (QUEUE_CONNECTION=sync, MAIL_MAILER=array, VAPID blank so no
    real pushes, SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8123 for the checkout API);
    second webServer starts Reverb. Fixed product-page.spec status locator
    (the toast region is also role=status since phase 3).
  - Hardening: `AppNotification` tries=3, backoff 60/300s; mark-all-read
    throttled; `NotificationHardeningTest` (constant query count on the bell
    list, per-channel queued jobs, 410 push device removed, throttle on every
    notification/push/SMS/webhook route). Unread already had sr-only text.
  - `RUNBOOK.md`: Forge deploy, worker/scheduler, counters, Reverb (Forge
    toggle, DNS, SSL, env, VITE rebuild), VAPID, Twilio A2P + webhook, smoke
    test, per-channel rollback.
  - Not done: push opt-in granted/denied browser states (headless Chromium
    has no push; covered by phase 5's persistent-context live check). One
    non-repeating failure in one full-suite run (two reruns and 4 repeat runs
    of the notification tests green).
  - Follow-ups: owner follows RUNBOOK on production; Twilio A2P approval then
    SMS_ENABLED=true; BackorderAvailable alert once backorders exist; quote-
    ready link; admin alert for legacy /api/v1/store/quote; record opt-out
    on Twilio error 21610.
- 2026-10-12 — Owner paused ("I will come back later"). Everything left for the owner is in `OWNER-TODO.md` (approve phase 7, Forge setup per RUNBOOK, privacy policy page + Twilio A2P, team rollout, deferred decisions). Phase 7 still uncommitted.
- 2026-10-23 — Owner approved phase 7 ("commit and push"). Committed b7866a12 and pushed main (46cca36a..b7866a12) with the 21 earlier unpushed commits. Module complete; folder renamed to `will-call-notifications-complete`. Production Forge setup is still the owner's to-do (OWNER-TODO.md §2–5).

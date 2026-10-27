---
plan: will-call-notifications
phase: 5
title: Web push — service worker, manifest, subscriptions, opt-in
status: completed
last_updated: 2026-10-11
depends_on: [phase-2, phase-4]
checkpoint: channel
---

# Phase 5 — Web Push

## Goal

Lock-screen / desktop notifications, even with the site closed, for buyers and
admins who opt in.

## Success criteria

- "Turn on notifications on this device" works in Chrome/Edge/Firefox
  (desktop + Android) and on iPhone/iPad when added to the Home Screen.
- Clicking a push opens the target URL (focuses an open tab if one exists).
- Unsubscribing or a dead subscription (404/410) removes it automatically.
- Push is never requested on page load — only after a user click.

## Sub-phases

### 5.1 — Package and keys — `checkpoint: package`
- `composer require laravel-notification-channels/webpush` (v13; needs
  Laravel ≥ 13.13 — we're on 13.19). Publish migration
  (`push_subscriptions`, polymorphic → works for User and Admin).
- `php artisan webpush:vapid` locally; production keys go in Forge env.
- `HasPushSubscriptions` on `User` and `Admin`.

### 5.2 — Service worker and manifest — `checkpoint: service-worker`
- `public/sw.js`: `push` → `showNotification(title, {body, icon, badge,
  data:{url}, tag})`; `notificationclick` → focus/open url.
- `public/manifest.webmanifest` + icons (needed for iOS Home Screen push).
- Register the SW only on https.

### 5.3 — Opt-in UI — `checkpoint: opt-in`
- Buyer: card on the notifications page + one-time soft prompt after the
  first order ("Get pickup alerts on this phone?").
- Admin: button on the My notifications page.
- Detects unsupported / denied / iOS-not-installed and shows the right
  instructions ("Share → Add to Home Screen, then open from there").
- Lists this user's devices with "Remove".

### 5.4 — Channel wiring — `checkpoint: channel`
- `toWebPush()` on `AppNotification`; `NotificationChannels` enables push only
  if the notifiable has subscriptions and (admins) hasn't muted push for that
  type.

## Tests

- Feature: subscribe/unsubscribe endpoints (auth, scoped to self, validates
  endpoint/keys); channel included only with a subscription.
- Payload test: title/body/url/tag shape.
- Manual/Playwright: permission granted in a test context → SW receives push
  (Chromium only).

## Risks

- iOS: only Home Screen web apps, iOS 16.4+; can't be fixed in code — must be
  communicated.
- Corporate browsers may block notifications; fall back silently to bell.

STOP. Await phase approval.

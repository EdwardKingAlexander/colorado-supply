---
plan: will-call-notifications
phase: 3
title: Real-time bell — Reverb, buyer bell and page, Filament admin notifications
status: completed
last_updated: 2026-10-11
depends_on: [phase-2]
checkpoint: admin-bell
---

# Phase 3 — Real-time Bell

## Goal

Facebook-style experience: the badge updates and a toast appears the instant
a notification is created, for buyers in the store and admins in the panel.

## Success criteria

- A notification sent to a logged-in buyer shows within ~1s without reload:
  badge +1 and a toast with title/body linking to the target.
- Admins see Filament's bell update live with the same content.
- If Reverb is unavailable the bell still works (load on open + 60s poll),
  with no console errors.
- Buyer can't subscribe to an admin channel or another user's channel.

## Sub-phases

### 3.1 — Reverb + Echo — `checkpoint: reverb`
- `php artisan install:broadcasting --reverb` (review generated files; keep
  only what's needed). Add `@laravel/echo-vue` + `pusher-js`.
- `routes/channels.php`: `App.Models.User.{id}` authorised on the web guard;
  `App.Models.Admin.{id}` on the admin guard. Broadcasting auth route uses
  middleware that accepts either guard and checks the matching one.
- `composer run dev` gains `reverb:start`. Local Herd https: Reverb over TLS
  via Herd, or `REVERB_SCHEME=http` locally — verify with the LNA/https
  setup in memory (don't revert to http dev URLs).

### 3.2 — Buyer bell — `checkpoint: buyer-bell`
- Rebuild `NotificationBell.vue` on `st-*` tokens and put it in
  `StoreLayout` header (and keep `AuthenticatedLayout`).
- Dropdown: newest 15, unread dot + tinted row, icon per type, relative time,
  click = mark that one read + navigate; "Mark all read"; "See all".
- Unread count shared via Inertia shared prop (no extra request on load).
- Echo listener (`.notification()`) → prepend, badge +1, toast.
- Toast component: polite live region, auto-dismiss 6s, pause on hover/focus,
  max 3 stacked, respects reduced motion.
- `NotificationController`: paginated index, `markRead(id)`, `markAllRead`,
  scoped to the current user (404 for others).

### 3.3 — Notifications page — `checkpoint: buyer-page`
- `/notifications` Inertia page: All / Unread tabs, grouped Today / This week
  / Earlier, infinite "Load more", mark read/unread, delete.
- Mobile: full-width list, 44px targets, no horizontal scroll at 320px.

### 3.4 — Admin bell — `checkpoint: admin-bell`
- `AdminPanelProvider`: `->databaseNotifications()`, Echo config enabled,
  polling fallback 60s.
- `AppNotification::toDatabase()` for admins uses Filament's
  `Notification::make()->...->getDatabaseMessage()` format so the panel bell
  renders title, body, icon and a "View" action; buyer payloads unchanged.

## Tests

- Feature: channel auth matrix (user→own ok, user→other 403, user→admin 403,
  admin→own ok); controller scoping; mark read.
- Broadcast: `Event::fake`/`Notification::fake` assert `broadcast` channel
  present only when broadcasting is configured.
- Playwright (in phase 7) covers live toast.

## Risks

- Reverb behind Herd https locally and Forge in production: websocket host/
  port/TLS config differs per environment — document env vars.
- Two Echo instances (Filament's and ours) must not both load on admin pages.

STOP. Await phase approval.

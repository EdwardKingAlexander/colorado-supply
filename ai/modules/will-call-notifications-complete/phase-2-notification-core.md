---
plan: will-call-notifications
phase: 2
title: Notification core — catalogue, base class, admin routing and preferences
status: completed
last_updated: 2026-10-11
depends_on: []
checkpoint: retention
---

# Phase 2 — Notification Core

## Goal

One consistent way to define, route and store every notification, with admin
preferences. No new UI for buyers yet; existing emails keep working.

## Success criteria

- Every notification type is declared once in a catalogue with audience,
  default channels, label and icon.
- Admin alerts reach admins only and respect each admin's preferences.
- Existing order notifications produce identical emails and still appear in
  the old bell.

## Sub-phases

### 2.1 — Catalogue and base class — `checkpoint: catalogue`
- `App\Notifications\NotificationType` enum: case per type, with
  `audience()` (buyer|admin), `defaultChannels()`, `label()`,
  `description()` (for the preferences page), `icon()` (heroicon name),
  `smsAllowed()`.
- `App\Notifications\AppNotification` abstract base (ShouldQueue): abstract
  `type()`, `title()`, `body()`, `url()`, optional `subject()`; implements
  `toArray()` / `toDatabase()` with the uniform payload; `via()` delegates to
  `NotificationChannels::for($notifiable, $this->type())`.
- Move `OrderStatusUpdated`, `OrderPaymentReceived`, `OrderPaymentFailed` onto
  the base. Keep `label`, `order_number`, `tracker_url` keys in the payload so
  the old bell and old rows still render.

### 2.2 — Channel resolution — `checkpoint: channels`
- `App\Notifications\NotificationChannels::for()`: type defaults ∩ recipient
  preferences ∩ available (mail needs an email; `broadcast` added only when
  `BROADCAST_CONNECTION` isn't `log`/`null`; push/SMS placeholders return
  false until phases 5–6).
- Guests (routed `AnonymousNotifiable`) → mail only, as today.

### 2.3 — Admin routing and preferences — `checkpoint: admin-routing`
- `admin_notification_preferences`: `admin_id`, `type`, `channel`,
  `enabled` bool; unique (admin_id, type, channel). **Absence = enabled**
  (default subscribed).
- `AdminNotifier::send(AppNotification)`: active admins only (respect any
  disabled/locked flag on `Admin`), dedupes, queues.
- Filament page **My notifications** (user menu): matrix of admin types ×
  channels (Bell, Email, Push, SMS) with toggles; Push/SMS columns disabled
  with a hint until set up; "Mute all email" shortcut.
- Policy: an admin edits only their own preferences.

### 2.4 — Retention — `checkpoint: retention`
- Scheduled `model:prune`-style command: delete read notifications older than
  90 days, unread older than 180 days (config).

## Tests

- Unit: catalogue completeness (every case has label/icon/channels).
- Feature: admin with type muted gets nothing; muted email still gets bell;
  portal users never receive admin types; guest order → mail only.
- Regression: existing order notification tests pass unchanged; email
  subject/body snapshot unchanged.

## Risks

- Moving existing notifications must not change the serialized class names of
  queued jobs in flight at deploy time — keep class names and namespaces.

STOP. Await phase approval.

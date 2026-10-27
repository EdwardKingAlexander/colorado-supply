---
plan: will-call-notifications
phase: 4
title: Event coverage — buyer and admin events, reminders, emails
status: completed
last_updated: 2026-10-11
depends_on: [phase-1, phase-2, phase-3]
checkpoint: emails
---

# Phase 4 — Event Coverage

## Goal

Every meaningful store event notifies the right people on the right channels.

## Catalogue

Buyer (their own orders/quotes):

| Type | Trigger | Default channels |
|---|---|---|
| order_received | order placed (ship or will call) | mail, bell |
| ready_for_pickup | staff marks ready | mail, bell, push, sms |
| pickup_reminder | 3 and 7 days uncollected | mail, bell, push, sms |
| picked_up | staff marks picked up | mail, bell |
| payment_received / payment_failed | existing | mail, bell |
| shipped / delivered / fulfilled / cancelled / refunded | existing | mail, bell, push |
| backorder_available | backordered line now in stock | mail, bell, push |
| quote_ready | admin sends a quote to the buyer | mail, bell, push |

Admin (all admins, filtered by preferences):

| Type | Trigger | Default channels |
|---|---|---|
| new_will_call_order | will-call order placed | bell, mail, push, sms |
| new_order | ship order placed | bell, mail, push |
| quote_requested | product page / store quote request | bell, mail, push |
| payment_received / payment_failed | payment sync | bell, push |
| backorder_created | order line exceeds stock | bell, mail |
| pickup_uncollected | 10 days uncollected | bell, mail |
| order_cancelled_by_buyer | buyer cancels (if/when allowed) | bell |

## Sub-phases

### 4.1 — Will-call events — `checkpoint: will-call-events`
- Listeners for phase-1 events → buyer + admin notifications.
- "Ready" content: location name, address, hours, instructions, pickup code,
  map link, "bring this code".
- Avoid double send: `picked_up` replaces the generic "order complete" email
  for will-call orders.

### 4.2 — Order, payment, quote, backorder events — `checkpoint: store-events`
- Admin `new_order`, `quote_requested`, payment events, `backorder_created`.
- Buyer `order_received`, `quote_ready`, `backorder_available` (verify where
  backorder state lives; if no reliable trigger exists, record as a follow-up
  rather than inventing one).

### 4.3 — Reminders — `checkpoint: reminders`
- Daily scheduled command: uncollected will-call orders → buyer reminders at
  3/7 days, admin alert at 10 days; idempotent (store sent markers in
  `meta`), skip cancelled/picked up. Days in config.

### 4.4 — Email templates — `checkpoint: emails`
- Consistent branded markdown mail layout for all types; plain-text part;
  footer explains why they got it and (admins) links to preferences.

## Tests

- One feature test per type: trigger → correct recipients and channels.
- Reminder command: idempotent, correct day boundaries (Carbon::setTestNow).
- No notification on factory/seeder creates.

## Risks

- Volume: bulk imports or admin edits could fan out notifications — only
  fire from user-facing flows and explicit admin actions, never model
  `saved` hooks on bulk paths.

STOP. Await phase approval.

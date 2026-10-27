---
plan: will-call-notifications
phase: 1
title: Will call — pickup locations, checkout option, order pickup workflow
status: completed
last_updated: 2026-10-11
depends_on: []
checkpoint: admin-actions
---

# Phase 1 — Will Call

## Goal

A buyer can choose Will Call at checkout and pick a pickup location; staff can
move the order through awaiting pick → ready → picked up in the admin panel.
Notifications for these moves are wired in phase 4 (this phase only records
state and fires domain events).

## Success criteria

- Checkout shows **Ship** / **Will Call**. Will Call hides the shipping
  address, shows pickup-enabled locations (name, address, hours) and charges
  no shipping. Ship behaves exactly as today.
- Server validates: will call requires a pickup-enabled location the buyer
  may use; ship ignores `pickup_location_id`.
- Admin order view shows method, location, pickup status, code and one-click
  actions with confirmation.
- Order tracker and checkout success page show pickup details.

## Sub-phases

### 1.1 — Location pickup fields — `checkpoint: locations`
- Migration on `locations`: `pickup_enabled` bool default false, `address`
  json nullable (line1, line2, city, state, postal_code), `phone`, `hours`
  text, `pickup_instructions` text — all nullable.
- `LocationResource`: "Will call" section (toggle + fields), filter on
  pickup-enabled.
- **Done when:** fields save/clear; a location without pickup is unaffected.

### 1.2 — Order fields — `checkpoint: order-fields`
- Migration on `orders`: `fulfillment_method` string default `ship`,
  `pickup_location_id` FK nullable (nullOnDelete), `pickup_status` nullable,
  `pickup_code` nullable unique, `ready_for_pickup_at`, `picked_up_at`,
  `picked_up_by` (string — name of person collecting), indexes on
  `(fulfillment_method, pickup_status)`.
- Enums `FulfillmentMethod` (Ship, WillCall) and `PickupStatus`
  (AwaitingPick, ReadyForPickup, PickedUp) with label()/color() like the
  existing enums.
- **Done when:** existing orders read as `ship` with null pickup fields.

### 1.3 — Checkout — `checkpoint: checkout`
- `StoreCheckoutController` validation: `fulfillment_method` in
  [ship, will_call]; `pickup_location_id` required_if will_call, exists,
  `pickup_enabled`, and visible to the buyer (same company rules as stock).
- `StoreCheckoutService::createFromCart`: store method/location; will call →
  `shipping_total = 0`, `shipping_address = null`, `pickup_status =
  awaiting_pick`. Fire `WillCallOrderPlaced` event.
- `Checkout.vue`: segmented Ship / Will Call control (radio group, 44px
  targets), pickup location cards with address/hours, remembered last choice
  (localStorage, wrapped in try/catch).
- `CheckoutSuccess.vue`, `OrderTracker.vue`: pickup location, hours,
  instructions, status; code shown only once ready.
- **Done when:** both methods place orders; invalid/foreign/disabled location
  is rejected with a field error.

### 1.4 — Admin pickup workflow — `checkpoint: admin-actions`
- `CRM/OrderResource`: method + pickup status columns/filters ("Will call
  queue" tab: awaiting pick / ready / uncollected).
- Actions (visible only for will-call orders, valid transition only):
  - **Mark ready for pickup** → generates `pickup_code` (unambiguous
    alphabet, retry on collision), sets `ready_for_pickup_at`.
  - **Mark picked up** → requires "Collected by" name; optional code check
    (warns on mismatch, admin can override); sets `picked_up_at`, and
    fulfillment_status → Fulfilled.
  - **Back to awaiting pick** (undo ready).
- Each transition fires a domain event (`OrderReadyForPickup`,
  `OrderPickedUp`) — listeners come in phase 4.
- **Done when:** transitions are enforced server-side, not just by button
  visibility.

## Tests

- Feature: checkout ship vs will call; location validation (missing,
  disabled, other company's, soft-deleted); shipping total zero; events fired.
- Filament: actions visible only when valid; code generated and unique;
  picked-up sets fulfillment; invalid transition rejected.
- Existing checkout tests still green unchanged.

## Risks

- Marking picked up sets fulfillment Fulfilled, which today triggers the
  "order complete" email via `OrderObserver` — phase 4 decides the final
  wording; in this phase confirm it doesn't double-send.
- Partial pickups (some items backordered) are out of scope: an order with
  backordered lines can be marked ready only when admin confirms (warning
  shown).

STOP. Await phase approval.

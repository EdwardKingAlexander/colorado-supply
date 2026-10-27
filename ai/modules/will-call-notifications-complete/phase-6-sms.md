---
plan: will-call-notifications
phase: 6
title: SMS — Twilio, phone and consent, STOP/HELP, feature flag
status: in_progress
last_updated: 2026-10-12
depends_on: [phase-2, phase-4]
checkpoint: channel
---

# Phase 6 — SMS

## Goal

Text messages for the moments that matter (ready for pickup, pickup reminder,
new will-call order for admins), legally compliant and off until the owner's
Twilio A2P 10DLC registration is approved.

## Owner prerequisites (outside code)

- Twilio account, a 10DLC number, A2P brand registration (legal name, EIN,
  address, website) and a campaign ("Customer care / order notifications").
  Approval is typically 1–3 business days for the brand; campaign vetting can
  take longer. Fees: small one-time brand fee, ~$10/month campaign, plus
  per-message carrier fees — confirm on Twilio's pricing page.
- The site needs a public SMS terms/privacy section (the campaign reviewer
  checks it) — added in 6.3.

## Success criteria

- Nothing is ever texted while `SMS_ENABLED=false`.
- A buyer only gets texts after entering a phone number **and** ticking an
  unchecked consent box; consent time/IP/text version stored.
- Replying STOP stops all texts to that number immediately; HELP gets a
  help reply; START resubscribes.
- Admin SMS respects preferences and requires a verified phone.

## Sub-phases

### 6.1 — Package and config — `checkpoint: package`
- `composer require laravel-notification-channels/twilio` (v4.2, Laravel 13,
  PHP ^8.3 — we're on 8.4). Config: SID, token, from number,
  `SMS_ENABLED`, quiet hours (default 9pm–8am, buyer timezone fallback
  America/Denver).

### 6.2 — Phones and consent — `checkpoint: consent`
- `sms_recipients` table (polymorphic owner, `phone_e164`, `verified_at`,
  `consented_at`, `consent_ip`, `consent_text_version`, `opted_out_at`).
- Verify phone by 6-digit code (rate limited) before any notification text.
- Buyer: phone + consent on notifications page and on Will Call checkout
  ("Text me when it's ready"). Admin: on My notifications page.

### 6.3 — Compliance — `checkpoint: compliance`
- Inbound webhook `POST /webhooks/twilio/sms` (Twilio signature validated):
  STOP/STOPALL/UNSUBSCRIBE/CANCEL/END/QUIT → opt out; HELP → help text;
  START/UNSTOP → opt back in.
- First text includes brand + "Reply STOP to opt out".
- Quiet hours: non-urgent texts deferred to 8am (admin new will-call order
  is not deferred during business hours only — configurable).
- Terms page section describing message types, frequency, rates, STOP/HELP.

### 6.4 — Channel wiring — `checkpoint: channel`
- `toTwilio()` on SMS-allowed types: ≤ 160 chars, no URLs shorteners from
  third parties (use own domain link).
- `NotificationChannels`: SMS only if enabled + allowed type + verified +
  consented + not opted out + preference on.

## Tests

- Feature: flag off → never sent; consent required; STOP/HELP/START webhook
  (valid and invalid signatures); quiet-hours deferral; message length.
- `Notification::fake()`; never hit Twilio in tests.

## Risks

- Registration rejection or delay — the rest of the module ships without SMS.
- Cost growth with volume — SMS limited to three types by design.

STOP. Await phase approval.

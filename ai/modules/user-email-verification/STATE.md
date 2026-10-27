# STATE — user-email-verification

## Status Table

| Item | Status |
|------|--------|
| Module plan | Approved (all 5 phases) |
| Phase 1 — Conditional enforcement | Complete |
| Phase 2 — Branded email | Complete |
| Phase 3 — Admin toggle + ops | Complete |
| Phase 4 — Order tracker + confirmation email | Complete |
| Phase 5 — Status-change notifications (email + bell) | Awaiting Validation |

## Log

- **2026-07-10 (phase 5 validation)** — Completed buyer-facing order status
  notifications by email and database bell. Order observer sends one
  most-significant cancellation/refund/fulfillment update per save; shipment
  observer handles shipped/delivered creation and updates without announcing
  pending shipments. Stripe/PayPal payment notifications now resolve account
  holders through `OrderNotifier` for mail + database while guests remain
  mail-only. Added authenticated, verification-ungated notification list and
  mark-read endpoints plus the responsive customer-header bell. Validation:
  50 module-focused tests passed (152 assertions); Vite production build,
  `pint --dirty --test`, and `git diff --check` passed. Full suite completed
  with the temporary 512 MB process limit at the unchanged 71-failure
  baseline, with 511 passing and 5 skipped (1850 assertions); no phase-5
  regression identified. Awaiting owner validation.

- **2026-07-10 (phase 5 approved)** — Owner approved the reconciled phase-5
  scope. Marked In Progress; implementation and validation underway.

- **2026-07-10 (phase 4 approved / phase 5 scope check)** — Owner approved
  phase 4; marked complete. Reconfirmed phase-5 scope against current code:
  `CRM\OrderResource` is view-only; shipped/delivered are shipment lifecycle
  transitions (`status`, `shipped_at`, `delivered_at`); fulfilled remains an
  order transition; and Stripe/PayPal currently route payment notifications
  through mail-only anonymous recipients, which must be replaced with the
  account-aware notifier for database bell delivery. `AuthenticatedLayout`
  is the correct customer shell for the bell. Phase-5 plan amended and set
  to Awaiting Phase Approval. No phase-5 implementation was changed at this
  gate; partial unvalidated backend files already in the worktree remain for
  the approved implementation pass.

- **2026-07-10 (phase 4 validation)** — Completed the signed public order
  tracker and confirmation-email integration. Audited both order-creation
  paths and closed the cart-checkout gap: `StoreCheckoutService` now sends
  `OrderConfirmationMail` after its transaction so the signed tracker email
  includes all persisted line items. Added a regression assertion for the
  checkout recipient/mailable. Validation: 29 phase-focused tests passed
  (89 assertions); Vite production build passed; `pint --dirty --test`
  passed. Full suite completed with a temporary 512 MB process limit and
  matched the recorded baseline exactly at 71 failures (499 passing, 5
  skipped); no phase-4 regression identified. Awaiting owner validation.

- **2026-07-10 (phase 1)** — Implemented and verified end-to-end.
  `User implements MustVerifyEmail` with a send-skip when the
  `email-verification` setting is disabled; `EmailVerificationSettings`
  support class; `verified.enabled` middleware (web-guard only — admins
  pass through) applied to dashboard/reports, company, SAM favorites,
  mil-spec download. `/profile` deliberately ungated (unverified users must
  be able to fix a typoed email). Checkout ungated per owner decision.
  Backfill migration grandfathers pre-existing users (ran locally).
  `.env` local MAIL_MAILER switched to `log` (Mailgun rejects local sends;
  sync notification would have made registration 500 locally).
  8 new Pest tests (checkpoint: all sub-phases complete); suite at exact
  71-failure baseline, 466 passing. Browser walkthrough: register →
  notice page → signed log-mail link → verified → dashboard. Flagged for
  phase 2: the notice page (VerifyEmail.vue/GuestLayout) still shows the
  default Laravel logo — should adopt BrandedAuthLayout for brand
  consistency. Awaiting validation.

- **2026-07-10 (amendment 2)** — Owner approved phase 4 and requested
  status-change notifications: emails plus "push". Clarified via question:
  push = in-app notification bell (database channel), no browser web-push.
  Phase 5 written: OrderStatusUpdated notification (shipped/delivered/
  cancelled/refunded), database channel added to existing payment
  notifications, bell UI in customer header, guest orders get email only.
  Awaiting approval of the phase-5 amendment.

- **2026-07-10 (later)** — Owner approved phases 1–3 and added a
  requirement: unverified users who place orders must still see the order
  and its status, via an order-number confirmation email with a status
  tracker. Amended: checkout is NOT verification-gated (00-overview
  decision), and phase 4 added (signed tracker page + confirmation-email
  integration; `OrderConfirmationMail` already exists on the quote→order
  path). Awaiting approval of the phase-4 amendment.

- **2026-07-10** — Module created. Requirements from owner: mandatory email
  verification for customer registrations, professional branded email,
  admin profile-dropdown toggle for development (store-toggle pattern),
  plus best-practice extras. Explored current state: Breeze verification
  routes + VerifyEmail.vue already scaffolded; User lacks MustVerifyEmail;
  McpSettings/store-toggle pattern identified for reuse; mail theme already
  published; Mailgun local-dev sends known to fail (reinforces toggle +
  MAIL_MAILER=log guidance). Plan written; awaiting plan approval.

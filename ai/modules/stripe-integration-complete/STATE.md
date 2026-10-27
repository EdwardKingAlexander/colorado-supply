# Stripe Integration — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-06-12 | Orchestrator sign-off recorded |
| 1 | Foundation & configuration | Complete | 2026-06-12 | Implemented; tests passing |
| 2 | Checkout session creation | Complete | 2026-06-12 | Implemented; tests passing |
| 3 | Webhook handling & idempotency | Complete | 2026-06-12 | Implemented; tests passing |
| 4 | Payment/order status sync & fulfillment | Complete | 2026-06-12 | Implemented; tests passing |
| 5 | Refunds & Filament admin tooling | Complete | 2026-06-12 | Implemented; tests passing |
| 6 | Testing & hardening | Complete | 2026-06-12 | Implemented; tests passing |

## Log

- 2026-06-12 — Plan drafted (`00-overview.md` + phase files 1-6). Awaiting
  orchestrator approval of overall plan before any phase begins.
- 2026-06-12 — Orchestrator approved; Phase 1 implemented:
  - Added `STRIPE_KEY`/`STRIPE_SECRET`/`STRIPE_WEBHOOK_SECRET` to `.env` and
    `phpunit.xml` (test dummies).
  - Added `services.stripe` config block.
  - Added `app/Services/Stripe/StripeClientFactory.php`.
  - Bound `\Stripe\StripeClient` as a singleton in `AppServiceProvider`.
  - Added `tests/Feature/Services/Stripe/StripeClientFactoryTest.php` (3
    passing tests).
  - `vendor/bin/pint` clean on touched files. Pre-existing failure in
    `Tests\Feature\Filament\SamInsightsDashboardTest` confirmed unrelated
    (fails identically on `main` before this change — memory exhaustion in
    Blade compiler for that dashboard).
- 2026-06-12 — Phase 2 implemented:
  - `app/Services/Stripe/StripeCheckoutService.php` — creates a Stripe
    Checkout Session (mode=payment, single line item from
    `Order::getGrandTotalInCents()`, `metadata.order_id`) and
    upserts a `pending`/`stripe` `Payment` row with `gateway_session_id`.
  - `app/Http/Controllers/Api/V1/StripeCheckoutController.php` — `POST
    /api/v1/orders/{order}/checkout`, authorizes `portal_user_id` ownership,
    returns `{ checkout_url }`.
  - `routes/api.php` — registered the checkout route under the
    `auth:sanctum` group.
  - `routes/web.php` + `resources/js/Pages/Store/CheckoutSuccess.vue` /
    `CheckoutCancel.vue` — Inertia result pages for `success_url`/`cancel_url`.
  - `app/Models/Order.php` — added `HasFactory` (was missing); 
    `database/factories/OrderFactory.php` — fleshed out default state for
    testable payable orders.
  - `tests/Support/Stripe/FakeStripeHttpClient.php` — canned-response
    `\Stripe\HttpClient\ClientInterface` implementation for tests (avoids
    real network calls, exercises the real Stripe SDK).
  - `tests/Feature/Api/StripeCheckoutTest.php` — 4 passing tests (session
    creation + Payment upsert, ownership 403, non-payable order rejected,
    repeated checkout reuses pending Payment row).
  - `vendor/bin/pint` clean on all touched files.
- 2026-06-12 — Phase 3 implemented:
  - `app/Http/Controllers/Api/V1/StripeWebhookController.php` — public `POST
    /api/v1/stripe/webhook` (no `auth:sanctum`; Laravel 12's `api` middleware
    group is CSRF-free, so no exemption needed). Verifies
    `Stripe-Signature` via `\Stripe\Webhook::constructEvent`, returns `400`
    on signature/payload errors, checks `StripeEvent::isProcessed()` for
    idempotency, persists a `StripeEvent` row (catching unique-constraint
    races as "already received"), and dispatches
    `ProcessStripeWebhookEvent`.
  - `app/Jobs/ProcessStripeWebhookEvent.php` — loads the `StripeEvent`,
    routes by `type` (`checkout.session.completed`,
    `payment_intent.succeeded`, `payment_intent.payment_failed`,
    `charge.refunded`), reconstructs the Stripe SDK object from the stored
    payload, calls `StripeEvent::markAsProcessed()` afterwards. Unhandled
    types are logged at `info` and marked processed. No-ops if the event was
    already processed.
  - `app/Services/Stripe/StripePaymentSyncService.php` — handlers for the
    four event types. Resolves `Payment`/`Order` via
    `gateway_session_id` / `gateway_payment_intent_id` /
    `metadata.order_id`, using `Order::withoutGlobalScopes()` to bypass
    `CompanyScope` (webhook requests are unauthenticated). Calls
    `Payment::markAsPaid()` / `markAsFailed()` / new `markAsRefunded()` and
    `Order::markAsPaid()` / `markPaymentAsFailed()` / refunded status update.
    Note: this folds in most of Phase 4's handler logic since the job and
    sync handlers are the same unit of work — Phase 4 will focus on any
    remaining refinements (e.g. notifications) rather than re-doing this
    wiring.
  - `app/Models/Payment.php` — added `markAsRefunded(?string $refundId)`.
  - `app/Services/Stripe/StripeCheckoutService.php` — added
    `payment_intent_data.metadata.order_id` to the Checkout Session so the
    resulting PaymentIntent is self-sufficient for order resolution
    regardless of webhook event ordering.
  - `routes/api.php` — registered the public webhook route (outside the
    `auth:sanctum` group).
  - `tests/Feature/Api/StripeWebhookTest.php` — 3 passing tests (valid
    signature creates `StripeEvent` + dispatches job via `Bus::fake()`,
    invalid signature → 400 with no row created, duplicate event id → 200
    with job dispatched only once).
  - `tests/Feature/Jobs/ProcessStripeWebhookEventTest.php` — 8 passing tests
    covering all four event types, unhandled-type no-op, already-processed
    short-circuit, and cross-company `Order` resolution bypassing
    `CompanyScope`.
  - `vendor/bin/pint` clean on all touched files.
- 2026-06-12 — Phase 4 implemented (core sync wiring landed in Phase 3; this
  phase covered the remaining scope — customer notifications and
  end-to-end/idempotency test coverage):
  - `app/Notifications/OrderPaymentReceived.php` and
    `OrderPaymentFailed.php` — new mail notifications (none existed
    previously). Sent on-demand to `Order::customer_email` via
    `Notification::route('mail', ...)` since orders may not have a portal
    user.
  - `app/Services/Stripe/StripePaymentSyncService.php` — wired
    `OrderPaymentReceived` after `Order::markAsPaid()` and
    `OrderPaymentFailed` (with the Stripe failure message) after
    `Order::markPaymentAsFailed()`. Both are naturally idempotent: the
    existing "already paid" / per-event guards prevent re-notification on
    duplicate webhook delivery.
  - Verified `Order::markAsPaid()` already confirms the order
    (`status => Confirmed`) and does not touch `fulfillment_status` —
    no changes needed per the Phase 4 "out of scope" note.
  - `tests/Feature/Services/Stripe/StripePaymentSyncServiceTest.php` — 5
    passing tests using real Stripe SDK object fixtures
    (`Session::constructFrom` / `PaymentIntent::constructFrom` /
    `Charge::constructFrom`), covering all four handlers plus an
    idempotency/no-double-notification test.
  - `tests/Feature/Api/StripeWebhookTest.php` — added an end-to-end test:
    posting a `payment_intent.succeeded` webhook (sync queue) results in
    `Order::payment_status === Paid` and `Order::status === Confirmed`.
  - `vendor/bin/pint` clean on all touched files.
- 2026-06-12 — Phase 5 implemented:
  - `app/Services/Stripe/StripeRefundService.php` — `refund(Payment, ?amountCents, ?reason)`.
    Validates `gateway === 'stripe'`, `status === Paid`, and
    `gateway_charge_id` is set; calls `$stripe->refunds->create()`. Full
    refunds (amount omitted or >= payment amount) call
    `Payment::markAsRefunded()`. Partial refunds keep `status = Paid`,
    record the refund in `payment.meta.refunds[]`, and set
    `gateway_refund_id` to the latest refund — no new `PaymentStatus` case
    added (kept simple per the phase doc's "or out of scope" option).
  - `app/Filament/Resources/CRM/OrderResource/RelationManagers/PaymentsRelationManager.php`
    (new) — lists an order's payments with status/amount/charge/refund
    columns and a "Refund" `Action` (confirm modal: amount defaulting to
    full, reason select). Visible only when `gateway === 'stripe'` and
    `status === Paid`. Registered in `OrderResource::getRelations()`.
  - `app/Filament/Resources/StripeEventResource.php` +
    `Pages/ListStripeEvents.php` (new, read-only, `navigationGroup =
    'Admin'`) — table of `StripeEvent` rows (`stripe_event_id`, `type`,
    processed indicator, `processed_at`, `created_at`).
  - `tests/Feature/Filament/PaymentRefundActionTest.php` — 3 passing tests:
    refund action visibility (paid Stripe vs. pending/cash payments), full
    refund via `StripeRefundService` (fake Stripe HTTP client) marks
    `Payment` refunded, partial refund leaves `status = Paid` and records
    the refund in `meta`.
  - `tests/Feature/Filament/StripeEventResourceTest.php` — 1 passing test
    listing `StripeEvent` records as a `super_admin`.
  - `vendor/bin/pint` clean on all touched files (13 files).
- 2026-06-12 — Phase 6 implemented:
  - `app/Services/Stripe/StripeCheckoutService.php` — wrapped
    `checkout->sessions->create()` in try/catch for
    `Stripe\Exception\ApiErrorException`; logs `order_id`,
    `stripe_error_code`, and `message` via `Log::error`, then rethrows as a
    `RuntimeException` with a user-safe message (already converted to a
    `422 ValidationException` by `StripeCheckoutController`).
  - `app/Services/Stripe/StripeRefundService.php` — same pattern around
    `refunds->create()`, logging `payment_id`/`order_id`/
    `gateway_charge_id`/`stripe_error_code`/`message` and rethrowing as
    `RuntimeException` (already surfaced as a Filament danger notification by
    `PaymentsRelationManager::refundAction`).
  - `app/Services/Stripe/StripePaymentSyncService.php` — added
    `Log::warning` calls (with the relevant Stripe IDs) for the "no matching
    Payment found" branches in all four event handlers, so orphaned/odd
    webhook events are observable without failing the webhook request.
  - `routes/api.php` — added `throttle:120,1` to the public
    `/api/v1/stripe/webhook` route (generous enough for Stripe's
    retry-with-backoff behavior while providing basic abuse protection).
  - `tests/Feature/Api/StripeIntegrationFlowTest.php` (new) — 2 passing
    tests:
    - End-to-end happy path: checkout session creation (via
      `FakeStripeHttpClient`) → `payment_intent.succeeded` webhook → asserts
      `Order::payment_status === Paid`, `Order::status === Confirmed`, and
      the `Payment` row is `paid` with both `gateway_session_id` and
      `gateway_payment_intent_id` set.
    - Negative path: `FakeStripeHttpClient` returns a Stripe `api_error`
      with HTTP 500 for `checkout/sessions`; asserts the API responds `422`
      with a clean `order` validation message (no raw Stripe exception/stack
      trace leaked) and no `Payment` row is created.
  - Full Stripe suite (28 tests, 87 assertions) passes:
    `StripeCheckoutTest`, `StripeIntegrationFlowTest`, `StripeWebhookTest`,
    `PaymentRefundActionTest`, `StripeEventResourceTest`,
    `ProcessStripeWebhookEventTest`, `StripeClientFactoryTest`,
    `StripePaymentSyncServiceTest`.
  - `vendor/bin/pint` clean on all Phase 6 touched files (5 files).
  - Ran the full `php artisan test` suite: failures in
    `Tests\Feature\Auth\Rbac\UserManagementTest`,
    `Tests\Feature\ComplianceAuditToolTest`,
    `Tests\Feature\Filament\CustomerResourceGooglePlacesTest`, and a memory
    exhaustion in `Tests\Feature\Filament\SamInsightsDashboardTest` are
    pre-existing and unrelated to the Stripe integration (RBAC, compliance
    audit, Google Places autocomplete, and SAM dashboard — none of which were
    touched by this module). The SAM dashboard memory exhaustion was already
    flagged as a pre-existing issue in the Phase 1 log entry.
  - Docs: intentionally skipped a `docs/stripe.md`/README update. No `docs/`
    directory or documentation convention exists in this repo, and project
    policy is not to create documentation files unless explicitly requested.
- 2026-06-12 — Orchestrator confirmed Phase 6 complete; module marked
  `Complete`. Stripe integration (checkout, webhooks, payment/order sync,
  refunds, admin tooling, hardening) is done across all 6 phases.
- 2026-06-12 - Post-completion admin testing hardening:
  - `config/sanctum.php` now checks both `web` and `admin` session guards for
    first-party API requests, allowing an admin-only session to call checkout
    APIs from the store UI.
  - `StoreCheckoutService` accepts `User|Admin`; admin-created test orders
    intentionally leave `portal_user_id` and `company_id` null instead of
    writing an admin id into the user-owned portal field.
  - `StripeCheckoutController` permits admins to create Checkout Sessions for
    payable orders while preserving portal-user ownership checks for regular
    users.
  - Added regression tests for admin store checkout and admin Stripe Checkout
    session creation.
  - Targeted store/checkout suite passes (19 tests, 107 assertions); full
    Stripe suite passes (31 tests, 106 assertions).

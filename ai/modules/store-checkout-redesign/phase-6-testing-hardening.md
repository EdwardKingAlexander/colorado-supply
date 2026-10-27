# Phase 6 — End-to-End Testing & Hardening

## Goal

Tie Phases 1-5 together with a full cart → checkout → payment integration
test for each gateway, close any gaps found, and confirm the whole suite
(plus the pre-existing Stripe suite from `stripe-integration`) is green.

## Work

- **`tests/Feature/StoreCheckoutFlowTest.php`** (new):
  - Full Stripe path: add items to cart (via direct `useCartStore`
    payload equivalent — i.e. construct the same JSON the frontend would
    send), `POST /api/v1/store/checkout` → `Order` created → `GET
    /store/checkout/{order}/pay` → `POST /api/v1/orders/{order}/checkout`
    (Stripe, `FakeStripeHttpClient`) → simulate `payment_intent.succeeded`
    webhook → assert `Order::payment_status === Paid`,
    `Order::status === Confirmed`.
  - Full PayPal path: same cart → order → `POST
    /api/v1/orders/{order}/checkout/paypal` (`Http::fake()`) → simulate the
    return-redirect capture → assert `Order::payment_status === Paid`.
  - Negative path: checkout with an empty cart → `422`; checkout for an
    order that's already paid → both gateway endpoints reject via
    `canBePaid()` → `422`.
- **Logging/error-handling audit**: confirm `StoreCheckoutController`,
  `PaypalCheckoutController`, and `StorePaypalReturnController` all follow
  the established pattern (catch service-level `RuntimeException`s, never
  leak raw SDK/HTTP exceptions to the client, log with relevant IDs).
- **Route/middleware audit**: confirm every new route
  (`store.checkout`, `store.checkout.pay`, `store.checkout.paypal.return`,
  `/api/v1/store/checkout`, `/api/v1/orders/{order}/checkout/paypal`,
  `/api/v1/paypal/webhook`) has the correct middleware (`auth.web_or_admin`
  + `store.enabled` for web pages, `auth:sanctum` for authenticated API
  routes, public + `throttle:120,1` for the PayPal webhook).
- **Cart/localStorage migration check**: verify that a browser with old
  `colorado-supply.quote` localStorage data doesn't crash the new
  `useCartStore` (Phase 1 changed the storage key to
  `colorado-supply.cart`, so old data is simply ignored — confirm this is
  graceful, not an error).
- **Full regression run**: `php artisan test` for the whole suite. Compare
  against the known pre-existing failures already documented in
  `ai/modules/stripe-integration-complete/STATE.md` (`UserManagementTest`,
  `ComplianceAuditToolTest`, `CustomerResourceGooglePlacesTest`,
  `SamInsightsDashboardTest` memory exhaustion) — these remain out of scope;
  confirm no *new* failures were introduced.
- `vendor/bin/pint --dirty` (or targeted file list for all files touched
  across Phases 1-6).

## Success Criteria

- `tests/Feature/StoreCheckoutFlowTest.php` passes (both gateway paths +
  negative paths).
- Full `php artisan test` run shows no new failures beyond the documented
  pre-existing ones.
- `vendor/bin/pint --dirty` clean across the whole module's changed files.
- `STATE.md`: mark Phase 6 `Awaiting Validation` with a full summary log
  entry; once the orchestrator signs off, mark the module `Complete`.

STOP. Await phase approval.

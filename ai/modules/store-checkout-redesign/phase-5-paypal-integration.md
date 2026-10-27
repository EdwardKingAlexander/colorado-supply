# Phase 5 — PayPal Payment Step (New Gateway)

## Goal

Add PayPal as a second payment gateway on `Store/CheckoutPay.vue`, using
PayPal's **Orders v2 API** with the redirect ("Standard Checkout") flow —
mirroring the existing Stripe module's shape (create → redirect →
return/capture → webhook → `Payment`/`Order` sync) so the two gateways share
the same mental model and the existing `Payment`/`StripeEvent`-style
idempotency pattern.

Raw HTTP via Laravel's `Http` facade is used (no new Composer SDK
dependency), matching this codebase's existing pattern for external APIs
(`SamApiClient`).

## Config / Environment

- **`config/services.php`**: add
  ```php
  'paypal' => [
      'client_id' => env('PAYPAL_CLIENT_ID'),
      'client_secret' => env('PAYPAL_CLIENT_SECRET'),
      'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox|live
      'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
  ],
  ```
- **`.env` / `.env.example` / `phpunit.xml`**: add
  `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_MODE=sandbox`,
  `PAYPAL_WEBHOOK_ID` (dummy test values in `phpunit.xml`, mirroring how
  `STRIPE_*` was added in `stripe-integration` Phase 1).

## Files to Create

- **`app/Services/Paypal/PaypalClientFactory.php`**:
  - `baseUrl(): string` → `https://api-m.sandbox.paypal.com` or
    `https://api-m.paypal.com` based on `config('services.paypal.mode')`.
  - `accessToken(): string` → `POST {baseUrl}/v1/oauth2/token` with HTTP
    Basic auth (`client_id`:`client_secret`), `grant_type=client_credentials`,
    cached briefly via `Cache::remember('paypal_access_token', ...,
    fn () => ...)` using the response's `expires_in` (minus a safety margin).
  - Throws if `client_id`/`client_secret` missing (mirrors
    `StripeClientFactory`'s "throws when secret is missing" test).
- **`app/Services/Paypal/PaypalCheckoutService.php`**:
  - `createOrderForOrder(Order $order): array` (returns
    `['approve_url' => ..., 'paypal_order_id' => ...]`)
  - Validates `$order->canBePaid()` (throws `RuntimeException` like
    `StripeCheckoutService`).
  - `POST {baseUrl}/v2/checkout/orders` with:
    ```json
    {
      "intent": "CAPTURE",
      "purchase_units": [{
        "reference_id": "{order->id}",
        "custom_id": "{order->id}",
        "amount": { "currency_code": "USD", "value": "<grand_total formatted to 2 decimals>" }
      }],
      "application_context": {
        "return_url": "<route('store.checkout.paypal.return', order)>",
        "cancel_url": "<route('store.checkout.cancel', order)>",
        "user_action": "PAY_NOW",
        "shipping_preference": "NO_SHIPPING"
      }
    }
    ```
  - Catches non-2xx responses (`Http::...->throw()` →
    `\Illuminate\Http\Client\RequestException`), logs via `Log::error` with
    `order_id` + response body, rethrows as `RuntimeException('Unable to
    start PayPal checkout. Please try again shortly.')` — same pattern as
    `StripeCheckoutService`'s `ApiErrorException` handling.
  - Upserts a `pending`/`paypal` `Payment` row: `gateway = 'paypal'`,
    `gateway_session_id = <paypal order id>`, `amount = order->grand_total`,
    `method = PaymentMethod::Card` (or add a `PaymentMethod::Paypal` case
    only if the enum is a simple string-backed enum — check
    `app/Enums/PaymentMethod.php` before adding a case; if it's a closed
    enum used elsewhere for display, prefer reusing `Card` to avoid
    unrelated UI changes, and record `gateway` as the source of truth for
    "this was a PayPal payment").
- **`app/Services/Paypal/PaypalCaptureService.php`**:
  - `captureOrder(Payment $payment): array` →
    `POST {baseUrl}/v2/checkout/orders/{paypal_order_id}/capture`. On
    `COMPLETED` status, extracts the capture id
    (`purchase_units[0].payments.captures[0].id`) and returns it; on
    non-2xx or non-`COMPLETED`, throws `RuntimeException`.
- **`app/Services/Paypal/PaypalPaymentSyncService.php`** (mirrors
  `StripePaymentSyncService`):
  - `handleCaptureCompleted(array $resource): void` — resolves `Payment` via
    `gateway = 'paypal'` + `gateway_session_id = resource['supplementary_data']['related_ids']['order_id']`
    (or via `purchase_units[0].custom_id` → `order_id` fallback, same
    dual-resolution pattern as `StripePaymentSyncService::resolvePaymentForIntent`).
    Sets `gateway_charge_id = <capture id>`, calls `markAsPaid()` +
    `Order::markAsPaid()` + reuses `OrderPaymentReceived` notification
    (already built in stripe-integration — gateway-agnostic).
  - Logs a warning (no-op) if no matching `Payment` is found, same as the
    Stripe handlers.
- **`app/Models/PaypalEvent.php`** + migration
  **`database/migrations/xxxx_create_paypal_events_table.php`**: same shape
  as `StripeEvent`/`stripe_events` (`paypal_event_id` unique, `type`,
  `payload` json, `processed_at`).
- **`app/Jobs/ProcessPaypalWebhookEvent.php`**: mirrors
  `ProcessStripeWebhookEvent` — loads `PaypalEvent`, routes
  `PAYMENT.CAPTURE.COMPLETED` → `PaypalPaymentSyncService::handleCaptureCompleted()`,
  other types logged at `info` and marked processed.
- **`app/Http/Controllers/Api/V1/PaypalCheckoutController.php`**:
  - `store(Request $request, Order $order): JsonResponse` — same ownership
    check as `StripeCheckoutController::store` (`portal_user_id` ownership),
    calls `PaypalCheckoutService::createOrderForOrder()`, catches
    `RuntimeException` → `ValidationException::withMessages(['order' => ...])`,
    returns `{ approve_url }`.
- **`app/Http/Controllers/Api/V1/PaypalWebhookController.php`**:
  - `handle(Request $request)` — verifies signature via
    `POST {baseUrl}/v1/notifications/verify-webhook-signature` (PayPal's
    server-side verification API, using the stored `transmission-id`/
    `transmission-time`/`cert-url`/`auth-algo`/`transmission-sig` headers +
    `webhook_id` + raw body). On `verification_status !== 'SUCCESS'` → `400`.
    Otherwise: `PaypalEvent::isProcessed()` check, create row, dispatch
    `ProcessPaypalWebhookEvent` — identical control flow to
    `StripeWebhookController`.
- **`app/Http/Controllers/StorePaypalReturnController.php`** (web,
  authenticated):
  - `return(Order $order)`: reads `?token=<paypal_order_id>` from the query
    string, finds the matching `pending`/`paypal` `Payment`, calls
    `PaypalCaptureService::captureOrder()`. On success, calls
    `PaypalPaymentSyncService`'s same paid-marking logic directly (capture
    happens synchronously here — the webhook is a reliability backstop for
    cases where the user closes the tab before returning) and redirects to
    `store.checkout.success`. On failure, redirects to
    `store.checkout.cancel` with a flash error message.

## Files to Modify

- **`routes/api.php`**:
  - `Route::post('/orders/{order}/checkout/paypal', [PaypalCheckoutController::class, 'store']);`
    inside the `auth:sanctum` group.
  - `Route::post('/paypal/webhook', [PaypalWebhookController::class, 'handle'])->middleware('throttle:120,1');`
    public, alongside `/stripe/webhook`.
- **`routes/web.php`**:
  - `Route::get('/store/checkout/{order}/paypal/return', [StorePaypalReturnController::class, 'return'])
      ->middleware(['auth.web_or_admin', 'store.enabled'])
      ->name('store.checkout.paypal.return');`
- **`resources/js/Pages/Store/CheckoutPay.vue`**: add a "Pay with PayPal"
  button next to "Pay with Card":
  - On click: `POST /api/v1/orders/{order.id}/checkout/paypal` → on success
    `window.location.href = approve_url`. Same loading/error handling
    pattern as the Stripe button from Phase 4.

## Tests to Create

- **`tests/Support/Paypal/`**: use `Http::fake()` with a response map for
  `*/v1/oauth2/token`, `*/v2/checkout/orders`, `*/v2/checkout/orders/*/capture`,
  and `*/v1/notifications/verify-webhook-signature` — no custom fake client
  class needed since `Http::fake()` is built into Laravel (simpler than
  Stripe's `FakeStripeHttpClient`, which was required because the Stripe SDK
  bypasses the `Http` facade).
- **`tests/Feature/Services/Paypal/PaypalClientFactoryTest.php`**: access
  token retrieval + caching, throws when credentials missing.
- **`tests/Feature/Api/PaypalCheckoutTest.php`**: creates a PayPal order for
  an owned/payable order → `201` with `approve_url`, upserts a `pending`/
  `paypal` `Payment`; ownership 403; non-payable order rejected (mirrors
  `StripeCheckoutTest`).
- **`tests/Feature/Api/PaypalWebhookTest.php`**: valid signature →
  `PaypalEvent` created + job dispatched (`Bus::fake()`); invalid signature
  → `400`; duplicate event id not reprocessed (mirrors `StripeWebhookTest`).
- **`tests/Feature/Services/Paypal/PaypalPaymentSyncServiceTest.php`**:
  `handleCaptureCompleted()` marks `Payment`/`Order` paid and fires
  `OrderPaymentReceived` (mirrors `StripePaymentSyncServiceTest`).
- **`tests/Feature/StorePaypalReturnControllerTest.php`**: simulates the
  return redirect with `?token=`, asserts capture is called, `Payment`/
  `Order` marked paid, redirect to `store.checkout.success`.

## Success Criteria

- "Pay with PayPal" button creates a PayPal order and redirects to PayPal's
  sandbox approval page (manual verification once `PAYPAL_CLIENT_ID`/
  `PAYPAL_CLIENT_SECRET` sandbox credentials are supplied — see
  `00-overview.md` Assumptions).
- Returning from PayPal approval captures the payment and marks
  `Payment`/`Order` paid; the webhook path provides idempotent backup
  confirmation.
- All new PayPal tests pass; existing Stripe tests unaffected.
- `vendor/bin/pint --dirty` clean.
- `STATE.md` updated to `Awaiting Validation` with a log entry, including a
  note that live PayPal sandbox testing requires real sandbox credentials
  (not available in this environment) — automated tests use `Http::fake()`.

STOP. Await phase approval.

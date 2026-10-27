# Phase 2 — Checkout Order-Creation Backend

## Goal

Add a `POST /api/v1/store/checkout` endpoint that takes the cart payload
(same shape `useCartStore`/`Cart.vue` already sends to `/api/v1/store/quote`)
plus shipping/billing address + contact info, and creates a real `Order`
(`status = Draft`, `payment_status = Unpaid`) with `OrderItems`, owned by the
authenticated portal user. This is the backend half of "Proceed to Checkout".

## Files to Create / Modify

- **`app/Http/Controllers/Api/V1/StoreCheckoutController.php`** (new):
  - `store(Request $request): JsonResponse`
  - Requires `auth:sanctum` (register under the existing
    `Route::middleware('auth:sanctum')->group(...)` block in
    `routes/api.php`, alongside `/orders/{order}/checkout`).
  - Validates:
    ```php
    'items' => ['required', 'array', 'min:1'],
    'items.*.id' => ['required'],
    'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
    'items.*.name' => ['required', 'string', 'max:255'],
    'items.*.quantity' => ['required', 'integer', 'min:1'],
    'items.*.price' => ['required', 'numeric', 'min:0'],
    'items.*.slug' => ['nullable', 'string', 'max:255'],
    'items.*.location_id' => ['nullable', 'integer', 'exists:locations,id'],
    'contact_name' => ['required', 'string', 'max:255'],
    'contact_email' => ['required', 'email', 'max:255'],
    'contact_phone' => ['nullable', 'string', 'max:50'],
    'company_name' => ['nullable', 'string', 'max:255'],
    'po_number' => ['nullable', 'string', 'max:100'],
    'job_number' => ['nullable', 'string', 'max:100'],
    'notes' => ['nullable', 'string', 'max:1000'],
    'billing_address' => ['required', 'array'],
    'billing_address.line1' => ['required', 'string', 'max:255'],
    'billing_address.line2' => ['nullable', 'string', 'max:255'],
    'billing_address.city' => ['required', 'string', 'max:255'],
    'billing_address.state' => ['required', 'string', 'max:50'],
    'billing_address.postal_code' => ['required', 'string', 'max:20'],
    'billing_address.country' => ['required', 'string', 'max:2'],
    'shipping_address' => ['nullable', 'array'],
    'shipping_address.*' => [/* same shape as billing, all optional */],
    'shipping_same_as_billing' => ['boolean'],
    ```
  - Delegates to `StoreCheckoutService::createFromCart()`.
  - Returns `201` with `{ order: { id, order_number, grand_total, ... } }`.
- **`app/Services/StoreCheckoutService.php`** (new):
  - `createFromCart(User $user, array $validated): Order`
  - Mirrors `QuoteOrderingService::convert()`'s `Order::create()` shape but
    sourced from `$validated['items']` instead of `Quote->items`:
    - `order_number` via `app(OrderNumberGenerator::class)->next()` (same
      generator Phase-1/2 of stripe-integration's `OrderFactory` already
      uses — reuse it, don't duplicate the `ORD-YYYYMMDD-XXXX` logic from
      `QuoteOrderingService::generateOrderNumber()`).
    - `customer_id = null`, `portal_user_id = $user->id`,
      `company_id = $user->company_id`.
    - `contact_name`/`contact_email`/`contact_phone`/`company_name`/
      `po_number`/`job_number`/`notes` from validated input.
    - `billing_address` = validated billing array; `shipping_address` =
      validated shipping array, or billing array if
      `shipping_same_as_billing` (default `true` when `shipping_address`
      omitted).
    - Compute `subtotal` = `sum(price * quantity)`, `tax_total = 0`,
      `shipping_total = 0`, `discount_total = 0`,
      `grand_total = subtotal` (no tax/shipping calc in scope — matches
      current cart behavior which also sends `tax: 0`).
    - `tax_rate = 0`, `status = OrderStatus::Draft`,
      `payment_status = PaymentStatus::Unpaid`,
      `fulfillment_status = FulfillmentStatus::Unfulfilled`.
    - Wrap `Order::create()` + `$order->items()->createMany(...)` in
      `DB::transaction()`.
    - Each `OrderItem`: `product_id` (from payload, validated to exist),
      `sku` = `item.slug`, `name`, `quantity`, `unit_price = price`,
      `line_discount = 0`, `line_total = price * quantity`,
      `meta = ['location_id' => item.location_id]` (no `location_id` column
      on `order_items` per the migration — confirm during implementation; if
      a column exists, use it directly instead of `meta`).
  - Throws `ValidationException` only for genuinely invalid state (e.g. an
    empty `items` array reaching the service) — most validation lives in the
    Form Request/controller.
- **`routes/api.php`**: add
  ```php
  Route::post('/store/checkout', [StoreCheckoutController::class, 'store']);
  ```
  inside the existing `auth:sanctum` group (next to
  `/orders/{order}/checkout`).

## Tests to Write

- **`tests/Feature/Api/StoreCheckoutTest.php`** (new):
  - Authenticated portal user posts a valid cart payload → `201`, asserts an
    `Order` row exists with `status = Draft`, `payment_status = Unpaid`,
    `portal_user_id = $user->id`, `company_id = $user->company_id`, correct
    `subtotal`/`grand_total`, and the right number of `OrderItems` with
    correct `unit_price`/`quantity`/`line_total`.
  - Missing required fields (e.g. no `billing_address.city`, no `items`) →
    `422` with `assertJsonValidationErrors`.
  - Two different users posting carts each get their own `Order` rows
    (no cross-contamination), and `company_id` is correctly attributed per
    user.
  - `shipping_same_as_billing = true` (or omitted) → `shipping_address`
    equals `billing_address` on the created order.

## Success Criteria

- `POST /api/v1/store/checkout` creates an `Order` + `OrderItems` matching
  the validated cart payload, owned by the requesting user.
- `php artisan test tests/Feature/Api/StoreCheckoutTest.php` passes.
- `vendor/bin/pint --dirty` clean.
- `STATE.md` updated to `Awaiting Validation` with a log entry describing the
  new controller/service/route/tests and confirming the `order_items`
  schema's handling of `location_id` (column vs. `meta`).

STOP. Await phase approval.

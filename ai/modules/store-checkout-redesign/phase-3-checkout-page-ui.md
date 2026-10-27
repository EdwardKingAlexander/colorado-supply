# Phase 3 — Checkout Page UI (Address + Order Summary)

## Goal

Add the two-step checkout UI: an address/contact form that submits the cart
to Phase 2's `POST /api/v1/store/checkout`, and an order-summary/payment page
that Phases 4 and 5 will attach payment buttons to. After this phase, a user
can go Cart → Checkout (fill address) → Order created → Payment page showing
the order total and a "Pay" section placeholder area (no live gateways yet —
those land in Phases 4/5).

## Files to Create / Modify

- **`routes/web.php`**:
  - `Route::get('/store/checkout', [StoreController::class, 'checkout'])
      ->middleware(['auth.web_or_admin', 'store.enabled'])
      ->name('store.checkout');`
  - `Route::get('/store/checkout/{order}/pay', [StoreController::class, 'checkoutPay'])
      ->middleware(['auth.web_or_admin', 'store.enabled'])
      ->name('store.checkout.pay');`
    - Authorize: abort 403 if `$order->portal_user_id !== auth()->id()`
      (mirrors the ownership check in `StripeCheckoutController::store`).
- **`app/Http/Controllers/StoreController.php`**:
  - `checkout()`: renders `Store/Checkout` Inertia page, passing `locations`
    (same data the existing `Cart`/`QuoteView` page receives) and
    `auth.user` contact defaults (`name`, `email`) for prefill.
  - `checkoutPay(Order $order)`: renders `Store/CheckoutPay` Inertia page,
    passing the order (id, order_number, grand_total, line items via
    `$order->load('items')`, `payment_status`, addresses).
- **`resources/js/Pages/Store/Checkout.vue`** (new):
  - Reads cart from `useCartStore` (from Phase 1). If cart is empty, redirect
    to `store.cart` (empty carts can't check out).
  - Form fields: contact name/email/phone, company name, PO number, job
    number, notes, billing address (line1/line2/city/state/postal_code/
    country), a "shipping same as billing" checkbox that hides/shows a
    separate shipping address block.
  - Order summary sidebar: line items grouped by location (reuse the
    grouping logic from `Cart.vue`/old `QuoteView.vue`), subtotal, total.
  - On submit: `POST /api/v1/store/checkout` with cart items + form fields
    (matching Phase 2's validation shape exactly). On success:
    `cartStore.clearCart()`, then `router.visit(route('store.checkout.pay', { order: response.data.order.id }))`.
    On `422`, surface field errors inline (standard Inertia/axios error
    display pattern used elsewhere in this app — check
    `resources/js/Pages/Auth/*.vue` for the existing convention and match
    it).
- **`resources/js/Pages/Store/CheckoutPay.vue`** (new):
  - Displays order number, line items, grand total, and the addresses
    entered in the previous step (read-only confirmation).
  - A "Choose a payment method" section with a placeholder note
    (e.g. "Payment options" heading + empty container `<div id="payment-methods">`)
    that Phase 4 (Stripe) and Phase 5 (PayPal) will populate with real
    buttons — **do not ship fake/disabled buttons**; an empty, clearly-
    labeled section is acceptable as an intermediate state since Phase 4
    lands immediately after and completes the page.
  - If `order.payment_status === 'paid'`, show a "this order is already paid"
    message with a link to `store.checkout.success`.

## Tests to Write

- **`tests/Feature/StoreCheckoutPageTest.php`** (new, Inertia):
  - `GET /store/checkout` (authenticated) → `assertInertia` renders
    `Store/Checkout` with `locations` prop.
  - `GET /store/checkout/{order}/pay` for the order's owner → renders
    `Store/CheckoutPay` with the order's items/total.
  - `GET /store/checkout/{order}/pay` for a different user's order → `403`.
  - Guests (`->actingAs` omitted) → redirected to `login` for both routes.

## Success Criteria

- Full click-through (manually verified via dev server, per
  `CLAUDE.md`'s "test the golden path in a browser" guidance): Cart →
  "Proceed to Checkout" → fill address form → "Place Order" → lands on
  `/store/checkout/{order}/pay` showing the correct order summary, and the
  `Order`/`OrderItems` rows from Phase 2 are correct.
- `npm run build` succeeds.
- `php artisan test tests/Feature/StoreCheckoutPageTest.php` passes.
- `vendor/bin/pint --dirty` clean.
- `STATE.md` updated to `Awaiting Validation` with a log entry and a note
  confirming the inline-error display pattern used (cite the file it was
  copied from).

STOP. Await phase approval.

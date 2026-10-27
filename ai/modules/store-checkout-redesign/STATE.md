# Store / Cart / Checkout Redesign — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | In Progress | 2026-06-12 | Orchestrator approved starting implementation |
| 1 | Cart terminology & UI redesign | Awaiting Validation | 2026-06-12 | |
| 2 | Checkout order-creation backend | Awaiting Validation | 2026-06-12 | |
| 3 | Checkout page UI (address + summary) | Awaiting Validation | 2026-06-12 | |
| 4 | Stripe payment step (card + Google/Apple Pay) | Awaiting Validation | 2026-06-12 | |
| 5 | PayPal payment step (new gateway) | Awaiting Validation | 2026-07-04 | Log was stale — code was actually already implemented; one real bug found and fixed |
| 6 | Testing & hardening | Awaiting Validation | 2026-07-04 | |

## Log

- 2026-06-12 — Plan drafted (`00-overview.md` + phase files 1-6) per the
  user's request to redesign the store/cart UI (rename "Add to Quote" →
  "Add to Cart", replace the PDF "Generate Quote" cart action with a real
  checkout that creates an `Order`), and add Stripe (existing), PayPal
  (new), and Google Pay (via Stripe Checkout wallets) as payment methods.
  Clarifications received:
  - Cart → real `Order` + payment flow, replacing the PDF-quote action
    entirely (CRM `Quote` feature itself is untouched/out of scope).
  - PayPal/Google Pay credentials will be supplied later; plan builds with
    `.env` placeholders and `Http::fake()`/`FakeStripeHttpClient`-based
    tests so it's verifiable without live credentials.
  - Redesign covers both the public and authenticated store surfaces, within
    the existing `auth.web_or_admin` gating (no new guest-checkout path —
    documented as an explicit assumption in `00-overview.md`).
  - Address capture: no existing checkout address form: `Order` already has
    `billing_address`/`shipping_address` JSON columns (used by
    `QuoteOrderingService`), reused as-is — new UI form added in Phase 3.

  Awaiting orchestrator approval of the overall plan before Phase 1 begins.

- 2026-06-12 — Phase 1 implemented (cart terminology & UI redesign):
  - `resources/js/Stores/useQuoteStore.js` → renamed to
    `resources/js/Stores/useCartStore.js`: exported function
    `useQuoteStore` → `useCartStore`, `clearQuote` → `clearCart`,
    `STORAGE_KEY` `colorado-supply.quote` → `colorado-supply.cart`. All
    other behavior (`addItem`, `removeItem`, `incrementQuantity`,
    `decrementQuantity`, `updateItemLocation`, `itemCount`, `total`,
    `totalCost`) preserved as-is.
  - `resources/js/Pages/Store/QuoteView.vue` → renamed to
    `resources/js/Pages/Store/Cart.vue`: imports `useCartStore`; header
    now "Cart Summary" / "Your Cart"; removed the entire PDF-quote flow
    (delivery modal, `generating`/`downloadMessage`/`downloadError`/
    `lastQuoteNumber`/`lastQuoteId`/`showDeliveryModal`/`emailInput`/
    `emailError` state, `buildPayload`/`downloadPdf`/`generateQuote`/
    `syncQuoteMeta`/`handleDeliverySelection` handlers, and the
    `axios.post('/api/v1/store/quote', ...)` call) and the "Generate
    Quote"/"Email Quote"/"Download PDF" buttons; added a single
    "Proceed to Checkout" `<Link href="/store/checkout">` (disabled via
    `pointer-events-none`/`aria-disabled` when the cart is empty — Phase 3
    will add the real `store.checkout` named route, at which point this
    can switch to `route('store.checkout')`); empty-state copy now "Your
    cart is empty" and "Cart Total" label.
  - `resources/js/Components/Store/ProductRow.vue`: `useQuoteStore` →
    `useCartStore`, `quoteStore` → `cartStore`, `handleAddToQuote` →
    `handleAddToCart`; both "Add to Quote" labels (desktop + mobile rows)
    → "Add to Cart".
  - `resources/js/Pages/Store/ProductDetail.vue`: same renames
    (`useCartStore`/`cartStore`/`handleAddToCart`); "Add to Quote" → "Add
    to Cart".
  - `resources/js/Components/Store/ProductList.vue`: table header "Quote"
    → "Cart".
  - `resources/js/Pages/Store/StoreIndex.vue`: `useQuoteStore` →
    `useCartStore`, `quoteStore`/`quoteItemCount` → `cartStore`/
    `cartItemCount`; heading "Browse products & build your quote" →
    "Browse products & build your cart"; cart-CTA link now
    `route('store.cart')` (was `route('store.quote')`).
  - `routes/web.php`: renamed route `store.quote`
    (`GET /store/quote` → `StoreController::quote`) to `store.cart`
    (`GET /store/cart` → `StoreController::cart`); still ordered before
    the catch-all `/store/{slug}` route.
  - `app/Http/Controllers/StoreController.php`: `quote()` → `cart()`,
    renders `Inertia::render('Store/Cart', ['locations' => $locations])`
    (admin-vs-company `$locations` logic unchanged).
  - `tests/Feature/StoreTest.php`: renamed the two quote-page tests to
    "store cart page is accessible to authenticated users/admins",
    updated to `GET /store/cart` and `component('Store/Cart')`.
  - `resources/js/Pages/Store/Public.vue`: confirmed no changes needed
    (no "quote"/cart terminology present on this public marketing page).
  - Out-of-scope CRM Quote/PDF feature (`app/Mail/QuotePdfMail.php`,
    `app/Http/Controllers/Api/V1/QuoteController.php`, the
    `/api/v1/store/quote` endpoint, and
    `tests/Feature/Api/V1/StoreApiTest.php`) left untouched — confirmed via
    grep, this is the separate CRM Quote/QuoteItem PDF flow.
  - Verification: `npm run build` succeeds; `vendor/bin/pint --dirty`
    reports 0 files needing changes (clean); `php artisan test
    tests/Feature/StoreTest.php` → 4 passed, 1 failed. The failure
    ("Attempt to read property 'locations' on null" in
    `StoreController::cart()`) is **pre-existing**: confirmed by stashing
    all Phase 1 changes and re-running the original
    `tests/Feature/StoreTest.php` against `StoreController::quote()` —
    same 1 failed / 4 passed result, because `User::factory()->create()`
    does not attach a `company`, so `$user->company->locations` is null.
    Not a Phase 1 regression; not fixed here since it's outside Phase 1's
    scope (cart terminology/UI only).

  Awaiting orchestrator approval of Phase 1 before starting Phase 2.

- 2026-06-12 — Phase 2 implemented (checkout order-creation backend):
  - **`app/Http/Controllers/Api/V1/StoreCheckoutController.php`** (new):
    `store(Request $request)` validates the cart payload (items,
    contact info, billing/shipping address, `shipping_same_as_billing`)
    per the phase spec — `shipping_address.*` fields are validated as
    `nullable` (not `required_unless`, which incorrectly fired when
    `shipping_address` itself was omitted and `shipping_same_as_billing`
    wasn't explicitly sent); delegates to
    `StoreCheckoutService::createFromCart()`; returns `201` with
    `{ order: { id, order_number, subtotal, grand_total, status,
    payment_status } }`.
  - **`app/Services/StoreCheckoutService.php`** (new):
    `createFromCart(User $user, array $validated): Order` — generates
    `order_number` via `OrderNumberGenerator::next()`, sets
    `portal_user_id = $user->id`, `company_id = $user->company_id`,
    `status = Draft`, `payment_status = Unpaid`,
    `fulfillment_status = Unfulfilled`, `tax_total`/`shipping_total`/
    `discount_total = 0`, `subtotal = grand_total = sum(price * qty)`.
    `shipping_address` = billing address when `shipping_same_as_billing`
    is true/omitted or `shipping_address` is empty, else the provided
    shipping address. Wraps `Order::create()` +
    `$order->items()->create(...)` per item in `DB::transaction()`.
  - **`order_items` schema confirmed**: the table has a real
    `location_id` foreign key column (added by migration
    `2025_11_30_005831_add_location_id_to_order_items_table.php`), so each
    `OrderItem` is created with `location_id` set directly from
    `item.location_id` — no `meta` JSON needed for this.
  - **`routes/api.php`**: added
    `Route::post('/store/checkout', [StoreCheckoutController::class, 'store'])`
    inside the existing `auth:sanctum` group, alongside
    `/orders/{order}/checkout`.
  - **`tests/Feature/Api/StoreCheckoutTest.php`** (new, 4 tests): valid
    cart → `201` + `Order`/`OrderItems` with correct totals/location;
    missing `items`/`billing_address.city` → `422` with
    `assertJsonValidationErrors`; two different users/companies each get
    their own `Order` with correct `company_id`/`portal_user_id`;
    `shipping_same_as_billing` (default) → `shipping_address` ===
    `billing_address`. `Company`/`Location` rows created directly via
    `::create()` (no factories exist for those models) since
    `User::factory()` doesn't attach a `company_id` by default.
  - Verification: `php artisan test tests/Feature/Api/StoreCheckoutTest.php`
    → 4 passed; `tests/Feature/Api/StripeCheckoutTest.php` +
    `StripeIntegrationFlowTest.php` → 6 passed (no regressions);
    `vendor/bin/pint --dirty` → 0 files needing changes.

  Awaiting orchestrator approval of Phase 2 before starting Phase 3.

- 2026-06-12 — Phase 3 implemented (checkout page UI: address form +
  order summary/payment placeholder):
  - **`app/Http/Controllers/StoreController.php`**: added
    `use App\Models\Order;` and two new methods:
    - `checkout(): Response` — same admin-vs-company `$locations` lookup
      as `cart()`/`index()`, renders `Inertia::render('Store/Checkout', [
      'locations' => ..., 'contact' => ['name' => $user->name, 'email' =>
      $user->email]])`.
    - `checkoutPay(Order $order): Response` — `abort(401)` if
      unauthenticated, `abort(403)` if `$order->portal_user_id !== null &&
      $order->portal_user_id !== $user->id`; loads `$order->items` and
      renders `Inertia::render('Store/CheckoutPay', ['order' => [...]])`
      with `id`, `order_number`, `subtotal`, `grand_total`,
      `payment_status`, `billing_address`, `shipping_address`, and mapped
      `items` (id, name, sku, quantity, unit_price, line_total,
      location_id).
  - **`routes/web.php`**: added
    `GET /store/checkout` → `store.checkout` and
    `GET /store/checkout/{order}/pay` → `store.checkout.pay`, both with
    `['auth.web_or_admin', 'store.enabled']`, placed before the catch-all
    `/store/{slug}` route (required since `/store/checkout` is a single
    path segment and would otherwise be captured by `{slug}`).
  - **`resources/js/Pages/Store/Checkout.vue`** (new): `AuthenticatedLayout`
    page. Redirects to `route('store.cart')` on mount if
    `useCartStore().items` is empty. Form fields: contact name/email/phone
    (prefilled from the `contact` prop), company name, PO number, job
    number, notes, billing address (line1/line2/city/state/postal_code/
    country), and a "Same as billing address" checkbox that toggles a
    separate shipping address block. Order summary sidebar reuses the
    `groupedItems`-by-`location_id` pattern from `Cart.vue`, showing line
    items, location names (via the `locations` prop), and the cart total.
    On submit, POSTs to `/api/v1/store/checkout` via `axios` with a payload
    matching Phase 2's validation shape exactly (`items[]` mapped from cart
    items with `id`, `product_id`, `name`, `quantity`, `price`, `slug`,
    `location_id`; contact/company/PO/job/notes; `billing_address`;
    `shipping_same_as_billing`; `shipping_address` sent only when not same
    as billing). On success: `cartStore.clearCart()` then
    `router.visit(route('store.checkout.pay', { order: response.data.order.id }))`.
    On `422`, field errors from `error.response.data.errors` are stored in
    a local `errors` ref and displayed inline.
    **Inline-error display pattern**: copied the
    `InputLabel`/`TextInput`/`InputError` + `PrimaryButton` markup
    convention from `resources/js/Pages/Auth/Register.vue` (each field is
    `<InputLabel>` + `<TextInput v-model="...">` + `<InputError
    :message="...">`); the only deviation is that `Register.vue` uses
    Inertia's `useForm().errors` directly (since it submits via
    `form.post()`), whereas `Checkout.vue` submits via `axios.post()` to a
    JSON API endpoint, so it populates an equivalent plain `errors` ref from
    the `422` response body and reads it through a `fieldError(path)`
    helper — same visual/markup convention, different error-source wiring.
  - **`resources/js/Pages/Store/CheckoutPay.vue`** (new): `AuthenticatedLayout`
    page rendering the `order` prop from `checkoutPay()` — order number,
    line items, grand total, read-only billing/shipping addresses. If
    `order.payment_status === 'paid'`, shows a banner linking to
    `route('store.checkout.success', order.id)`. Includes a "Choose a
    Payment Method" sidebar with a placeholder `<div id="payment-methods">`
    (no payment buttons yet — Phase 4/5 will populate this).
  - **`tests/Feature/StoreCheckoutPageTest.php`** (new, 4 tests):
    - `GET /store/checkout` (authenticated user with a company) → renders
      `Store/Checkout` with `locations` and `contact` props.
    - `GET /store/checkout/{order}/pay` for the order's owner → renders
      `Store/CheckoutPay` with `order.id`, `order.grand_total === 21`, and
      1 item. The order is created directly via
      `app(StoreCheckoutService::class)->createFromCart(...)` (Phase 2's
      service) rather than over HTTP, to avoid an `actingAs()` guard-switch
      issue (see below).
    - `GET /store/checkout/{order}/pay` for a different user **in the same
      company** → `403` (the explicit `portal_user_id` check in
      `checkoutPay()`). Note: a different user in a *different* company
      would get `404` instead, because `CompanyScope` filters the `Order`
      out of the route-model-binding query entirely before the controller's
      403 check runs — this is existing `CompanyScope` behavior, not new to
      Phase 3.
    - Guests → redirected to `login` for both `/store/checkout` and
      `/store/checkout/{order}/pay`.
  - **Debugging note**: initially built `createOrderForUser()` using
    `actingAs($user, 'sanctum')->postJson('/api/v1/store/checkout', ...)`
    followed by `actingAs($user)->get(...)` for the page request. This
    caused `Auth::guard('web')->check()` to return `false` on the second
    request (redirected to `/login`) — switching `actingAs()` guards
    mid-test left the `web` guard unauthenticated for subsequent requests.
    Fixed by creating the order directly via
    `StoreCheckoutService::createFromCart()` (no HTTP round-trip), then a
    single `actingAs($user)->get(...)` for the page assertion.
  - Verification: `npm run build` succeeds;
    `php artisan test tests/Feature/StoreCheckoutPageTest.php` → 4 passed;
    `php artisan test tests/Feature/StoreTest.php
    tests/Feature/Api/StoreCheckoutTest.php
    tests/Feature/Api/StripeCheckoutTest.php
    tests/Feature/Api/StripeIntegrationFlowTest.php
    tests/Feature/StoreCheckoutPageTest.php` → 18 passed, 1 failed (the
    same pre-existing `StoreTest > store cart page is accessible to
    authenticated users` failure documented in the Phase 1 log —
    `User::factory()->create()` has no `company`, unrelated to Phase 3);
    `vendor/bin/pint --dirty` → 0 files needing changes.

  Awaiting orchestrator approval of Phase 3 before starting Phase 4.

- 2026-06-12 — Phase 4 implemented (Stripe payment step: card + Google
  Pay/Apple Pay):
  - **`app/Services/Stripe/StripeCheckoutService.php`**: reviewed
    `createSessionForOrder()` — `payment_method_types` was already absent
    from the `checkout.sessions.create()` params (confirmed via grep across
    `app/Services/Stripe` and `tests/`), so no backend change was needed.
    Stripe will offer the account's enabled payment methods (including
    Google Pay / Apple Pay wallets) automatically on the hosted Checkout
    page.
  - **`resources/js/Pages/Store/CheckoutPay.vue`**: added a "Pay with Card"
    button inside the "Choose a Payment Method" sidebar (`#payment-methods`,
    using `PrimaryButton`). On click, `payWithCard()` POSTs to
    `/api/v1/orders/{order.id}/checkout` via `axios`; on success,
    full-page-redirects via `window.location.href = response.data.checkout_url`
    to the Stripe-hosted Checkout Session. While in flight, the button is
    disabled and shows "Redirecting to Stripe…". On `422` (the
    `StripeCheckoutController`'s `ValidationException` with
    `{ errors: { order: [...] } }`), the message is shown inline via
    `InputError` (same convention as Phase 3's form errors); on any other
    error, a generic retry message is shown. The button is also disabled
    when `order.payment_status === 'paid'`. Added a small note under the
    button that Google Pay/Apple Pay appear automatically on the Stripe
    page.
  - **`tests/Feature/StoreCheckoutPageTest.php`**: no new test file —
    `test_checkout_pay_page_renders_for_order_owner` already asserts
    `order.id` and `order.grand_total` are present in the `CheckoutPay`
    Inertia props, which is exactly what the frontend needs to call
    `POST /api/v1/orders/{order}/checkout`. Per the phase doc's guidance to
    add an assertion to the existing file rather than create a new one, this
    existing coverage was judged sufficient (no additional assertion
    needed).
  - **No changes** to `StripeWebhookController`, `ProcessStripeWebhookEvent`,
    or `StripePaymentSyncService` — the existing webhook pipeline already
    marks `Payment`/`Order` paid and the customer lands on
    `store.checkout.success` (Phase 3's `CheckoutPay.vue` already links to
    this when `payment_status === 'paid'`).
  - **Deployment prerequisite (Google Pay / Apple Pay)**: to surface these
    wallet buttons on the live Stripe Checkout page in production, the
    Stripe Dashboard account must have: (1) Apple Pay — a domain
    verification file uploaded for the production domain under
    Settings → Payment methods → Apple Pay; (2) Google Pay — enabled under
    Settings → Payment methods (on by default for most US accounts). This is
    an account-configuration step outside the codebase — flagged here as a
    follow-up for whoever owns the Stripe account before go-live.
  - Verification: `npm run build` succeeds;
    `php artisan test tests/Feature/Api/StripeCheckoutTest.php
    tests/Feature/Api/StripeIntegrationFlowTest.php
    tests/Feature/Api/StripeWebhookTest.php
    tests/Feature/Filament/StripeEventResourceTest.php
    tests/Feature/Jobs/ProcessStripeWebhookEventTest.php
    tests/Feature/Services/Stripe/StripeClientFactoryTest.php
    tests/Feature/Services/Stripe/StripePaymentSyncServiceTest.php` → 27
    passed (the full Stripe-integration suite, no regressions);
    `tests/Feature/StoreCheckoutPageTest.php` → 4 passed;
    `vendor/bin/pint --dirty` → 0 files needing changes.

  Awaiting orchestrator approval of Phase 4 before starting Phase 5.

- 2026-07-04 — Discovered this log was stale: Phase 5 (PayPal) was already
  fully implemented in the codebase (all services, model/migration,
  controllers, config, frontend button, and 5 test files existed) despite
  this table saying "Not Started." Verified every file listed in
  `phase-5-paypal-integration.md` exists and matches the spec. Running the
  full PayPal test suite surfaced one genuine failing test (not a
  pre-existing/unrelated failure):
  - **Bug**: `PaypalCheckoutTest > repeated paypal checkout updates the
    existing pending payment` failed — asserted a second checkout call
    would update the pending `Payment`'s `gateway_session_id` to a new
    PayPal order id, but it stayed at the first value.
  - **Root cause (in the test, not the app)**: traced via
    `vendor/laravel/framework` source —
    `Illuminate\Http\Client\Factory::fake()` *appends* each call's stub
    callbacks to a growing collection rather than replacing it, and
    `PendingRequest::buildStubHandler()` resolves a request by taking the
    **first** matching callback (`->map->__invoke(...)->filter()->first()`).
    The test called `fakePaypal()` twice, expecting the second call's
    `$overrides` for `*/v2/checkout/orders` to take precedence — but the
    first call's stub for that same URL pattern was already registered and
    permanently wins for the rest of the test. `PaypalCheckoutService::
    upsertPendingPayment()` itself was correct all along; it faithfully
    recorded whatever PayPal (the stub) actually returned both times, which
    was the same order id both times because of this fake-ordering
    footgun.
  - **Fix**: rewrote that one test (`tests/Feature/Api/PaypalCheckoutTest.php`)
    to use `Http::fakeSequence('*/v2/checkout/orders')` — the correct tool
    for "same endpoint, different response per call" — instead of calling
    `fakePaypal()` twice. No application code changed. Confirmed the other
    `fakePaypal($overrides)` call sites (`StorePaypalReturnControllerTest`,
    `PaypalWebhookTest`) are each single first-and-only calls per test, so
    they don't hit this footgun and needed no changes.
  - **Audit finding, not fixed (flagged for a future decision)**:
    `PaypalCheckoutController::store()` and `StorePaypalReturnController::
    return()` check `$order->portal_user_id !== null && ... !== $user->id`
    without the `! $user instanceof Admin` bypass that
    `StripeCheckoutController::store()` has (added later per
    `stripe-integration`'s "Post-completion admin testing hardening" log
    entry). This means an admin can create test Stripe checkouts for any
    order but would get a 403 doing the same for PayPal. Not fixed here —
    it's an authorization-behavior change, not a test/logging issue, and
    out of Phase 6's stated scope; flagging for the orchestrator to decide
    whether to align it.
  - Verification: `tests/Feature/Api/PaypalCheckoutTest.php` → 4 passed;
    full Stripe+PayPal+Store regression
    (`PaypalCheckoutTest`, `PaypalWebhookTest`,
    `Services/Paypal/*`, `StorePaypalReturnControllerTest`,
    `StripeCheckoutTest`, `StripeIntegrationFlowTest`, `StripeWebhookTest`,
    `StoreCheckoutPageTest`, `StoreTest`, `Api/StoreCheckoutTest`) → 44
    passed, 0 failed; `vendor/bin/pint --dirty` clean.

- 2026-07-04 — Phase 6 implemented (end-to-end testing & hardening):
  - **`tests/Feature/StoreCheckoutFlowTest.php`** (new, 4 tests):
    - Full Stripe path: cart → `POST /api/v1/store/checkout` (real `Order`
      created) → `POST /api/v1/orders/{order}/checkout`
      (`FakeStripeHttpClient`) → simulated `payment_intent.succeeded`
      webhook → asserts `Order::payment_status === Paid`,
      `Order::status === Confirmed`.
    - Full PayPal path: same cart → order → `POST
      /api/v1/orders/{order}/checkout/paypal` (`Http::fake()` via
      `FakesPaypalHttp`) → simulated return-redirect
      (`GET /store/checkout/{order}/paypal/return?token=...`) → asserts
      `Order` marked paid/confirmed. Hit the same `actingAs()` guard-switch
      gotcha already documented in this file's Phase 3 entry
      (`actingAs($user, 'sanctum')` silently switches Laravel's *default*
      guard for the rest of the test) — fixed by passing `'web'` explicitly
      on the second `actingAs()` call for the return-redirect request;
      documented inline in the test with a comment pointing back to this
      log.
    - Negative paths: empty-cart checkout → `422` on `items`; an
      already-`Paid` order is rejected by **both** gateway endpoints via
      `Order::canBePaid()` → `422` on `order` for each.
  - **Logging/error-handling audit**: `StoreCheckoutController` has no
    external API call (pure local DB writes via `StoreCheckoutService`), so
    no SDK/HTTP exception-leak risk applies to it. `PaypalCheckoutController`
    and `StorePaypalReturnController` both correctly catch service-level
    `RuntimeException`s and never leak raw HTTP/SDK exceptions; the
    underlying `PaypalCheckoutService`/`PaypalCaptureService` both log via
    `Log::error` with `order_id`/`payment_id`/`paypal_order_id` before
    throwing — matches the Stripe module's established pattern exactly.
  - **Route/middleware audit**: confirmed directly against `routes/web.php`
    and `routes/api.php` — `store.checkout`, `store.checkout.pay`,
    `store.checkout.paypal.return` all have
    `['auth.web_or_admin', 'store.enabled']`; `/api/v1/store/checkout` and
    `/api/v1/orders/{order}/checkout/paypal` are inside the `auth:sanctum`
    group; `/api/v1/paypal/webhook` is public with `throttle:120,1`. All
    correct, no changes needed.
  - **Cart/localStorage migration check**: confirmed by inspection —
    `useCartStore.js` only ever reads/writes the `colorado-supply.cart`
    key; a browser with old `colorado-supply.quote` data simply never
    touches that key, so `loadInitialItems()` returns `[]` gracefully. No
    crash, no test needed (the code makes it structurally impossible to
    read the old key).
  - **Full regression run**: `php artisan test` (whole suite) shows the
    same pre-existing, unrelated failures already documented in this
    module's own Phase 1 note and `stripe-integration`'s `STATE.md`
    (`CrmAuthorizationTest`, `UserManagementTest`, `ComplianceAuditToolTest`,
    `CustomerResourceGooglePlacesTest`, a Filament/Blade component memory
    exhaustion later in the run) — none touch cart/checkout/payment code.
    No new failures introduced by Phase 5's fix or Phase 6's new test.
  - `vendor/bin/pint --dirty` clean (86 files checked, 1 auto-fix on the
    new test file's imports).
  - Status set to `Awaiting Validation` for both Phase 5 and Phase 6.
    Outstanding before the module can be marked `Complete`:
    (1) orchestrator sign-off on all 6 phases, (2) a decision on the
    PayPal admin-bypass authorization gap noted above, (3) live PayPal
    sandbox credentials were never available in this environment — all
    PayPal coverage here is `Http::fake()`-based per Phase 5's own
    documented assumption, not verified against PayPal's real sandbox.

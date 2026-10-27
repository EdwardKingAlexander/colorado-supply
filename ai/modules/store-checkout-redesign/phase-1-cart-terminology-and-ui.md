# Phase 1 — Cart Terminology & UI Redesign

## Goal

Rename "quote" → "cart" everywhere in the storefront UI (wording, route
names, store/composable names) and restyle the cart page from a "quote
summary" into a normal e-commerce cart, with its terminal action becoming
"Proceed to Checkout" (wired to Phase 2's endpoint, added in that phase —
this phase can stub the click handler with a placeholder Inertia link to
`store.checkout` that Phase 3 will create). The PDF "Generate Quote" /
"Email Quote" actions are removed from the cart page entirely (per
`00-overview.md` Assumptions — the CRM `Quote` PDF feature remains, just not
reachable from the storefront cart).

## Files to Create / Modify

- **`resources/js/Stores/useQuoteStore.js` → `resources/js/Stores/useCartStore.js`**
  (rename file + exported function `useQuoteStore` → `useCartStore`,
  `clearQuote` → `clearCart`, localStorage key
  `colorado-supply.quote` → `colorado-supply.cart` — bump the key so stale
  "quote" localStorage data doesn't silently load into the new cart with a
  different shape. Keep all other method names/shapes (`addItem`,
  `removeItem`, `incrementQuantity`, `decrementQuantity`,
  `updateItemLocation`, `itemCount`, `total`, `totalCost`).
- **`resources/js/Pages/Store/QuoteView.vue` → `resources/js/Pages/Store/Cart.vue`**:
  - Import `useCartStore` instead of `useQuoteStore`.
  - Header: "Quote Summary" / "Current Quote" → "Cart" / "Your Cart".
  - Remove the "Generate Quote" button, delivery modal (email/download PDF),
    and all related state (`generating`, `downloadMessage`, `downloadError`,
    `lastQuoteNumber`, `lastQuoteId`, `showDeliveryModal`, `emailInput`,
    `emailError`, `buildPayload`, `downloadPdf`, `generateQuote`,
    `syncQuoteMeta`, `handleDeliverySelection`, and the `axios`/
    `/api/v1/store/quote` call).
  - Replace with a single primary "Proceed to Checkout" button
    (`<Link :href="route('store.checkout')">`), disabled when
    `!hasItems`. Route `store.checkout` doesn't exist yet — Phase 3 adds it;
    for this phase, point at `store.cart` as a placeholder *only if*
    `route('store.checkout')` would throw at build time (check Ziggy's
    behavior — if Ziggy errors on unknown route names, temporarily hardcode
    `/store/checkout` as a plain string and switch to `route()` in Phase 3).
  - "Your quote is empty" → "Your cart is empty"; "Quote Total" → "Cart
    Total"; "Browse the catalog..." copy can stay.
  - Rename component-local variables referencing `quoteStore` → `cartStore`.
- **`resources/js/Components/Store/ProductRow.vue`** (2 occurrences,
  lines ~182 and ~248): `Add to Quote` → `Add to Cart`. Check whether this
  component imports `useQuoteStore` and update to `useCartStore` if so.
- **`resources/js/Pages/Store/ProductDetail.vue`** (line ~372):
  `Add to Quote` → `Add to Cart`; update `useQuoteStore` import/usage.
- **`resources/js/Components/Store/ProductList.vue`** (line ~72): table
  header `Quote` → `Cart` (this is the column showing the add-to-cart
  control/price for each row).
- **`resources/js/Pages/Store/StoreIndex.vue`**:
  - `useQuoteStore` → `useCartStore`, `quoteStore`/`quoteItemCount` →
    `cartStore`/`cartItemCount`.
  - Heading "Browse products & build your quote" → "Browse products & build
    your cart" (or similar — keep tone consistent with rest of page).
  - The link at line ~386 (`:href="route('store.quote')"`) → update to the
    renamed route `store.cart` (see route rename below). Cart item-count
    badge logic unchanged, just renamed variables.
- **`resources/js/Pages/Store/Authenticated.vue`**: this page is a static
  demo (not wired to the cart store) — only the literal label is already
  "Add to Cart" (line 72), so **no change needed**; double-check no other
  "quote" strings exist here that need updating for consistency (grep showed
  none beyond what's listed).
- **`resources/js/Pages/Store/Public.vue`**: grep for "quote"/"Add to
  Quote"-style copy and update to cart terminology if any customer-facing
  product/cart wording exists; this page is not behind auth and may be purely
  marketing — confirm during implementation and document what (if anything)
  changed.
- **`routes/web.php`**:
  - Rename route `store.quote` (`GET /store/quote`) → `store.cart`
    (`GET /store/cart`), still pointing at `StoreController::class` — rename
    the controller method `quote()` → `cart()` to match (update
    `app/Http/Controllers/StoreController.php`).
  - Keep `auth.web_or_admin`/`store.enabled` middleware unchanged.
- **`app/Http/Controllers/StoreController.php`**: rename `quote()` method →
  `cart()`, update its returned Inertia component from
  `Store/QuoteView` → `Store/Cart` (and any other references to the old
  component name).

## Tests to Write

- Update/rename any existing Pest test that renders `store.quote` /
  `Store/QuoteView` (search `tests/` for `store.quote`, `QuoteView`,
  `useQuoteStore` references) to the new route/component names.
- New lightweight Inertia test: `GET /store/cart` (as an authenticated portal
  user) returns the `Store/Cart` component (`assertInertia`).
- No JS unit test framework is configured for this repo (none found under
  `resources/js`) — manual verification only for the Vue changes, but run
  `npm run build` to confirm no broken imports/compile errors from the
  renamed composable/component.

## Success Criteria

- No occurrences of "Add to Quote", "Generate Quote", "Quote Summary",
  "Current Quote", `useQuoteStore`, `colorado-supply.quote`, or `store.quote`
  remain in `resources/js/` or `routes/web.php` (grep clean), except where
  they refer to the unrelated CRM `Quote`/`QuoteItem` models (which are out
  of scope and untouched).
- `npm run build` succeeds.
- `php artisan test` passes (including the renamed/added cart route test).
- `vendor/bin/pint --dirty` clean.
- `STATE.md` updated to `Awaiting Validation` with a log entry listing every
  file changed and the exact renames performed.

STOP. Await phase approval.

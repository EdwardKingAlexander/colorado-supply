# Phase 5 - Store Discovery, Product, and Cart

## Goal

Make catalog discovery, filtering, product inspection, quantity adjustment, and
cart review efficient on phones while preserving the current store/cart state
and APIs.

## Files to Create/Modify

- `resources/js/Pages/Store/StoreIndex.vue`
- `resources/js/Pages/Store/ProductDetail.vue`
- `resources/js/Pages/Store/Cart.vue`
- `resources/js/Components/Store/SearchBar.vue`
- `CategorySidebar.vue`
- `ParametricFilters.vue`
- `ProductList.vue`
- `ProductRow.vue`
- Optional new focused components such as a mobile filter dialog or cart item
  row when they remove duplicated responsive markup.
- `tests/Browser/mobile-store.spec.ts`
- Existing store/cart feature and API tests.

## Implementation Requirements

- Do not begin until the current store/checkout redesign implementation is
  validated or explicitly adopted.
- Catalog header and cart action stack cleanly; search remains reachable without
  a large sticky block consuming most of a short phone viewport.
- Search field, clear button, breadcrumb targets, category rows, filter trigger,
  chips, pagination, and cart links meet 48 px target sizing.
- On phones, open parametric filters in an accessible modal/bottom sheet with
  apply/clear actions, selected-count feedback, focus management, and scroll
  lock. Keep the existing desktop sidebar at `lg`.
- Product results use a mobile list/card layout with product name, SKU, key
  attributes, price/stock, location, quantity, and Add to Cart action in a
  stable scan order. Do not force users through a desktop table.
- Product detail uses 16 px content, 16 px panel padding, one-column specs, and a
  safe-area-aware sticky Add to Cart area only if it does not obscure focused
  elements or the keyboard.
- Cart quantity decrement/increment/remove actions use 48 px targets; item
  totals and labels remain visible at 320 px. Checkout becomes the clear primary
  action, preferably in a bottom action area with matching content padding.
- Preserve Pinia/localStorage behavior, route names, API payloads, and all cart
  calculations.

## Tests

- Mock catalog/filter/product endpoints and test categories, search, filter
  open/apply/clear, result loading/empty/error, pagination, add-to-cart,
  quantity, location, remove, and cart total flows.
- Assert mobile and desktop variants expose the same values/actions and do not
  create duplicate accessible controls at one breakpoint.
- Screenshot/overflow/target checks at 320/360/390/430 px and landscape.
- Verify sticky content does not cover the final item or focused control.
- Existing `StoreTest`, store API, checkout-page, build, SSR, and Pint checks
  pass.

## Success Criteria

- A user can search, filter, inspect, add, adjust, and begin checkout entirely
  with 48 px targets and no page-level horizontal scrolling.
- Dense desktop catalog behavior remains available at larger breakpoints.
- No cart, pricing, inventory, or API behavior changes.

**STOP. Await phase approval before implementation.**

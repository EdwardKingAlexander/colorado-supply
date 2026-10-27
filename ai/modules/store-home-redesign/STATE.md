# Store Home Redesign — State

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Approved | 2026-10-05 | User directly requested the redesign (frontend-design skill) |
| 1 | Store shell, catalog index, product view | Awaiting Validation | 2026-10-05 | Built, tested, desktop verified in browser |

## Log

- 2026-10-05 — User: store home "looks like just another link on the Users
  Dashboard ... keep the McMaster Carr behavior". Plan written; building
  under the user's standing blanket approval for driving phases.
- 2026-10-05 — Phase 1 implemented:
  - `Layouts/StoreLayout.vue` (sticky header: logo, catalog search with `/`
    shortcut, Dashboard, account menu, cart with count). Store, Cart,
    ProductDetail, Checkout, CheckoutPay use it.
  - `StoreIndex.vue`: catalog index (departments + subcategories, leader-dot
    counts), department view, product view (sibling-category rail + spec
    filters, filter pills, mobile filter drawer); URL state via Inertia
    `router.push/replace` (`?department=`, `?category=`, `?q=`).
  - `ProductList` / `ProductRow` / `ParametricFilters` restyled to `st-*`
    tokens; picsum fallback removed (neutral glyph instead).
  - API: `CategoryResource.products_count`; counts + visibility now use
    active products only. Test added; existing tests pinned `is_active`.
  - Tokens in `resources/css/app.css` (`.store-shell`), `st` colours +
    `catalog` font in `tailwind.config.js`; Archivo loaded in `app.blade.php`.
  - Full suite: 904 passed / 5 skipped / 0 failed. Pint clean. Build OK.
  - Browser (desktop): index, drill-down, filters, back button, search,
    cart and product detail verified. Mobile not screenshot-verified
    (Chrome window was backgrounded; site blocks iframes).
  - Known data issue: seeded products store picsum.photos URLs in `image`.
- 2026-10-10 — Committed by the product-detail-page session at the owner's request (owner chose to commit the working state). Still awaiting owner validation.

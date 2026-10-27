# Product Detail Page — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-10 | Approved by owner |
| 1 | Data foundation and admin entry | Complete | 2026-10-10 | Owner proceeded to phase 2. 14 new tests passing; full suite 980 passed / 5 skipped / 0 failed |
| 2 | Server-side page data, pricing and URL fix | Complete | 2026-10-10 | Owner proceeded to phase 3; committed 612c7d8b. 24 new tests; full suite 1004 passed / 5 skipped / 0 failed; browser-checked |
| 3 | Buy box: quantity, tiers, cart and Request a Quote | Complete | 2026-10-10 | Owner approved; committed ea77ce8e. Adds server-side checkout pricing; sticky bar not browser-verified |
| 4 | Content: facts, compliance, specs, documents, gallery, stock, related | Complete | 2026-10-10 | Owner approved; committed f3cfedde. Browser-checked full + bare product; full suite 1020 passed |
| 5 | Testing, accessibility, visual QA and hardening | Complete | 2026-10-10 | Owner approved; committed b10b1926. 14 Playwright + full suite 1022 passed; live quantity pricing added |

## Log

- **2026-10-10** — Reviewed current page (`ProductDetail.vue`,
  `StoreController::show`, `Api\V1\ProductController::show`,
  `V1\ProductResource`). Found: slug-only lookup despite per-vendor slug
  uniqueness, unit read from `meta.unit`, Add to Cart on unpriced items, no
  quantity/MOQ, government columns not exposed, `company_products.price`
  ignored, location visibility not applied, client-side fetch. Researched B2B
  product-page guidance (Baymard B2B components/machinery research and 2026
  benchmark). Owner decisions: full scope; every field/section optional and
  hidden when absent; Request a Quote creates a draft `Quote`; GSA/contract
  prices only for government-buyer companies and admins; tiers and gallery in
  scope now. Plan written (5 phases). Awaiting plan approval.
- **2026-10-10** — Owner approved the plan. Phase 1 started directly (owner
  allows chaining once questions are settled). Scope check found the product
  admin form lacks unit of measure, MOQ, NSN/CAGE, compliance flags,
  documents, GSA/contract prices and dimensions (import-only today); added
  sub-phase 1.4 to expose them as optional fields. Also found
  `public/storage` symlink missing locally, so Filament `public`-disk uploads
  are not web-visible; flagged to owner (needs `php artisan storage:link`,
  also on Forge).
- **2026-10-10 (phase 1 validation)** — Implemented all four sub-phases.
  - 1.1: migration `2026_10_10_100000_create_product_page_tables`
    (`product_price_tiers`, `product_images`, `product_relations`,
    `companies.is_government_buyer`); models `ProductPriceTier`,
    `ProductImage`, `ProductRelation` (rejects self-links and unknown types);
    factories for tiers and images; `Product::priceTiers()`, `images()`,
    `productRelations()`, `substitutes()`, `accessories()`,
    `relatedProducts()`. Named `productRelations` (not `relations`) to avoid
    Eloquent's own `$relations`. Migrated, rolled back and re-migrated on the
    dev DB.
  - 1.2: `ProductResource` form gained collapsed "Quantity pricing" and
    "Gallery images" (reorderable, `public` disk `products/gallery`)
    repeaters; new `RelatedProductsRelationManager` (type + searchable
    product picker, excludes the product itself, blocks duplicate links).
  - 1.3: Company form "Government buyer" toggle, list column and filter.
  - 1.4: collapsed Ordering, Identifiers, Government, Compliance and
    Specifications & documents sections. Blank unit of measure saves `EA`,
    blank minimum order saves 1; NSN accepts dashes, stores 13 digits.
  - Tests: `tests/Feature/Models/ProductPageRelationsTest.php` (7) and
    `tests/Feature/Filament/ProductPageDataEntryTest.php` (7) pass. Full
    suite `php artisan test --parallel`: 980 passed, 5 skipped, 0 failed.
    `pint --dirty` clean.
  - Not verified in a browser. Uploaded images will not display until
    `php artisan storage:link` is run (locally and on Forge).
- **2026-10-10** — Owner directed: run `storage:link` and start phase 2.
  Phase 1 marked Complete. `php artisan storage:link` run locally; verified a
  file in `storage/app/public` is served at `/storage/...` over https. Forge
  deploy still needs `storage:link` once (not verified here).
- **2026-10-10 (phase 2 validation)** — Implemented all four sub-phases.
  - 2.1: `GET /store/product/{product}/{slug?}` (`store.product`); wrong or
    missing slug → 301 to canonical. Legacy `/store/{slug}` → 301 when exactly
    one active product matches, else 404. `ProductRow.vue` links use the
    canonical route (only `store.show` caller found).
  - 2.2: `App\Services\Store\ProductPriceResolver` — company price > highest
    tier reached > base; tier-only products are `priced` with no unit price
    below the first break; GSA/contract keys only for admins and
    government-buyer companies, and only when set.
  - 2.3: `App\Services\Store\ProductPageData` — single payload, recursively
    drops nulls/blank strings/empty arrays (false and 0 kept). Sections:
    vendor, breadcrumbs (cycle/depth-guarded), unit, MOQ, pricing,
    identifiers, government (standard type omitted when Commercial),
    classification, compliance + warnings (true flags only), specs (general,
    dimensions, attributes, details), documents, images (primary then
    gallery), stock, related (active only), `inactive` for admin preview.
  - 2.4: `StoreController::product` renders `Store/ProductDetail` with the
    payload (SSR, no client fetch). Page adapted to the new payload; existing
    layout kept for phases 3–4.
  - **Deviation — location visibility:** the plan said 404 for products hidden
    at the buyer's location. Users have no own location (only companies do),
    and the catalog API does not filter by location visibility, so a 404 would
    dead-end catalog clicks. Instead the page follows the catalog's rules
    (company scope + active) and hidden locations are left out of the stock
    list. Revisit if the catalog gains location filtering.
  - **Pulled forward from phases 3–4:** removed the "No description" / "No
    technical specifications" placeholders, and Add to Cart only renders when
    there is a unit price (owner's "everything optional" rule).
  - Viewer resolution prefers the web guard, then admin (matches the rest of
    `StoreController`).
  - Tests: `ProductPriceResolverTest` (9), `ProductPageDataTest` (7),
    `ProductPageRouteTest` (8). Full suite `--parallel`: 1004 passed,
    5 skipped, 0 failed. Pint clean. `npm run build` + `ssr-build` succeed.
  - Browser (admin session): legacy URL redirected to
    `/store/product/2/...`, page rendered with no `/api/v1/store/products`
    request, no console errors; catalog search links use the canonical URL.
- **2026-10-10** — Owner: "commit and start phase 3", plus three explicit
  asks: a real quantity selector on the product page, quantities above stock
  allowed (they go to the backorder screen), and cart item names linking back
  to the product. Committed in three commits after owner chose to include the
  two uncommitted modules this work sat on: `652d5d1e`
  (store-dashboard-switch), `eeffe9b6` (store-home-redesign; ProductDetail /
  ProductRow staged at their pre-phase-2 state), `612c7d8b` (this module,
  phases 1–2). Phase 2 marked Complete.
- **2026-10-10 (phase 3 validation)** — Implemented all four sub-phases.
  - **Added scope — server-side checkout pricing:** `StoreCheckoutService`
    trusted the cart's browser price (anyone could edit localStorage and pay
    less, and tiers/company prices could not apply). Every line is now priced
    with `ProductPriceResolver` for the buyer and quantity; the browser price
    is ignored (`items.*.price` now optional). Rejects with field errors:
    product not in the buyer's company catalog or inactive, quantity below
    minimum order, unpriced ("needs a quote"). No stock cap: above-stock
    quantities are accepted for backorder. Updated 7 existing checkout tests
    (store-checkout-redesign's) to use catalog products priced at the values
    they assert, attached to the buyer's company.
  - 3.1: `Components/Store/ProductBuyBox.vue` — quantity stepper + typed input
    starting at the MOQ (no max), quantity price table with the current tier
    highlighted, line total, backorder notice ("812 will be backordered"),
    tier-only notice. Header price follows the chosen quantity.
    `Support/pricing.js` mirrors the resolver for display.
  - 3.2: cart store keeps sku, canonical url, unit, MOQ and display pricing
    per line; quantity changes re-derive the tier price; `setQuantity` added.
    Cart page: item name links to the product (old lines rebuild the URL from
    id + slug), shows the real SKU (was showing the slug), typed quantity
    input, MOQ floor. Catalog rows: start at MOQ, 9,999 cap removed, unpriced
    items show "Request quote" (links to the product) instead of adding $0.
    Catalog API exposes `minimum_order_quantity` and `unit_of_measure`.
  - 3.3: `POST /store/product/{product}/quote-request`
    (`store.product.quote`, throttle 10/min) →
    `ProductQuoteRequestService` creates a `draft` Quote + item with
    server-resolved price (0 when unpriced), company and portal user set;
    `sales_rep_id` follows the existing web-quote convention.
    `RequestQuoteModal.vue` (shared `Modal`) with quantity, note, errors and a
    confirmation showing the quote number.
  - 3.4: mobile sticky bar (below `lg`) with price and Buy / Request a Quote
    once the buy box scrolls out of view.
  - Tests: `StoreCheckoutPricingTest` (8), `ProductQuoteRequestTest` (7).
    Full suite `--parallel`: 1019 passed, 5 skipped, 0 failed. Pint clean on
    changed files (an over-broad pint run reformatted 9 unrelated files; each
    was verified HEAD+pint-only and restored). Builds OK.
  - Browser (admin, desktop, on SAMPLE-0484 with a temporary 25+ tier):
    tier switch $58.55 → $52.70 at 30; 2,000 > stock 1,188 showed "812 will
    be backordered" and "Added 2,000 to cart · View cart"; cart links to all
    four products, quantity 10 re-priced to $58.55; quote modal created draft
    `Q-20261011-FWFX`. Test quote force-deleted, temporary tier removed,
    owner's cart restored.
  - **Not verified:** sticky bar — the automation window would not go below
    ~1080px and the tab was backgrounded (IntersectionObserver never fires
    while hidden). Needs a real phone-width check.
  - Known gaps / follow-ups: catalog rows still show the base price (not the
    company price or tiers) until the item is in the cart; cart prices are
    display-only and checkout's server price wins.
- **2026-10-10** — Owner: "Good work. Move to the next phase after committing." Phase 3 committed (`ea77ce8e`) and marked Complete; phase 4 started.
- **2026-10-10 (phase 4 validation)** — Rebuilt the product page body; every
  section renders only when its data exists.
  - Layout: breadcrumbs → (inactive notice for admins) → vendor + title →
    gallery | purchase column (price with list/company/GSA/contract, stock and
    per-location availability, compliance + warning badges, standard type /
    mil-spec, buy box, key facts) → description (collapses on mobile when
    long) → specifications → related products.
  - New components: `ProductGallery` (thumbnails, zoom dialog, arrow keys,
    Escape, swipe, lazy thumbnails, fixed aspect ratio), `ProductKeyFacts`
    (SKU/MPN/part number/NSN formatted/CAGE with copy buttons, unit, MOQ),
    `ProductSpecs` (General, Dimensions with mm→in and g→oz/lb,
    Specifications, Classification + GTIN/UPC/EAN, additional specs text,
    Datasheet/SDS links in new tabs), `RelatedProducts` (Substitutes,
    Accessories, Related cards). `Support/clipboard.js` copy with
    execCommand fallback.
  - Payload: breadcrumbs now include the category `slug` (catalog URLs are
    slug-based; links go to `?department=` for the top level, `?category=`
    below).
  - Availability never shows a bare 0: in stock → "N EA available"; out of
    stock → "Backorder ships in N days" or "Contact us for availability";
    unpriced → "Price on request".
  - Tests: bare-product page test in `ProductPageRouteTest` (props for every
    optional section absent). Full suite `--parallel`: 1020 passed,
    5 skipped, 0 failed. Pint clean on changed files. Builds OK.
  - Browser (admin, desktop): SAMPLE-0484 given temporary NSN/CAGE/part
    number, Berry/Made in USA/Hazmat, MS standard, datasheet + SDS, 2 gallery
    images and a substitute — every section rendered; thumbnail switch, zoom
    open, ArrowRight, close, breadcrumb hrefs, document links and substitute
    link verified. Temporary bare product (name/SKU/vendor) showed only
    title, "Price on request", Request a Quote, SKU and unit — no empty
    headings or placeholder text. All temporary data restored/deleted.
  - Not verified: phone width (automation window cannot go below ~1080px and
    runs backgrounded) and swipe gestures. Phase 5 covers phone QA.
- **2026-10-10** — Owner: "commit and move forward". Phase 4 committed (`f3cfedde`) and marked Complete; phase 5 started.
- **2026-10-10 (phase 5 validation)** —
  - **Owner request mid-phase:** quantity changes must update the price
    instantly, without leaving the field. Buy box and cart quantity inputs now
    update on `input` (any whole number ≥ 1) and enforce the minimum order on
    `change`/add; the "Minimum order" hint turns amber while below it. Cart
    store gained `previewQuantity`.
  - **Browser QA (new `tests/Browser/product-page.spec.ts`, 14 tests, all
    pass):** a fixture (`tests/Browser/support/product-page-fixture.php`)
    creates a throwaway buyer (random password per run, never stored) and
    token-scoped `E2E-PDP-{TOKEN}-*` products, and removes them afterwards
    (verified 0 left). At 320/390/430/1280px: no horizontal scroll, every
    content button ≥ 44px, 16px quantity input, axe WCAG 2.1 A/AA clean on
    full and bare products and the quote modal. At 390px with touch: price
    updates while typing (verified before blur), MOQ floor, backorder notice,
    keyboard add-to-cart, cart link back to the product, sticky bar
    hide/show/return-and-focus, gallery thumbnail/swipe/zoom/arrow/Escape,
    keyboard quote request, NSN copy button.
  - **Fixes found by QA:** copy buttons enlarged to 44px; zoom viewer closes
    on Escape itself (Chrome can ignore Escape on a modal dialog without user
    activation); attributes group renamed "Technical details" (was a second
    "Specifications" heading); related-product cards no longer run one
    company-price query per card (`ProductPriceResolver::companyPricesFor`,
    passed into `resolve()`; resolver kept stateless because Laravel reuses
    controller instances — an instance cache caused stale prices across
    requests, caught by `StoreCheckoutPricingTest`).
  - **Feature tests added:** government-buyer GSA/contract visibility on the
    page; query count is identical for 1 vs 5 related products (fails without
    the fix: 19 vs 15).
  - Full suite `--parallel`: 1022 passed, 5 skipped, 0 failed. Pint clean on
    changed files. Builds OK.
  - **Follow-ups (not in this module):**
    1. `/api/v1/store/products/{slug}` is still public and slug-only.
    2. `POST /api/v1/store/quote` still trusts client-sent prices.
    3. Catalog rows show the base price, not the buyer's company price or
       tiers (product page, cart and checkout are correct).
    4. Product CSV import does not cover tiers, gallery images or relations.
    5. Location visibility is not applied by the catalog API (see phase 2
       deviation).
    6. `auth.web_or_admin` redirects JSON requests to login instead of 401.

- 2026-10-10 — Owner approved phase 5; committed b10b1926. All phases Complete; module renamed to `product-detail-page-complete` and code comments updated to the new path.

---
plan: product-detail-page
phase: 2
title: Server-side page data, pricing and URL fix
status: completed
last_updated: 2026-10-10
depends_on: [phase-1]
checkpoint: wiring
---

# Phase 2 — Server-Side Page Data, Pricing and URL Fix

## Goal

Render the product page from a single server-built payload (via Inertia/SSR)
that resolves the correct product, the correct price for this viewer, and every
optional section — omitting anything the product lacks.

## Success criteria

- Two vendors sharing a slug can never show the wrong product.
- The payload contains no empty keys: absent data → key not present.
- Prices: company price > tier price for the quantity > base price; GSA and
  contract prices present only for government-buyer companies and admins.
- Portal users cannot load products outside their company or hidden at their
  location (404).
- No client-side fetch on page load.

## Sub-phases

### 2.1 — Canonical URL — `checkpoint: url`

- New route `GET /store/product/{product}/{slug?}` (`store.product`), same
  middleware as `store.show`; wrong/missing slug → 301 to canonical.
- Legacy `GET /store/{slug}`: 301 to canonical when exactly one visible
  product has the slug; otherwise 404.
- Update links in `ProductList.vue` / `ProductRow.vue` (and anywhere else
  `store.show` is used) to the canonical route.

**Done when:** route tests cover canonical, slug-correction redirect, legacy
unique redirect, legacy ambiguous 404.

### 2.2 — Price resolver — `checkpoint: pricing`

- `app/Services/Store/ProductPriceResolver.php`:
  `forViewer(Product, viewer, int $qty = 1)` returns base, company, tier and
  effective unit price plus the tier table, and GSA/contract only when allowed.
- Unpriced product (no base, company or tier price) → `priced: false`.

**Done when:** unit tests cover every precedence combination and the
government gate (portal non-gov, portal gov, admin).

### 2.3 — Page payload — `checkpoint: payload`

- `app/Services/Store/ProductPageData.php` builds: identity (name, sku, mpn,
  part_number, nsn, cage_code, mil_spec_number, standard_type, gtin/upc/ean,
  unspsc, psc_fsc, naics_code), vendor, breadcrumbs (category ancestors),
  pricing (2.2), unit_of_measure, minimum_order_quantity, stock (total +
  per-location on_hand for locations the viewer can see, lead time),
  compliance flags (only `true` ones), hazmat/export-controlled, specs
  (attributes + material, finish, dimensions with mm→in and g→lb/oz),
  documents (datasheet, SDS), images (primary + gallery), related groups
  (substitutes, accessories, related — only active, visible ones).
- Helper drops null/empty/false-only values so absent data is never sent.

**Done when:** a "bare" product (name, sku, vendor) yields only those keys; a
"full" product yields every section.

### 2.4 — Controller + page wiring — `checkpoint: wiring`

- `StoreController::show` renders `Store/ProductDetail` with the payload; the
  Vue page reads the prop instead of fetching. Existing visual layout kept for
  now (phases 3–4 rebuild it). `unit_of_measure` replaces `meta.unit`.
- 404 for out-of-company or location-hidden products for portal users.

**Done when:** page renders via SSR with no `/api/v1/store/products` request.

## Tests

- `tests/Feature/Store/ProductPageRouteTest.php`
- `tests/Feature/Services/Store/ProductPriceResolverTest.php`
- `tests/Feature/Services/Store/ProductPageDataTest.php` (bare vs full, scope,
  location visibility, government gate)

## Risks

- `CompanyScope` only applies when `Auth::user()` is a web user with a
  `company_id`; admins on the `admin` guard bypass it. Test both explicitly.
- Category ancestor walk: guard against cycles and cap depth.

**STOP. Await phase approval.**

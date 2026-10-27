# Product Detail Page — Overview

## Problem statement

The store product page (`resources/js/Pages/Store/ProductDetail.vue`, route
`store.show`) is a basic catalog view. It is missing most of what an
industrial/government B2B buyer needs to decide and buy, and it has defects:

- **Wrong-product risk:** `GET /api/v1/store/products/{slug}` looks up by
  `slug` alone, but slugs are only unique per vendor
  (`products_vendor_id_slug_unique`). Two vendors with the same slug → an
  arbitrary product is shown.
- **Wrong unit:** the unit is read from `meta.unit`, not the real
  `unit_of_measure` column, so it shows `EA` for almost everything.
- **Unpriced items can be bought:** "Call for pricing" products still show Add
  to Cart.
- **Quantity of 1 only:** no quantity input; `minimum_order_quantity` ignored.
- **Government data hidden:** NSN, CAGE, standard type, mil-spec number,
  compliance flags, datasheet/SDS URLs and GSA/contract prices never reach the
  page.
- **Pricing ignores the company:** `company_products.price` (per-company
  pricing) exists but the page shows only the base price.
- **No location visibility:** the catalog hides products not `visible` at the
  buyer's location; the product page does not.
- **Client-side fetch:** data loads after mount (skeleton flash) even though
  the app has Inertia SSR.

## Goal

A product page that serves both the fast re-order buyer and the evaluating
engineer/contracting officer: correct product, correct price for this buyer,
quantity-aware buying, a real Request a Quote path, full specs and compliance
data, and discovery of related parts and substitutes.

## Owner decisions (2026-10-10)

1. **Scope:** all suggestions — fixes, B2B buying, specs/trust, discovery,
   technical.
2. **Everything optional:** every field and section renders only when the
   product has the data. No empty headings, placeholder rows, "N/A" values or
   "No specifications available" boxes. A product with only name + SKU + vendor
   must still render a clean page.
3. **Request a Quote:** creates a real `Quote` record (status `draft`) for the
   logged-in user with this product and quantity; sales prices it in Filament.
4. **GSA / contract price:** visible only to users of companies flagged as
   government buyers (new `companies.is_government_buyer` flag), and to admins.
5. **Tiered pricing and image gallery:** in scope now (new tables), optional per
   product.

## Constraints

- Store routes stay behind `auth.web_or_admin` + `store.enabled`.
- Respect `CompanyScope`: a portal user only sees products in their company's
  `company_products`. Admins bypass it.
- Server is the source of truth for price. The new quote endpoint never trusts
  a client-sent price (the existing public `POST /api/v1/store/quote` does —
  untouched here, noted as a risk).
- Mobile-first: works at 390px; touch targets ≥ 44px (matches
  `mobile-ui-reformat-complete`).
- Reuse existing design tokens (`st-*`) and `StoreLayout`.
- Filament v4 conventions for all admin data entry.

## Architecture (textual)

```
GET /store/product/{product}/{slug?}    (new canonical URL, id-based)
GET /store/{slug}                        (legacy; 301 to canonical when the slug
                                          is unique, 404 otherwise)
  -> StoreController::show(Product)
       -> ProductPageData::for(Product, viewer, location)   (new service)
            - visibility: CompanyScope + location `visible`
            - pricing:    PriceResolver (company price > tier > base;
                          GSA/contract only for government buyers/admins)
            - stock:      per-location on_hand from location_products
            - breadcrumbs: category ancestor chain
            - compliance, identifiers, documents, specs, images, related
            - omits every key whose value is empty
       -> Inertia::render('Store/ProductDetail', ['product' => [...]])  (SSR)

Buy box (Vue)
  quantity stepper (MOQ + increments) -> live unit price from tiers
  -> Add to Cart     useCartStore.addItem(product, qty)
  -> Request a Quote POST /store/product/{product}/quote-request (web, auth)
       -> ProductQuoteRequestService -> Quote(status=draft, portal_user_id)
          + QuoteItem(server-resolved price or 0 when unpriced)
```

## Data model changes

| Change | Purpose |
|---|---|
| `product_price_tiers` (product_id, min_quantity, unit_price) | Quantity breaks |
| `product_images` (product_id, path, alt, sort_order) | Gallery; existing `products.image` stays as the primary/fallback image |
| `product_relations` (product_id, related_product_id, type: `substitute` / `accessory` / `related`) | Substitutes and accessories |
| `companies.is_government_buyer` boolean, default false | Gates GSA/contract prices |

No changes to existing product columns.

## Phases

| # | Phase | File | Depends on |
|---|---|---|---|
| 1 | Data foundation and admin entry | `phase-1-data-foundation.md` | — |
| 2 | Server-side page data, pricing and URL fix | `phase-2-page-data-and-pricing.md` | 1 |
| 3 | Buy box: quantity, tiers, cart and Request a Quote | `phase-3-buy-box-and-quote.md` | 2 |
| 4 | Content: facts, compliance, specs, documents, gallery, stock, related | `phase-4-page-content.md` | 2 |
| 5 | Testing, accessibility, visual QA and hardening | `phase-5-testing-hardening.md` | 3, 4 |

## Risks

- **URL change:** existing links use `/store/{slug}`. Mitigated by the legacy
  redirect; ambiguous legacy slugs 404 rather than guess.
- **Company scope surprises:** admins and portal users see different product
  sets; tests must cover both guards.
- **Public API unchanged:** `/api/v1/store/products/{slug}` remains public and
  slug-only. Out of scope; flagged for a follow-up (it is listed as public in
  `CLAUDE.md`).
- **Existing quote endpoint trusts client prices:** out of scope; flagged.
- **Test baseline:** full suite was green on 2026-10-10 (980 passed, 5
  skipped); any failure is a regression.

## Out of scope

- Product CSV import of tiers/images/relations (can follow later).
- Reviews/ratings, product comparison table, saved lists.
- Changes to the catalog list page beyond linking to the new URL.

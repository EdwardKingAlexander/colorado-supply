---
plan: product-detail-page
phase: 1
title: Data foundation and admin entry
status: completed
last_updated: 2026-10-10
depends_on: []
checkpoint: product-fields
---

# Phase 1 — Data Foundation and Admin Entry

## Goal

Add the optional data the new page needs (price tiers, gallery images, product
relations, government-buyer flag) and let admins enter it in Filament. No
storefront changes.

## Success criteria

- Migrations run up and down cleanly on SQLite.
- Admins can add/edit/remove tiers, images and relations on a product, and
  toggle a company's government-buyer flag.
- A product with none of this data behaves exactly as before.

## Sub-phases

### 1.1 — Migrations and models — `checkpoint: migrations`

- `product_price_tiers`: `product_id` (FK cascade), `min_quantity` unsigned int,
  `unit_price` decimal(12,2), timestamps; unique `(product_id, min_quantity)`.
- `product_images`: `product_id` (FK cascade), `path`, `alt` nullable,
  `sort_order` int default 0, timestamps.
- `product_relations`: `product_id`, `related_product_id` (both FK cascade),
  `type` string (`substitute`|`accessory`|`related`), timestamps; unique
  `(product_id, related_product_id, type)`; disallow self-relation in model.
- `companies.is_government_buyer` boolean default false.
- Models `ProductPriceTier`, `ProductImage`, `ProductRelation` with factories;
  `Product::priceTiers()`, `images()` (ordered), `relations()`,
  `substitutes()`, `accessories()`, `relatedProducts()`.
- Check names don't already exist (incl. untracked files) before creating.

**Done when:** migrations + rollback succeed; relationship tests pass.

### 1.2 — Filament product entry — `checkpoint: filament-product`

- `ProductResource` form: "Quantity pricing" repeater (min qty, unit price),
  "Images" repeater (FileUpload to `public` disk, alt text, reorderable),
  all collapsed and optional.
- Relation manager "Related products" with type select and a searchable
  product picker (excludes the product itself).

**Done when:** create/edit/remove for each works in Livewire tests.

### 1.3 — Company flag — `checkpoint: company-flag`

- Toggle "Government buyer (sees GSA/contract pricing)" on the Company form;
  column/filter on the list.

**Done when:** toggling persists; test covers it.

### 1.4 — Existing product fields in the admin form — `checkpoint: product-fields`

Added during scope check: these columns exist but can only be set by CSV
import today. Add collapsed, optional sections to the `ProductResource` form:

- **Ordering:** unit_of_measure, minimum_order_quantity, reorder_point,
  lead_time_days.
- **Identifiers:** mpn, part_number, gtin, upc, ean.
- **Government:** standard_type (select), mil_spec_number, nsn (13 digits,
  dashes allowed), cage_code, naics_code, unspsc, psc_fsc, gsa_price,
  contract_price.
- **Compliance:** berry_compliant, taa_compliant, made_in_usa, gsa_approved,
  qpl_listed, is_hazmat, export_controlled, country_of_origin.
- **Specs and documents:** material, finish, specifications,
  search_keywords, datasheet_url, msds_url, weight/dimensions.

**Done when:** saving a product round-trips every field; leaving them blank
still saves.

## Tests

- `tests/Feature/Models/ProductPageRelationsTest.php` — relations, ordering,
  self-relation rejected, cascade on product delete.
- `tests/Feature/Filament/ProductPageDataEntryTest.php` — repeaters and
  relation manager.
- Company flag test in the existing Company resource test file (or new).

## Risks

- Two Filament admin panel providers exist (`AdminPanelProvider` is the live
  one per `store-toggle-profile-menu-complete`); register only there.
- `ProductResource.php` is large; keep new form sections in a separate schema
  class if it grows further.

## Out of scope

Storefront changes; importer support for the new data.

**STOP. Await phase approval.**

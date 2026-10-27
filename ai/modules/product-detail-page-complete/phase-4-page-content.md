---
plan: product-detail-page
phase: 4
title: "Content: facts, compliance, specs, documents, gallery, stock, related"
status: completed
last_updated: 2026-10-10
depends_on: [phase-2]
checkpoint: related
---

# Phase 4 — Page Content

## Goal

Rebuild the page body from the phase-2 payload so evaluators find what they
need fast, with every section optional.

## Success criteria

- Every section below renders only when its data exists; no empty headings or
  placeholder text anywhere.
- A bare product (name, SKU, vendor) renders a clean, intentional page.
- Layout matches existing `st-*` tokens and works from 390px to desktop.

## Sub-phases

### 4.1 — Header, breadcrumbs, key facts — `checkpoint: header`

- Breadcrumbs (Store › Parent › Category) replacing "Back to Catalog";
  `BreadcrumbList` semantics.
- Key-facts strip: SKU, MPN, part number, NSN (formatted `5305-00-269-3238`),
  CAGE, unit, minimum order — each with a copy button (with clipboard
  fallback, per the NSN lookup work).

### 4.2 — Compliance badges — `checkpoint: compliance`

- Badges for true flags only: Berry, TAA, Made in USA, GSA, QPL; warning
  styling for Hazmat and Export Controlled, with a short tooltip/explanation.
- Standard type + mil-spec number shown when not `Commercial`.

### 4.3 — Specifications and documents — `checkpoint: specs`

- One specs table grouped as: General (material, finish, country of origin),
  Dimensions (mm and in; g and lb/oz), Attributes, Classification (UNSPSC,
  PSC/FSC, NAICS, GTIN/UPC/EAN). Groups with no rows are omitted.
- Documents list (Datasheet, SDS) as direct links, opening in a new tab, with
  file-type labels — no form gating.
- Description with preserved line breaks; collapses on mobile when long.

### 4.4 — Gallery — `checkpoint: gallery`

- Primary image + thumbnails; click/tap opens a zoom view; keyboard and swipe
  navigation; lazy-load non-primary images; fixed aspect ratio (no layout
  shift). Single image → no thumbnails; no images → current neutral glyph.

### 4.5 — Stock and availability — `checkpoint: stock`

- "In stock at {your location}: 42" first, then other locations the viewer can
  see; out of stock → "Ships in N days" when lead time exists, otherwise
  "Contact us for availability". Never show a raw stock number of 0 as a badge.

### 4.6 — Related products — `checkpoint: related`

- Sections for Substitutes, Accessories, Related (each only if non-empty):
  compact cards with image, name, SKU, price/"Request a quote", link.

## Tests

- Payload-driven rendering verified in browser at 390px and desktop for a bare
  and a full product (screenshots referenced in STATE).
- Feature test asserting the Inertia props for bare/full products drive the
  page (no empty sections).

## Risks

- Page length on mobile — use disclosure for long description/specs.
- Image paths: support both absolute URLs and `storage/` paths, as today.

**STOP. Await phase approval.**

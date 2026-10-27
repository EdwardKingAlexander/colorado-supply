---
plan: store-home-redesign
phase: 1
title: Store shell, catalog index, product view restyle
status: awaiting_validation
last_updated: 2026-10-05
depends_on: []
checkpoint: 1f-verify
---

# Phase 1 — Store Shell, Catalog Index, Product View

## Goal

`/store` reads as a professional industrial catalog with its own chrome,
while preserving McMaster-style drill-down and parametric filtering.

## Sub-phases

1a-tokens — Archivo font, `st-*` colour tokens (CSS vars, light + dark).
1b-layout — `StoreLayout.vue`; Cart/Checkout/CheckoutPay/ProductDetail use it.
1c-api — `CategoryResource` exposes `products_count`; test.
1d-index — `StoreIndex.vue` catalog index, department view, product view,
URL state sync.
1e-components — restyle `ProductList`, `ProductRow` (no picsum),
`ParametricFilters`.
1f-verify — `php artisan test`, `pint --dirty`, `npm run build`, browser
screenshots desktop + mobile.

Definition of done: each sub-phase builds, tests green (pre-existing
failures excepted), visually verified.

## Risks

- Inertia client-side visit + back button behaviour — verify in browser.
- Other pages still use stock gray styles inside the new shell (acceptable;
  matches dark palette).

STOP. Await phase approval.

# Store Home Redesign — Overview

## Problem Statement

The customer store (`/store`, `Store/StoreIndex.vue`) renders inside the stock
dashboard chrome (`AuthenticatedLayout`) and looks like "just another link on
the user dashboard": a generic grid of eight boxes, a duplicate heading, a
search strip, no product counts, random picsum stock photos as product
fallbacks, and no URL state (back button / shareable links don't work).

User request (2026-10-05): make the store — especially the home page —
modern, easy to use and professional, **keeping the McMaster-Carr behaviour**
(category → subcategory → parametric spec filters → dense product table).

## Design Direction

- Dedicated store shell (`Layouts/StoreLayout.vue`): sticky header with logo,
  wide catalog search (`/` to focus), account menu, cart with count.
- Home = McMaster-style catalog index: every department with its
  subcategories beneath, leader dots to item counts; one click to products.
- Product view: left rail (sibling categories + spec filters), dense parts
  table with qty + add to cart.
- Tokens: steel-navy dark palette (site forces `html.dark`), light variant
  defined; brand blue for actions, safety yellow as a rare signal.
  Archivo (variable width) as the store typeface.
- URL state via Inertia client-side visits: `?department=`, `?category=`, `?q=`.

## Constraints

- No backend behaviour change except exposing `products_count` on the
  category API resource.
- Filament admin untouched. Store toggle / maintenance page untouched.

## Phases

| # | Phase | File |
|---|-------|------|
| 1 | Store shell, catalog index, product view restyle | `phase-1-store-shell-and-catalog.md` |

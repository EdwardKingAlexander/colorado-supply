---
plan: product-detail-page
phase: 5
title: Testing, accessibility, visual QA and hardening
status: completed
last_updated: 2026-10-10
depends_on: [phase-3, phase-4]
checkpoint: closeout
---

# Phase 5 — Testing, Accessibility, Visual QA and Hardening

## Goal

Prove the page is correct, accessible and fast across viewers and data
shapes, and close any gaps found in phases 1–4.

## Success criteria

- Full `php artisan test` at or better than the recorded baseline; all module
  tests green.
- No axe-level accessibility errors on bare and full products.
- No layout shift from the gallery; no horizontal scroll at 390px.
- `npm run build` and SSR build succeed; `vendor/bin/pint --dirty` clean.

## Sub-phases

### 5.1 — Viewer matrix — `checkpoint: viewer-matrix`

Feature tests across: admin; portal user (non-government); portal user
(government company); portal user with location visibility hidden; product
outside the company → 404.

### 5.2 — Data-shape matrix — `checkpoint: data-matrix`

Bare product; priced vs unpriced; with/without tiers, company price, images,
documents, relations, compliance flags, MOQ > 1.

### 5.3 — Accessibility — `checkpoint: a11y`

Keyboard-only buy flow (stepper, add, quote modal, gallery), focus management
in modals, `aria-live` confirmations, contrast on badges, alt text fallback
to product name.

### 5.4 — Visual QA and performance — `checkpoint: visual-qa`

Browser checks at 390px, 768px, desktop, light/dark if supported; eager-load
relations (no N+1 — assert query count for the page); image `loading`/
`decoding` attributes.

### 5.5 — Close-out — `checkpoint: closeout`

Update `STATE.md`, `ai/reference/remaining.md`; list follow-ups (public product
API scope, client-priced `/api/v1/store/quote`, importer support for tiers/
images/relations).

## Risks

- Browser tooling quirks (hidden-tab throttling) — verify via JS where
  screenshots time out.

**STOP. Await phase approval.**

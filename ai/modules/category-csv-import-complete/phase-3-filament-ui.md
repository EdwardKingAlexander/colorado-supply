# Phase 3 — Filament Upload UI (preview -> confirm) + Sample Template

## Goal

Give admins the actual workflow inside the Filament `CategoryResource`: upload a
CSV, read it with `maatwebsite/excel`, run the Phase 1 planner, show a **dry-run
preview** (counts + per-row errors), and on **Confirm** run the Phase 2
committer. Plus a one-click **sample template** download so admins know the
expected format.

## Files to Create/Modify

- `app/Filament/Resources/CategoryResource/Pages/ImportCategories.php`
  (custom Filament page) **or** a header `Action` with a modal on
  `ListCategories` — pick whichever fits Filament v4 ergonomics best; a
  dedicated page is preferred for the two-step (upload -> preview -> confirm)
  flow and room to render an error table.
- Register the page in `CategoryResource::getPages()` and add an **"Import CSV"**
  header action/button on `ListCategories` linking to it.
- `resources/views/filament/resources/category/import-categories.blade.php`
  (if a custom page view is needed) — upload control, preview summary, error
  table, Confirm / Cancel.
- File reading: use `Excel::toArray()` / `Excel::toCollection()` (a small inline
  reader or a tiny `HeadingRowImport`-style helper) to get normalized rows, then
  hand them to `CategoryImportPlanner`. Accept `.csv` and `.xlsx`.
- **Sample template** action: a header action that streams a small example CSV
  (the format from `00-overview.md`) via a `StreamedResponse` / `Excel::download`
  so admins can fill it in.
- Guards: max file size and a max row-count check (reject oversized files with a
  clear notification before planning).

## UX Flow

1. Admin opens **Categories -> Import CSV**.
2. Uploads a file. Page parses + plans (no writes) and shows:
   - Summary: `N new`, `M existing (reused)`, `E errors`.
   - If errors: a table of `row #`, `message`, offending values. **Confirm is
     disabled while errors exist** (matches "dry-run then confirm").
3. Admin clicks **Confirm** -> committer runs in a transaction -> success
   notification with created/reused counts, redirect back to the list.

## Out of Scope

- Changing planner/committer logic (done in Phases 1-2) — this phase only wires
  them to the UI and reads the file.
- Bulk **product** import (categories only).

## Tests

`tests/Feature/Filament/CategoryImportTest.php` (Pest, Livewire/Filament):

- An admin can reach the import page (authorization).
- Uploading a valid CSV shows the correct preview counts **without** creating
  categories yet (dry-run: DB unchanged before confirm).
- Confirming creates the expected nested categories (end-to-end through planner
  + committer).
- Uploading a CSV with a blank-name / unknown-parent row shows the error row and
  the commit is blocked (no rows created).
- Sample-template download returns a CSV with the expected header row.

## Success Criteria

- Full flow works end to end in a Filament test: upload -> preview -> confirm ->
  nested categories exist.
- Dry-run guarantee holds (no writes until Confirm).
- All Phase 3 tests pass; `php artisan test --filter=CategoryImport` green.
- `vendor/bin/pint --dirty` clean on touched files.

**STOP. Await phase approval before implementation.**

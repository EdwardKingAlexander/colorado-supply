---
plan: report-builder
phase: 3
title: Saved reports, sharing, preview API, templates
status: completed
last_updated: 2026-10-23
depends_on: [phase-2]
checkpoint: admin
---

# Phase 3 — Persistence, Sharing, API

## Goal

Users can save, rename, duplicate, share and delete report definitions, and
preview any definition (saved or not) through HTTP endpoints that later
phases' UI uses.

## Success criteria

- `reports` table with ownership, company, visibility and edit flags.
- A policy enforces owner and company access with no roles. Admin guard users
  are redirected as today.
- Preview endpoint: validates the definition, runs it, returns
  `ReportResult` JSON, is rate limited and caches results briefly.
- Templates (the 5 legacy presets + ~6 new starters) are available to every
  user and can be copied into "My reports".

## Sub-phases

### 3.1 — Schema — `checkpoint: schema`
- Migration `create_reports_table`: `id`, `company_id` (nullable FK), `user_id` (owner FK, cascade), `name` (120), `description` (text, nullable), `dataset` (string), `definition` (json), `schema_version` (unsigned small int), `visibility` enum(`private`,`company`) default private, `company_can_edit` bool default false, `last_run_at` nullable, timestamps, soft deletes. Indexes on (`company_id`,`visibility`) and (`user_id`).
- `App\Models\Report` (casts, `owner()`, `company()`, `definition()` → `ReportDefinition`), `ReportFactory`.
- Check for existing or untracked `Report*` names before creating (see memory: parallel untracked work).
- **Done when:** the migration runs on SQLite and MySQL and the factory works.

### 3.2 — Policy — `checkpoint: policy`
- `ReportPolicy`:
  - **view:** owner, or (visibility company and same company).
  - **update:** owner, or (company + `company_can_edit` + same company).
  - **delete and share settings:** owner only.
  - **duplicate:** anyone who can view.
- A no-company user only ever sees their own reports.
- If an owner leaves the company, their shared reports stay visible to the company but can only be edited when `company_can_edit` is set. Write a test for this.
- **Done when:** a policy test matrix passes.

### 3.3 — Endpoints — `checkpoint: endpoints`
- Routes (group `auth`, `verified.enabled`, prefix `dashboard/reports`, name `dashboard.reports.`). Keep the existing `dashboard.reports` GET name pointing at the library page so links don't break:
  - `GET /` library (Inertia `Reports/Index`): my reports, shared, templates.
  - `GET /new?template=` builder (Inertia `Reports/Builder`), passes the dataset catalog.
  - `GET /{report}` builder for an existing report (view-only when not editable).
  - `POST /`, `PATCH /{report}`, `DELETE /{report}`, `POST /{report}/duplicate`.
  - `POST /preview` (JSON, `throttle:60,1`): body is a definition, response is `ReportResult` or 422.
  - `GET /catalog` (JSON) datasets → fields (key, label, group, type, aggregates, operators, enum options). Location options are limited to the user's company.
- Form requests wrap `ReportDefinitionValidator`. Controllers stay thin and the logic sits in `App\Reporting\ReportService`.
- The preview cache key is the hash of (user scope key, definition), with a 60 s TTL.
- **Done when:** feature tests cover CRUD, auth, 422s and throttling.

### 3.4 — Templates — `checkpoint: templates`
- `app/Reporting/Templates/TemplateCatalog.php`: the legacy presets plus starters:
  - Spend by month by location (pivot)
  - Top 25 products by spend
  - Open orders aging (table)
  - Payment status summary
  - Shipment delivery times by carrier
  - Quote-to-order conversion by month
- Templates live in code, not in the DB, so they upgrade with deploys.
- **Done when:** every template validates and runs on the seed in a test.

### 3.5 — Admin visibility (minimal) — `checkpoint: admin`
- A read-only `ReportResource` in Filament (list and view: owner, company, dataset, visibility, last run). It's for support only; admins can't edit customers' reports.
- **Done when:** the Filament list test passes.

## Files to create

- Migration, `Report` model, factory, `ReportPolicy`, `App\Reporting\ReportService`, controllers `Reports/{ReportLibraryController,ReportController,ReportPreviewController,ReportCatalogController}`, form requests, `TemplateCatalog`, `ReportResource` (+ pages), tests `tests/Feature/Reports/*`.

## Files to modify

- `routes/web.php` (new routes; the old `dashboard.reports` and `dashboard.reports.export` stay until phase 8, with the GET now rendering the library)
- `resources/js/Pages/Dashboard/Reports.vue`: kept until phase 4 replaces the page.

## Risks

- Route name collisions with the existing `dashboard.reports`. Decide the naming in 3.3 and grep all `route('dashboard.reports` usages.
- The JSON column on SQLite vs MySQL: use the `json` cast only and never query inside the JSON.

## Definition of done

Phase tests pass, the full suite passes, and pint has been run on the edited
files. Set STATE to `Awaiting Validation`.

**STOP. Await phase approval.**

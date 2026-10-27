# Phase 3 — Filament Read-Only Activity Log Resource

## Goal

Give admins a way to actually see the audit trail Phase 1-2 built, mirroring
the read-only resource pattern already established in this codebase
(`ContactMessageResource`, `RepairRequestResource`, `StripeEventResource`).

## Files to Create

- `app/Filament/Resources/ActivityLogResource.php`:
  - `navigationGroup = 'Admin'` (matches `StripeEventResource`'s grouping
    for system/audit-style resources), `navigationIcon =
    'heroicon-o-clipboard-document-list'`.
  - `table()`: columns for `subject_type` (formatted, e.g. "Order" not
    "App\Models\Order"), `subject_id` (linked to the actual record where
    practical), `event` (created/updated/deleted, badge-colored), `causer`
    (name, falling back to "System" when null), `created_at`. A
    `description`/diff preview column showing what changed.
  - `filters()`: subject type (select), date range, causer.
  - `getPages()`: `index` + `view` only — no create/edit/delete (this is a
    trail, not editable data; even `super_admin` shouldn't be able to
    tamper with it via the UI).
- `app/Filament/Resources/ActivityLogResource/Pages/ListActivityLog.php`,
  `Pages/ViewActivityLog.php` — thin, no header actions, same pattern as
  `RepairRequestResource`'s pages.

## Out of Scope

- No bulk actions, no delete — deliberately.
- No per-model "view history" tab on Order/Customer/etc. resources in this
  pass; the standalone log viewer is enough for v1. (A future
  `RelationManager`-style "Activity" tab on individual resource pages would
  be a natural follow-up, not required here.)

## Tests

- `tests/Feature/Filament/ActivityLogResourceTest.php`: admin can list
  activity log entries; the resource has no create/edit/delete pages
  registered (assert `getPages()` doesn't expose them).

## Success Criteria

- Admin can browse and filter the activity log in `/admin`.
- No way to edit or delete a log entry through the UI.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

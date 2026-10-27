# Phase 2 — Filament Admin Visibility

## Goal

Give staff a way to see and manage incoming repair requests, mirroring
`ContactMessageResource` exactly (same navigation group, same
handled/unhandled workflow) so there's one consistent pattern for "lead
inbox" resources in the admin panel.

## Files to Create/Modify

- `app/Filament/Resources/RepairRequestResource.php`
  - `navigationGroup = 'CRM'`, `navigationIcon = 'heroicon-o-wrench-screwdriver'`,
    `navigationLabel = 'Repair Requests'`, sort near `ContactMessageResource`.
  - `form()`: read-only display of all intake fields (disabled +
    `dehydrated(false)`, same as `ContactMessageResource`), plus editable
    `handled_at` / `handled_by`.
  - `table()`: `is_handled` icon column, `name`, `email`, `equipment_type`,
    `model_number`, `created_at` ("Received"), with `phone`/`manufacturer`/
    `serial_number` toggleable-hidden-by-default columns. Same
    `TernaryFilter` (handled) + date-range filter as `ContactMessageResource`.
  - `recordActions()`: `ViewAction`, `mark_handled`/`mark_unhandled` (same
    logic as `ContactMessageResource`).
  - `toolbarActions()`: `DeleteBulkAction` inside `BulkActionGroup`.
  - `getPages()`: `index` + `view` only (no create/edit — external
    submissions only, same as contact messages).
  - `getGloballySearchableAttributes()`: `name`, `email`, `equipment_type`,
    `model_number`, `manufacturer`.
- `app/Filament/Resources/RepairRequestResource/Pages/ListRepairRequests.php`
- `app/Filament/Resources/RepairRequestResource/Pages/ViewRepairRequest.php`

## Out of Scope

- No create/edit UI (matches `ContactMessageResource` — leads are
  submitted externally only).

## Success Criteria

- Logged in as an admin, `/admin` shows "Repair Requests" under the CRM
  nav group; a seeded/test `RepairRequest` row is visible, searchable, and
  can be marked handled/unhandled.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

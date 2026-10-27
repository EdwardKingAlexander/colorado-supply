# Phase 1 - Location Hierarchy and Reporting Foundation

## Goal

Add the data foundation needed for location and sublocation reporting without
building the dashboard UI yet.

## Scope

- Convert locations from a flat company-owned list into a simple hierarchy.
- Preserve all existing locations as root locations.
- Add model relationships for parent/children.
- Add query helpers needed by later dashboard/reporting phases.
- Establish authorization-safe assumptions for location filtering.

## Files to Create

- `database/migrations/<timestamp>_add_parent_id_to_locations_table.php`
- `tests/Feature/Models/LocationHierarchyTest.php`

## Files to Modify

- `app/Models/Location.php`
- `app/Filament/Resources/LocationResource.php`
- `app/Filament/Resources/CompanyResource/RelationManagers/LocationsRelationManager.php`
- `database/factories/LocationFactory.php` if present or create one if needed
- Relevant seeders only if they currently create demo locations and need
  parent/sublocation examples

## Data Changes

- Add nullable `parent_id` to `locations`.
- Add index on `parent_id`.
- Add foreign key from `locations.parent_id` to `locations.id` with null on
  delete.
- Keep existing unique `company_id + slug`.

## Model Relationships

`Location` should expose:

- `company()`
- `parent()`
- `children()`
- `scopeRoots()`
- `scopeForCompany($companyId)`
- Helper for display labels, such as `fullName()` or `display_name`, if useful.

## Validation Rules

- A location cannot be its own parent.
- A child location must belong to the same company as its parent.
- Deleting a parent should not delete child locations automatically unless the
  business explicitly wants that. The first implementation should null child
  `parent_id` on delete to avoid data loss.

## Reporting Contract

Later phases can rely on:

- Root locations representing primary locations.
- Child locations representing sublocations.
- An order item with a child location can be rolled up to its parent.
- An order item with a null location is reported as "Unassigned".

## Tests to Write

- Existing flat locations are still valid with `parent_id = null`.
- A location can have children.
- A child location belongs to the same company as its parent.
- Cross-company parent assignment is rejected at the validation layer.
- Deleting a parent does not delete historical order items.

## Success Criteria

- Location hierarchy exists and is safe for existing data.
- Filament location management can create/edit parent and child locations.
- No dashboard UI exists yet.
- Location hierarchy tests pass.
- `php artisan test` for targeted location/model tests passes.
- `vendor/bin/pint --dirty` passes.

**STOP. Await phase approval before implementation.**

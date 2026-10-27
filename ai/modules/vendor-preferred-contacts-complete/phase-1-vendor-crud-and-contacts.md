# Phase 1: Vendor CRUD Repair + Optional Preferred Contacts

## Goal

An administrator can create and edit a vendor without a 500, optionally add
and manage personal contacts in the same form, and designate no more than one
preferred contact.

## Work

### 1. Schema and models

- Migration makes `vendors.user_id` nullable and adds nullable `address`.
- Migration creates `vendor_contacts` with cascading `vendor_id`, name,
  nullable job title/email/work phone/mobile phone/notes, preferred flag, and
  timestamps.
- `Vendor` gains address fillability, collision-safe slug generation,
  `contacts()`, and `preferredContact()` relationships.
- New `VendorContact` model/factory enforces one preferred sibling per vendor.

### 2. Filament CRUD

- Organize `VendorResource` into vendor-details and contacts sections.
- Add an optional relationship repeater supporting create, edit, and delete
  for contacts directly on vendor create/edit.
- Contact name is required only when a contact row is added; personal contact
  fields remain optional.
- Add preferred-contact name, email, and phone columns to the vendor table,
  with mail/tel links and placeholders.
- Preserve existing vendor email and phone behavior.

### 3. Tests and validation

- Filament create test: vendor without a portal owner or contact saves,
  persists address, and receives a unique slug.
- Filament create/edit tests: optional contact details persist; replacing the
  preferred contact leaves exactly one preferred record.
- Contact delete test and vendor cascade-delete test.
- Vendor list test shows preferred contact details.
- Run focused tests, full suite baseline, `vendor/bin/pint --dirty`, and the
  frontend build if Filament assets are affected.

## Files

| File | Change |
|---|---|
| `database/migrations/...update_vendors_for_admin_creation.php` | nullable owner + address |
| `database/migrations/...create_vendor_contacts_table.php` | contact records |
| `app/Models/Vendor.php` | fillable, slug, relationships |
| `app/Models/VendorContact.php` | new model + preferred invariant |
| `database/factories/VendorContactFactory.php` | new factory |
| `app/Filament/Resources/VendorResource.php` | repaired form, contacts CRUD, table columns |
| `tests/Feature/Filament/VendorResourceTest.php` | regression and CRUD coverage |

## Success Criteria

- Adding a vendor from Filament returns to the resource normally and persists
  every displayed vendor field.
- A vendor can have no contacts.
- Contact CRUD works from vendor create/edit, including all requested personal
  contact fields.
- Setting a new preferred contact clears the previous preferred contact.
- Focused tests/build/formatting pass and the full-suite failure count does not
  exceed the recorded baseline.

STOP. Await phase approval.


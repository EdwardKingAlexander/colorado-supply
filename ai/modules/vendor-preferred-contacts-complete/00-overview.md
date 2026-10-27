# Vendor Creation Repair + Preferred Contacts

## Problem

The Filament vendor form cannot reliably create a vendor from the admin
panel. It submits no `user_id` or `slug`, although both database columns are
required, and it displays an `address` input that has no backing column or
fillable model attribute. Vendors also have no structured way to store an
optional preferred contact and that person's contact details.

## Current Incident

- The current log showed two active 500 sources: a missing `notifications`
  table from the just-completed email-verification module and Windows locking
  a compiled Filament view during a rename.
- Operational remediation completed on 2026-07-10: cleared Laravel/Filament
  caches and ran all pending migrations successfully.
- Independent code inspection found the vendor-create data mismatch above;
  this module repairs it so the create action remains valid after the cache
  incident is gone.

## Architecture

- Keep `vendors.user_id` for vendors associated with a portal user, but make
  it nullable because Filament administrators use the separate `admins`
  guard/model and cannot safely populate a `users.id` foreign key.
- Persist the existing vendor address input in a nullable `vendors.address`
  column.
- Generate a unique vendor slug in the `Vendor` model on creation. The slug
  is stable after creation and collision-safe (`acme`, `acme-2`, etc.).
- Add `vendor_contacts` as an optional one-to-many child table. A vendor may
  have zero or many contacts. Contact fields: name, job title, email, work
  phone, mobile phone, notes, and `is_preferred`.
- Enforce at most one preferred contact per vendor in the model layer so the
  invariant applies outside Filament too. Contacts are managed inline on the
  vendor create/edit form, allowing the preferred contact to be entered in
  the same workflow while remaining entirely optional.
- Show the preferred contact on the vendor list for quick email/phone access.

## Data Flow

```text
Admin opens Vendor create
  -> vendor fields + optional contacts repeater
  -> Vendor saved with generated slug and nullable portal owner
  -> contacts saved through Vendor::contacts relationship
  -> preferred contact save clears is_preferred on siblings
  -> vendor list resolves Vendor::preferredContact
```

## Constraints and Risks

- Existing vendors and product foreign keys must remain intact.
- SQLite is used locally/tests; the migration must be portable to production
  and preserve the existing `user_id` foreign key while making it nullable.
- Application-level single-preferred enforcement is cross-database portable
  but not a substitute for serialized writes under extreme concurrency; admin
  CRUD is low-volume, so this is acceptable.
- Contact details are operational business data. No public/API exposure is
  added by this module.

## Phase Table

| Phase | File | Goal |
|---|---|---|
| 1 | `phase-1-vendor-crud-and-contacts.md` | Repair vendor creation and add optional preferred-contact CRUD |


# Phase 2 — Auditable Location CRUD Backend

## Goal

Implement tenant-safe location and sublocation lifecycle endpoints, including
archive/restore behavior and structured organization audit records, before
exposing mutation controls in the normal-user dashboard.

## Scope

- Add location permissions, policy, form requests, controller, and routes.
- Default eligible company members to full location-management access.
- Server-assign `company_id`; never accept it from the request.
- Validate root/sublocation hierarchy and prevent cycles, not only direct
  self-parenting.
- Add location soft deletes and safe archive/restore workflows.
- Define explicit behavior for children when a parent is archived: the request
  must choose to archive the subtree or promote direct children to roots;
  default to blocking until a choice is made.
- Preserve archived locations for historical order/quote relationships while
  excluding them from new store/cart/checkout selection.
- Extend Spatie Activitylog with an indexed `company_id` using an application
  activity model.
- Instrument locations for created, updated, moved, archived, and restored
  events with allowlisted old/new fields.
- Add company-scoped, read-only audit endpoint/service for Phase 3 UI.

## Expected Files to Create

- `app/Policies/LocationPolicy.php`
- `app/Http/Controllers/Dashboard/LocationController.php`
- `app/Http/Controllers/Dashboard/OrganizationActivityController.php`
- `app/Http/Requests/Dashboard/StoreLocationRequest.php`
- `app/Http/Requests/Dashboard/UpdateLocationRequest.php`
- `app/Http/Requests/Dashboard/ArchiveLocationRequest.php`
- `app/Services/Organizations/LocationManagementService.php`
- `app/Services/Organizations/OrganizationActivityService.php`
- `app/Models/ActivityLog.php`
- Migration adding `deleted_at` to locations
- Migration adding indexed nullable `company_id` to `activity_log`
- `tests/Feature/Dashboard/LocationManagementApiTest.php`
- `tests/Feature/Organizations/LocationPolicyTest.php`
- `tests/Feature/ActivityLogging/LocationActivityLogTest.php`

## Expected Files to Modify

- `app/Models/Location.php`
- Historical `OrderItem` and `QuoteItem` location relationships if
  `withTrashed()` is needed
- `config/activitylog.php`
- `app/Providers/AppServiceProvider.php`
- `routes/web.php`
- Store/cart/checkout location queries where archived records must be excluded
- Existing location hierarchy/reporting tests

## Endpoint Design

- `GET /dashboard/locations` — management page data, added fully in Phase 3.
- `POST /dashboard/locations` — create root or child.
- `PATCH /dashboard/locations/{location}` — rename or move within company.
- `DELETE /dashboard/locations/{location}` — archive with explicit child
  strategy.
- `POST /dashboard/locations/{location}/restore` — restore when parent/company
  constraints remain valid.
- `GET /dashboard/organization-history` — paginated company-scoped activity.

Exact route names follow established dashboard naming conventions during
implementation.

## Tests to Write

- Company member can create roots and sublocations for their own company.
- Foreign company IDs/parents/model-bound locations return 403/404 and never
  mutate.
- Name/slug uniqueness is company-scoped and handles archived records safely.
- Arbitrary-depth cycles are rejected.
- Archive requires child strategy; promotion/archive-subtree is transactional.
- Historical order/quote location references remain readable after archive.
- Archived locations disappear from new commerce selectors and can be restored.
- Every lifecycle event records actor, company, timestamp, subject, and correct
  old/new allowlisted values.
- Organization audit queries never expose another company's entries.
- Admin override is explicitly identified in the audit entry.

## Success Criteria

- All backend CRUD operations are tenant-safe, auditable, and reversible where
  promised.
- Existing store, checkout, reports, and Filament location management continue
  working with archived records.
- No normal-user UI mutation controls are required until Phase 3.
- Targeted tests, `php artisan test`, and `vendor/bin/pint --dirty` pass.

**STOP. Await phase approval before implementation.**

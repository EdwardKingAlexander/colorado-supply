# Phase 2 — Instrument Order, User, Customer, Opportunity, Vendor

## Goal

Add activity logging to the five models named in the overview, scoping
logged attributes deliberately rather than logging every column.

## Files to Modify

- `app/Models/Order.php` — `LogsActivity` trait; log `status`,
  `payment_status`, `fulfillment_status`, `grand_total`, `portal_user_id`
  (status/payment transitions are the events a dispute or audit would
  actually ask about — not every touch).
- `app/Models/User.php` — `LogsActivity` trait; log `name`, `email`,
  role/permission changes (via `getDescriptionForEvent` or a dedicated
  logged event on `assignRole`/`removeRole` — Spatie's permission and
  activitylog packages don't auto-bridge, so this needs an explicit
  `Event::listen` or model observer bridging Spatie Permission's role
  events into an activity log entry). This is the single highest-value
  entry in this whole module: "who was granted what role, when" is exactly
  what a security review asks for first.
- `app/Models/Customer.php` — `LogsActivity` trait; log `owner_id`,
  `status`/stage-equivalent fields, contact fields.
- `app/Models/Opportunity.php` — `LogsActivity` trait; log `owner_id`,
  `stage_id`/pipeline fields, `value`/amount fields.
- `app/Models/Vendor.php` — `LogsActivity` trait; log every compliance
  field (`sam_expiration_date`, `sam_number`, `w9_date`, `tax_id`,
  `insurance_expiration_date`, `insurance_policy_number`, `cage_code`,
  `duns_number`, `naics_code`) — these are exactly the fields
  `ComplianceAuditTool` (see `ai/modules/repair-services` sibling work this
  session — the tool's own bug was just fixed) audits, so a change history
  on them is directly useful for a compliance review.

## Out of Scope

- No other models get instrumented in this pass (see 00-overview's
  constraint on scope).
- No UI yet (Phase 3).

## Tests

- `tests/Feature/ActivityLogging/OrderActivityLogTest.php`,
  `CustomerActivityLogTest.php`, `OpportunityActivityLogTest.php`,
  `VendorActivityLogTest.php`: creating/updating each model produces the
  expected `activity_log` row with the correct causer, subject, and only
  the scoped attributes in the diff.
- `tests/Feature/ActivityLogging/UserRoleActivityLogTest.php`: assigning/
  removing a role produces an activity log entry describing the change
  (this is the bridge mentioned above — needs its own explicit test since
  it's not automatic).

## Success Criteria

- All five models produce correct, causer-attributed log entries on
  create/update.
- Role assignment changes are logged (the User/role bridge is the
  highest-scrutiny item in this phase — don't skip its test).
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

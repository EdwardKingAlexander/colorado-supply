# Phase 4 — Supervisor Requests and Member Locks

## Goal

Add a multi-supervisor governance workflow that lets company users request the
role and lets authorized reviewers control which members may mutate locations.

## Scope

- Add `location_supervisor` role and focused permissions.
- Add supervisor request and organization location-access models/tables.
- Add request/cancel/review services, policies, controllers, and dashboard UI.
- Require administrator approval for the first active company supervisor.
- Allow an active same-company supervisor or administrator to approve/reject
  later requests.
- Add same-company member list for supervisors with lock/unlock controls.
- Make access decisions immediately affect the Phase 2 `LocationPolicy`.
- Retain view/history access for locked users.
- Audit request decisions, role assignment/revocation, locks, unlocks, reasons,
  and admin overrides.
- Keep supervisor revocation administrator-only.

## Expected Files to Create

- `app/Models/SupervisorRoleRequest.php`
- `app/Models/OrganizationLocationAccess.php`
- Enums for request status and access status
- `app/Policies/SupervisorRoleRequestPolicy.php`
- `app/Policies/OrganizationLocationAccessPolicy.php`
- `app/Services/Organizations/SupervisorGovernanceService.php`
- `app/Http/Controllers/Dashboard/SupervisorRequestController.php`
- `app/Http/Controllers/Dashboard/OrganizationMemberAccessController.php`
- Focused form requests for submit/review/lock/unlock actions
- Migrations for requests and access decisions
- Factories for both models
- `resources/js/Pages/Dashboard/OrganizationGovernance.vue`
- `resources/js/Components/Organizations/SupervisorRequestPanel.vue`
- `resources/js/Components/Organizations/MemberAccessTable.vue`
- `tests/Feature/Organizations/SupervisorRequestTest.php`
- `tests/Feature/Organizations/LocationAccessControlTest.php`
- `tests/Feature/ActivityLogging/SupervisorGovernanceActivityTest.php`

## Expected Files to Modify

- `database/seeders/RolesAndPermissionsSeeder.php`
- `app/Models/User.php`
- `app/Models/Company.php`
- `app/Policies/LocationPolicy.php`
- `app/Providers/AppServiceProvider.php`
- `routes/web.php`
- `resources/js/Layouts/AuthenticatedLayout.vue`
- `resources/js/Pages/Dashboard/Locations/Index.vue`
- Admin User/Company resources for first approval, revocation, and emergency
  override controls

## Workflow Rules

- One pending request per user; duplicate submissions are idempotently rejected.
- Existing supervisors cannot submit another request.
- First-approval decision acquires a company row lock and re-checks active
  supervisors inside the transaction.
- Once any active supervisor exists, same-company supervisors may review later
  requests.
- Reviewers cannot review requests from another company unless they are a
  Colorado Supply administrator.
- Supervisors cannot lock themselves.
- Last authorized lock/unlock decision wins; prior decisions remain audited.
- Locking a supervisor blocks their location CRUD but does not silently remove
  the supervisor role or review authority. Only an administrator can revoke the
  role.
- Users cannot be locked unless they currently belong to the reviewer's company
  and their domain remains valid.

## Tests to Write

- First request requires admin approval.
- After first approval, same-company supervisor can approve another request.
- Multiple supervisors coexist.
- Foreign-company supervisor cannot view/review requests or control members.
- Concurrent first approvals remain consistent and do not duplicate role state.
- Supervisor can lock/unlock another member but not themselves.
- Locked user loses all location mutations immediately but keeps view/history.
- Another supervisor can reverse a decision and the complete history remains.
- Supervisor cannot revoke a supervisor role; admin can and action is audited.
- Domain-mismatched/inactive members cannot request or receive the role.

## Success Criteria

- Supervisor requests and approvals obey the confirmed first/later reviewer
  rules.
- Multiple supervisors and reversible member locks work without cross-tenant
  access or accidental lockout.
- All governance actions appear in organization history.
- Targeted tests, `npm run build`, `php artisan test`, and
  `vendor/bin/pint --dirty` pass.

**STOP. Await phase approval before implementation.**

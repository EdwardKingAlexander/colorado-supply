# Organization Location Governance — Overview

## Problem Statement

Normal users can see location-based purchasing analytics, but they cannot
create, edit, archive, or restore their organization's locations and
sublocations. The platform also lacks a customer-visible history of those
changes and an organization-level mechanism for supervisors to restrict who
may change the hierarchy.

This feature adds:

1. Company-domain membership enforcement.
2. Tenant-safe location and sublocation CRUD in the normal-user dashboard.
3. A read-only organization audit trail showing who changed what and when.
4. A request/approval workflow for multiple location supervisors.
5. Per-member location-edit locks managed by supervisors.

## Confirmed Product Decisions

- Every company can have one or more administrator-approved email domains.
- A company user must have an email matching one of that company's domains.
  Both `company_id` and domain matching are enforced; a user from
  `example.com` cannot manage an `example2.com` organization.
- The first supervisor request for a company is approved by a Colorado Supply
  administrator. Once a company has a supervisor, any active supervisor or
  administrator can approve additional supervisor requests.
- Multiple supervisors are supported.
- All eligible company users can manage locations by default.
- A supervisor can lock or restore location-management access for another
  member of the same company. Any active supervisor can reverse another
  supervisor's decision. Administrators retain an emergency override.
- All company members can view the company-wide, read-only location governance
  history, including actor, timestamp, action, and before/after values.
- Supervisor-role revocation remains administrator-only to avoid peer-removal
  conflicts. Supervisors may approve requests and manage location access, but
  cannot remove another supervisor's role.

## Existing Foundation to Reuse

- `Company`, `User`, and `Location` already use `company_id` relationships.
- `Location.parent_id` already represents sublocations and prevents
  cross-company parents and self-parenting.
- The normal-user dashboard already receives company location data and has
  authenticated navigation.
- Spatie Laravel Permission is installed for roles and permissions.
- Spatie Laravel Activitylog v4, its database schema, and dual-guard causer
  resolution are already implemented by `ai/modules/audit-logging` Phase 1.
- Admin Filament resources already manage companies, users, and locations.

This module extends those foundations. It does not introduce a second role or
audit system.

## Constraints

- All reads and mutations must be scoped by authenticated `company_id` on the
  server. Client-provided company IDs are never trusted.
- Company-domain checks supplement tenant scoping; they never replace it.
- Domains are normalized to lowercase ASCII, stored without `@`, paths, ports,
  or whitespace, and unique across companies.
- Users without a company may register and edit their email normally. Once an
  administrator links a user to a company, the email must match an approved
  domain for that company.
- Existing company users keep access during migration only through an explicit
  rollout gate. Strict enforcement cannot be enabled for a company until an
  administrator has configured at least one domain and reviewed mismatches.
- No company is inferred from an email domain during public registration; that
  could allow unauthorized organization enrollment.
- Location deletion is implemented as an archive (soft delete), preserving
  order/reporting history and the audit subject. Child sublocations must be
  explicitly moved, archived, or handled by the approved archive workflow; no
  silent destructive cascade.
- Archived locations cannot be selected for new carts, quotes, or orders, but
  remain resolvable for historical records.
- Audit history is append-only in customer-facing UI. No user or supervisor may
  edit or delete audit entries.
- Audit values are allowlisted. Passwords, MFA data, tokens, and unrelated user
  profile fields must never be logged.
- The repository currently has extensive uncommitted work. Each phase must
  enumerate overlapping files before editing and preserve unrelated changes.

## Architecture (Textual)

```text
Company
  -> CompanyEmailDomain (one or more; admin-approved; globally unique domain)
  -> Users
       -> optional location_supervisor role
       -> OrganizationLocationAccess (default allowed; explicit locked/allowed)
       -> SupervisorRoleRequest (pending/approved/rejected/canceled)
  -> Locations
       -> root location
       -> child sublocations via parent_id

Normal-user dashboard
  -> LocationPolicy + CompanyDomainMatcher + LocationAccessService
  -> company-scoped controllers/form requests
  -> create/update/archive/restore Location
  -> Spatie Activitylog writes actor + company_id + old/new values
  -> organization members read company-scoped audit history

Supervisor request
  -> first request: admin approval
  -> later requests: current company supervisor or admin approval
  -> transactional role assignment + audited request decision

Supervisor member control
  -> lock/unlock company member
  -> LocationPolicy evaluates explicit access state
  -> audited decision visible to all company members
```

## Authorization Rules

### Location viewing

- Authenticated, verified normal user.
- User has a company.
- User's email domain matches an approved domain for that company after strict
  enforcement is activated.
- The requested location belongs to the same company.

### Location mutation

- Meets all viewing requirements.
- No active location-management lock for the user.
- Parent and child records are in the same company.
- Administrators may override for support/recovery; the override is audited.

### Supervisor operations

- Request: any eligible company user who is not already a supervisor and has no
  pending request.
- Approve first supervisor: Colorado Supply administrator only.
- Approve later supervisors: active supervisor in the same company or
  administrator.
- Reject: same authorization as approval.
- Lock/unlock: active supervisor in the same company or administrator.
- A supervisor cannot lock themselves; this prevents accidental self-lockout.
  An administrator can apply or remove any lock.
- Revoke supervisor role: administrator only.

## Audit Contract

Organization history includes:

- Location/sublocation created, updated, archived, restored, and parent moved.
- Company email domain added, updated, or removed.
- Supervisor request submitted, approved, rejected, or canceled.
- Supervisor role granted or revoked.
- Member location access locked or restored.
- Administrator override actions.

Each entry records company, actor, subject type/ID, action, timestamp, and
allowlisted old/new values. Tenant queries use an indexed `company_id` column on
the activity log rather than unindexed JSON filtering.

## Data Model

### `company_email_domains`

- `id`, `company_id`, `domain`, `is_primary`, `approved_by_admin_id`,
  `approved_at`, timestamps.
- Unique normalized domain globally.
- At most one primary domain per company, enforced by service validation and
  tested for database-specific behavior.

### `supervisor_role_requests`

- `id`, `company_id`, `user_id`, `status`, `reviewed_by_type`,
  `reviewed_by_id`, `reviewed_at`, `review_note`, timestamps.
- One active pending request per user.
- Historical decisions retained.

### `organization_location_access`

- `id`, `company_id`, `user_id`, `status` (`allowed` or `locked`),
  `decided_by_type`, `decided_by_id`, `decided_at`, optional reason,
  timestamps.
- Unique user/company row.
- No row means allowed, preserving default access.

### Existing tables

- Add `softDeletes()` to `locations`.
- Add nullable indexed `company_id` to `activity_log` and configure the package
  to use an application activity model that supports reliable tenant scoping.
- Add a company-level domain-enforcement timestamp/flag so rollout is explicit
  rather than inferred from whether a relation happens to be empty.

## Data Flows

### Create sublocation

```text
POST /dashboard/locations
  -> authenticated/verified middleware
  -> LocationPolicy::create
  -> domain match + access-control check
  -> validate parent belongs to user's company
  -> server assigns company_id and slug
  -> save Location
  -> activity entry includes company_id, actor, parent/name old/new values
  -> redirect with success state
```

### Lock a member

```text
Supervisor selects same-company member
  -> policy verifies active supervisor and same company
  -> transaction upserts access status=locked
  -> audit entry captures actor, affected member, reason, old/new status
  -> locked member immediately loses create/update/archive/restore permission
     but keeps view/history/supervisor-request access
```

### Email change

```text
Company user submits new email
  -> shared CompanyEmailDomainRule validates normalized domain
  -> matching domain: normal save + verification reset
  -> nonmatching domain: validation error, no mutation

Admin assigns/transfers company
  -> destination company must have active domain enforcement
  -> user's current/new email must match destination domain
  -> explicit audited admin action
```

## Risks and Mitigations

- **Existing companies have no configured domain** — add an admin mismatch
  report and per-company activation gate; never guess domains or enable strict
  enforcement before review.
- **Email-based organization takeover** — never auto-join a company from public
  registration; only administrators control company membership/domains.
- **Cross-tenant mutation** — server assigns company IDs, policies re-query
  tenant records, and tests attack route/model binding with foreign IDs.
- **Supervisor race during first approval** — approve in a transaction with row
  locking and re-check whether an active supervisor already exists.
- **Conflicting supervisors** — last authorized lock/unlock decision wins and
  every decision remains visible in audit history.
- **Location deletion damages history** — archive instead of hard delete and
  keep historical order/quote relations capable of resolving archived records.
- **Unbounded audit exposure** — organization history is company-scoped,
  paginated, allowlisted, and contains no secrets.
- **Role/package mismatch** — use one `location_supervisor` Spatie role and
  dedicated policies; do not rely on the legacy `users.role` enum.

## Phase List

| # | Phase | Outcome |
|---|---|---|
| 1 | Company domains and membership boundary | Domain schema/admin management, matching rule, rollout audit and enforcement |
| 2 | Auditable location CRUD backend | Policies, requests, routes, soft-delete lifecycle, tenant-indexed activity records |
| 3 | Dashboard location management and history | Normal-user CRUD UI plus organization-wide read-only history |
| 4 | Supervisor requests and member locks | Multi-supervisor approval workflow and per-member mutation control |
| 5 | Migration, security, and hardening | Legacy rollout, comprehensive authorization/concurrency/accessibility tests |

Status tracking lives in `STATE.md`. Every phase requires its own approval and
validation under `PROJECT_CANNON.md`.

**STOP. Await plan approval before any implementation begins.**

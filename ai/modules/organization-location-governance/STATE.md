# Organization Location Governance — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|---|---|---|---|
| - | Module plan (`00-overview.md`) | Complete | 2026-07-10 | Orchestrator approved the overall plan |
| 1 | Company domains and membership boundary | Complete | 2026-07-10 | Implemented, targeted verification passed, and orchestrator validated |
| 2 | Auditable location CRUD backend | Awaiting Validation | 2026-07-10 | Implemented; focused and full regression suites pass |
| 3 | Dashboard location management and history | Not Started | 2026-07-10 | Normal-user UI and tenant-visible read-only trail |
| 4 | Supervisor requests and member locks | Not Started | 2026-07-10 | First approval by admin; later approvals by supervisor/admin |
| 5 | Migration, security, and hardening | Not Started | 2026-07-10 | Staged domain activation and adversarial tenant testing |

## Log

- 2026-07-10 — Initial plan drafted after the orchestrator confirmed all three
  recommended governance decisions:
  - Enforce both `company_id` and one-or-more administrator-approved company
    email domains.
  - Require Colorado Supply administrator approval for the first supervisor;
    allow existing same-company supervisors or administrators to approve later
    supervisors.
  - Allow reversible per-member location-management locks and expose a
    company-wide read-only history to all organization members.
- Existing implementation review confirmed:
  - `Location.parent_id`, same-company parent validation, dashboard location
    reporting, and admin location CRUD already exist.
  - Normal users currently have no location mutation routes or UI.
  - Users have one nullable `company_id`; companies currently have no domain
    registry.
  - Spatie Permission is installed, but there is no location-supervisor role.
  - Spatie Activitylog v4 and dual web/admin causer resolution are installed,
    but model instrumentation/customer history have not been implemented.
  - Current hard deletion nulls child parent links and historical item foreign
    keys in some cases, so this plan uses archive/restore semantics for safe
    customer CRUD.
- Planning added only `ai/modules/organization-location-governance/`. No feature
  implementation or unrelated dirty-worktree files were changed.
- STOP. Await orchestrator approval of the overall plan.
- 2026-07-10 — Orchestrator approved the overall module plan and asked to start
  Phase 1. Phase 1 scope was reconfirmed against the current worktree:
  - Add normalized, globally unique administrator-approved company domains.
  - Add explicit per-company enforcement activation and a mismatch audit
    command/report so existing users are not silently locked out.
  - Apply the shared domain rule to profile email changes and administrator
    company/email assignment without auto-joining public registrations.
  - Add Company-resource domain management and targeted unit/feature/Filament
    tests.
  - No location CRUD, customer-facing history, supervisor workflow, or member
    locks are included until their later phases.
  - STOP. Await explicit Phase 1 approval before implementation.
- 2026-07-10 — Orchestrator explicitly approved continuation through all feature
  phases and requested feature changes be committed for later pushing. Under
  `PROJECT_CANNON.md`, implementation remains one phase at a time; Phase 1 is
  now In Progress. Only feature-owned files will be committed.
- 2026-07-10 — Phase 1 implemented:
  - Added `company_email_domains` with normalized globally unique domains,
    primary-domain handling, administrator approval metadata, and organization
    activity logging.
  - Added explicit `companies.domain_enforcement_enabled_at`; enforcement is
    activated only after at least one domain exists and every current member
    matches.
  - Added `CompanyDomainService`, shared validation rule, User model guard,
    profile validation, and Filament User create/edit validation.
  - Added Company Filament domain management with enable/disable enforcement
    actions and matching company/user display fields.
  - Added `organizations:audit-email-domains` for rollout readiness and mismatch
    reporting.
  - Corrected the existing Activitylog causer resolver so an explicit
    `causedBy($model)` is honored before falling back to admin/web guards; the
    existing three causer-resolution tests remain green.
  - Verification passed: 26 targeted tests / 76 assertions across domain
    normalization, activation, profile/model enforcement, admin forms,
    Activitylog causers, and existing profile behavior. `vendor/bin/pint
    --dirty` passes and `git diff --check` is clean.
  - Full `php artisan test` was run but is not green for demonstrably unrelated
    existing failures: four `ProductDataScraperToolTest` cases call a now
    six-argument method with three arguments; two SAM document API tests assert
    files on a different fake disk/path; the complete run later exhausts the
    128 MB PHP limit inside the backup ZIP dependency. Each failing test file
    reproduces independently without this feature's code path.
  - Phase 1 is Awaiting Validation. No Phase 2 implementation has started.
- 2026-07-10 — Orchestrator validated Phase 1. Phase 1 marked Complete and
  Phase 2 scope reconfirmed:
  - Add company-scoped Location policy, form requests, controller, routes, and
    transactional lifecycle service.
  - Add soft-delete archive/restore semantics with an explicit child strategy,
    preserve historical order/quote relations, and exclude archived locations
    from new commerce selections.
  - Extend Activitylog with indexed `company_id`, instrument location lifecycle
    events, and expose a tenant-scoped read-only activity service/endpoint for
    the Phase 3 UI.
  - No dashboard CRUD UI or supervisor/member lock workflow is included in
    Phase 2.
  - Phase 2 is Awaiting Phase Approval, but implementation cannot begin while
    `PROJECT_CANNON.md`'s all-tests-pass gate is red from the unrelated MCP
    scraper, SAM document fake-disk, and backup ZIP memory failures recorded
    above. Correcting those failures requires explicit scope authorization.
- 2026-07-10 — Orchestrator authorized repairing the unrelated test/memory
  blockers and approved Phase 2. Baseline gate restored before Phase 2 work:
  - Fixed stale MCP scraper reflection calls, registered the private SAM
    document disk, and raised PHPUnit-only memory to 512 MB (`64569959`).
  - Added missing factories, aligned legacy tests with current routes/config and
    Excel fake APIs, restored quote payment-method validation, and made SAM
    retry sleeps production-only (`270d235b`).
  - Full baseline suite passes: 650 passed, 5 skipped, 2,391 assertions.
  - Phase 2 set to In Progress; later-phase UI and supervisor controls remain
    out of scope.
- 2026-07-10 — Phase 2 implemented:
  - Added company-scoped location authorization, validated dashboard CRUD API
    routes, and a transactional lifecycle service for root locations and
    sublocations.
  - Added archive/restore semantics with explicit promote-or-subtree handling,
    cycle protection, historical order/quote preservation, and active-location
    validation for new commerce requests.
  - Added an indexed `activity_log.company_id`, company-indexed location and
    domain audit events, administrator-override metadata, and a tenant-scoped
    read-only organization activity endpoint for the Phase 3 dashboard.
  - Focused verification passes: 33 tests / 155 assertions. Full regression
    verification passes: 664 tests, 5 skipped / 2,463 assertions.
    `vendor/bin/pint --dirty`, config caching, route caching, and
    `git diff --check` pass.
  - Phase 2 is Awaiting Validation. No Phase 3 implementation has started.

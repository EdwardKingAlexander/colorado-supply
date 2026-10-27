# Audit Logging / Activity Trail — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Approved | 2026-07-09 | User approved proceeding with all unblocked phases |
| 1 | Package install, version decision, multi-guard causer config | Complete | 2026-10-23 | Validated 2026-10-23: 3/3 causer tests re-run green, see log |
| 2 | Instrument Order, User, Customer, Opportunity, Vendor | Complete | 2026-10-23 | Validated by orchestrator ("Approved. Start phase 3") |
| 3 | Filament read-only Activity Log resource | In Progress | 2026-10-23 | Scope confirmed, see log |
| 4 | Testing & hardening | Not Started | 2026-07-04 | |

## Log

- 2026-07-04 — Plan drafted. This module was one of four gap areas the user
  asked to have planned after asking "what's the right path forward" for a
  platform selling to government and other businesses (the other three:
  `ai/modules/admin-account-security-complete`, `ai/modules/production-observability`,
  and a direct bug fix already applied to `ComplianceAuditTool`, see
  `app/Mcp/Servers/Business/Tools/ComplianceAuditTool.php`).
  - Researched `spatie/laravel-activitylog` current state: v5 (released
    2026-03-25) requires PHP 8.4 + Laravel 12 minimum. This dev environment
    runs PHP 8.4.22, but `composer.json` only declares `"php": "^8.2"` —
    meaning production may run a lower PHP version than dev. **Phase 1 is
    blocked until the orchestrator confirms production's actual PHP
    version**, since installing v5 on a sub-8.4 production host would be a
    real outage risk, and v4 vs v5 have different APIs/schemas that Phase 2
    would need to target correctly from the start.
  - Scoped to 5 models (Order, User, Customer, Opportunity, Vendor) rather
    than app-wide logging, per the "don't log everything blindly" principle
    — expanding coverage later is a small additive change.
  - Flagged the User-role-change bridge (Spatie Permission's role
    assignment isn't automatically bridged into Spatie Activitylog) as the
    single highest-value, easiest-to-miss piece of this module.
  - Flagged a sensitive-data-in-logs decision (`Vendor.tax_id`, `User.email`)
    for Phase 4 rather than deciding it unilaterally now.
  - Awaiting orchestrator approval of the overall plan, and an answer to
    the PHP-version blocking question, before Phase 1 begins.
- 2026-07-05 — User confirmed production runs PHP 8.3. Phase 1 unblocked:
  will install `spatie/laravel-activitylog:^4.0` (not the newly-released
  v5, which hard-requires PHP 8.4+) to match production's actual PHP
  version. User approved moving forward on all three gov/B2B-readiness
  modules; Phase 1 can now start once the module plan itself is confirmed
  approved.
- 2026-07-09 — Phase 1 implemented:
  - `composer require spatie/laravel-activitylog:^4.0`, published config
    and the 3 migration stubs (base table + `event` column +
    `batch_uuid` column), ran `php artisan migrate` cleanly.
  - `config/activitylog.php`: `subject_returns_soft_deleted_models` set to
    `true` — deletion is itself one of the audited events, so the subject
    should stay visible on its own audit trail after a soft delete.
  - `app/Support/ActivitylogCauserResolver.php` (new, invokable class):
    checks `Auth::guard('admin')->user()` then `Auth::guard('web')->user()`,
    matching the existing dual-guard pattern from
    `AuthenticateWebOrAdmin`. Registered in `AppServiceProvider::boot()`
    via `CauserResolver::resolveUsing(...)`.
  - Correction to the plan's assumed API: v4 has no static
    `setCauserResolver()`. The actual API is the `CauserResolver` facade's
    instance method `resolveUsing(Closure $callback)`, which requires a
    real `Closure` — passing the invokable object directly throws a
    TypeError, so it's wrapped with `Closure::fromCallable(...)`.
  - `tests/Feature/ActivitylogCauserResolutionTest.php`: 3/3 passing —
    web-guard causer, admin-guard causer, and null-causer (unauthenticated)
    cases.
  - `vendor/bin/pint --dirty` clean (102 files, 3 auto-fixed import-order
    issues in the package's own published migration stubs).
  - Proceeding to Phase 2 (model instrumentation) next.
- 2026-10-23 — Orchestrator approved ("Approved. Move forward"). Phase 1
  re-verified before sign-off: `ActivitylogCauserResolutionTest` 3/3 green
  on PHP 8.4.26. Phase 1 marked Complete. Drift folded in: a
  `2026_07_10` migration added nullable `company_id` to `activity_log`
  (no FK, survives company deletion) and `config/activitylog.php` now
  points at a custom `App\Models\ActivityLog` (adds `company()`
  relation); ten other models (NSN sourcing, DIBBS, Location, ...)
  already use `LogsActivity`, proving the foundation. Phase 2 scope
  re-confirmed against current models with adjustments: Customer has no
  status/stage field, so it logs `owner_id` + contact fields only
  (address blobs skipped as noise); Opportunity gains `status` alongside
  the planned owner/stage/pipeline/amount fields (close-outcome audit);
  log names follow the codebase convention (`store`, `users`, `crm`,
  `vendors`); `tapActivity` stamps `company_id` (direct on Order/User,
  via owner/user on Customer/Opportunity/Vendor); the role bridge also
  covers direct permission grants and Admin subjects (same Spatie
  events), log name `users`/`admins`. Phase 2 approval granted in the
  same message; set to In Progress.
- 2026-10-23 — Phase 2 implemented: `LogsActivity` on Order (`store`),
  User (`users`), Customer (`crm`), Opportunity (`crm`), Vendor
  (`vendors`) with scoped `logOnly` + `logOnlyDirty` +
  `dontSubmitEmptyLogs` and `tapActivity` company stamping, mirroring
  the Location/DIBBS pattern. Role bridge: new
  `app/Listeners/LogRoleAssignmentActivity.php` handles Spatie
  RoleAttached/RoleDetached/PermissionAttached/PermissionDetached
  (permission payload is `$permissionsOrIds`, not `$rolesOrIds`),
  registered explicitly in `AppServiceProvider::boot`, with
  `permission.events_enabled` flipped to true (was false, events never
  fired). Tests: 6 new files under `tests/Feature/ActivityLogging/`
  (15 tests: scoped-attributes on create, old/new diffs on update,
  unscoped-touch writes nothing, role/permission grant+removal with
  admin causer). Two test bugs fixed during the run (permission payload
  property; stale-instance decimal diff in the Opportunity test).
  Verification: focused `--filter=ActivityLog` 21/21 green (15 new +
  3 Phase 1 causer + 3 pre-existing Location), Pint clean on all
  touched files. Full suite is non-deterministic on this tree: three
  identical runs gave 2, 9, then 3 failures with rotating test names
  (DIBBS export, reporting validator, tenant matrix — none in
  audit-logging scope; DIBBS file passes 26/26 standalone with these
  changes applied; suite has documented flakes since 2026-10-04). Phase
  set to Awaiting Validation. STOP: Phase 3 needs approval after
  validation.
- 2026-10-23 — Orchestrator approved Phase 3 ("Approved. Start phase 3");
  Phase 2 marked Complete. Scope re-confirmed against the current
  Filament v4 codebase: pages stay thin (index + view, no header
  actions) per RepairRequestResource; one deliberate deviation — the
  view page uses an infolist (not a disabled form) so the properties
  diff renders as key/value entries. Small additions: `log_name` badge
  and toggleable-hidden company columns (entries now span six logs
  across tenants); subject-type filter options are dynamic distinct
  values so other modules' subjects stay filterable; subject links
  resolve view-or-edit per target resource (Order/Opportunity view,
  User/Customer/Vendor edit) guarded by `hasPage` plus the record
  permission check, plain text otherwise. Phase 3 set to In Progress.

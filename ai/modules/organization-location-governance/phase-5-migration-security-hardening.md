# Phase 5 — Migration, Security, and Hardening

## Goal

Validate the complete feature under legacy data, hostile tenant-boundary
attempts, concurrent governance decisions, and production deployment/rollback
conditions before enabling strict domain enforcement company by company.

## Scope

- Run and review the company-domain audit report against production-shaped data.
- Document administrator steps to configure domains and activate companies.
- Add end-to-end tests spanning domain enforcement, CRUD, audit visibility,
  supervisor approval, and member locks.
- Add explicit cross-company route/model-binding attack coverage.
- Test concurrent supervisor approvals and lock reversals where the database
  driver permits realistic transactions.
- Test audit pagination, retention compatibility, and query performance.
- Test historical order/quote reporting with archived locations.
- Validate responsive, keyboard, and screen-reader behavior.
- Validate config/route caching and production PHP 8.3 compatibility.
- Add deployment, rollback, and support runbooks.

## Expected Files to Create

- `tests/Feature/Organizations/OrganizationLocationGovernanceEndToEndTest.php`
- `tests/Feature/Organizations/OrganizationTenantIsolationTest.php`
- `tests/Feature/Organizations/OrganizationGovernanceConcurrencyTest.php`
- Browser accessibility/responsive tests as appropriate
- `docs/organization-location-governance-deployment.md`
- `docs/organization-location-governance-support.md`

## Expected Files to Modify

- Only implementation/test files requiring fixes found during validation
- `STATE.md` and cross-reference notes in `ai/modules/audit-logging/STATE.md`
  after shared audit functionality is genuinely validated

## Deployment Sequence

1. Back up the production database through the established deployment process.
2. Deploy additive schemas with domain enforcement inactive.
3. Run the domain audit report.
4. Administrator configures approved domains and resolves mismatched members.
5. Activate domain enforcement one company at a time.
6. Enable dashboard location management after smoke-testing an eligible and a
   locked account.
7. Validate a complete audit entry and first-supervisor approval.
8. Monitor authorization failures and activity-log volume.

## Tests and Validation

- Full relevant PHP suite and focused browser suite.
- `npm run build` and SSR build if SSR assets are intentionally part of the
  current deployment process.
- `vendor/bin/pint --dirty`.
- `php artisan config:cache` and `php artisan route:cache`.
- Database query inspection for location tree, member list, and audit history.
- Manual two-company matrix with normal member, locked member, supervisor,
  second supervisor, and global administrator.

## Success Criteria

- No existing company is unexpectedly locked out during rollout.
- Strict domain enforcement is active only for reviewed companies.
- Cross-tenant reads and writes fail across every endpoint and role.
- Location history survives archive/restore and accurately renders diffs.
- First/later supervisor approvals and reversible locks work under concurrency.
- Documentation enables an administrator to deploy, activate, diagnose, and
  roll back safely.
- All required tests/build/cache/format checks pass.

**STOP. Await phase approval before implementation.**

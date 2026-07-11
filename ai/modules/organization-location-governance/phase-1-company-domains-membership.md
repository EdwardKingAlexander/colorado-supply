# Phase 1 — Company Domains and Membership Boundary

## Goal

Create an administrator-approved company-domain registry and centrally enforce
that a company member's email belongs to that company before organization
management features are enabled.

## Scope

- Add normalized, globally unique company email domains with approval metadata.
- Support multiple domains and one primary domain per company.
- Add explicit per-company domain-enforcement activation.
- Add admin domain CRUD within the existing Company resource.
- Add a reusable domain parser/matcher and validation rule.
- Enforce matching on self-service profile email changes and administrator
  company assignment/email changes once the destination company is activated.
- Do not infer company membership from public registration.
- Add a read-only admin report/command listing companies without domains and
  current users whose emails do not match.
- Log domain CRUD through the existing activity package where the Phase 2
  tenant activity extension is not yet required; Phase 2 will finalize the
  company-indexed audit representation before customer visibility.

## Expected Files to Create

- `app/Models/CompanyEmailDomain.php`
- `app/Rules/MatchesCompanyEmailDomain.php`
- `app/Services/Organizations/CompanyDomainService.php`
- `app/Console/Commands/AuditCompanyEmailDomains.php`
- `app/Filament/Resources/CompanyResource/RelationManagers/EmailDomainsRelationManager.php`
- Migration for `company_email_domains`
- Migration adding explicit domain-enforcement state to `companies`
- `database/factories/CompanyEmailDomainFactory.php`
- `tests/Unit/Organizations/CompanyDomainServiceTest.php`
- `tests/Feature/Organizations/CompanyDomainEnforcementTest.php`
- `tests/Feature/Filament/CompanyEmailDomainTest.php`

## Expected Files to Modify

- `app/Models/Company.php`
- `app/Models/User.php` relationships/helpers only as needed
- `app/Http/Requests/ProfileUpdateRequest.php`
- `app/Filament/Resources/CompanyResource.php`
- `app/Filament/Resources/UserResource.php`
- User Filament create/edit page hooks if validation must occur after form data
  composition
- Existing factories/seeders only where required for stable tests

## Validation Rules

- Normalize lowercase, trim whitespace, strip a leading `@`, and convert
  international domains to canonical ASCII where platform support exists.
- Reject schemes, paths, ports, local-only names, malformed labels, and empty
  values.
- A domain belongs to only one company.
- A company's final domain cannot be removed while strict enforcement is active.
- Activation is blocked when any existing member mismatches; the admin report
  identifies those records for explicit correction or transfer.
- Unchanged emails remain saveable during pre-activation migration.
- After activation, every new/changed company membership or email must match.

## Tests to Write

- Normalization and exact-domain matching, including case and subdomain edge
  cases; `notexample.com` must not match `example.com`.
- Duplicate domains across companies are rejected.
- Multiple approved domains for one company work.
- Company user cannot change to another company's domain.
- Unaffiliated user can change email and public registration does not auto-join.
- Admin cannot assign a mismatched user to an enforced company.
- Enforcement activation fails with a mismatch and succeeds after correction.
- Final-domain removal is blocked for an enforced company.
- Audit command accurately reports missing and mismatched configurations.

## Success Criteria

- Company/email matching has one reusable implementation across mutation paths.
- Existing users are not silently reassigned or locked out during migration.
- Administrators can prepare each company and explicitly activate enforcement.
- Targeted tests and full relevant auth/profile tests pass.
- `php artisan test` and `vendor/bin/pint --dirty` pass in proportion to touched
  files.

**STOP. Await phase approval before implementation.**

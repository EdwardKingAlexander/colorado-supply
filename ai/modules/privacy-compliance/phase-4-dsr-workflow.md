---
plan: privacy-compliance
phase: 4
title: Data Subject Request (DSR) Workflow
status: not_started
last_updated: 2026-07-10
depends_on: [phase-1]
checkpoint: none
---

# Phase 4 — Data Subject Request Workflow

## Goal

Build the full request lifecycle: public intake with identity verification,
a Filament work queue with statutory deadline tracking (45 days + one 45-day
extension), automated data export, anonymizing deletion that respects legal
holds, and the appeal path Colorado and other states require.

## Success Criteria

- A visitor (no account needed) can submit a request, verify via signed email
  link, and receive a completion notice.
- Filament queue shows every request with a due-date countdown, overdue
  highlighting, and one-click extension (records the reason, notifies the
  requester as required).
- Access/portability requests produce a JSON + CSV export of the person's data
  (profile, orders, quotes, activities, consent receipts).
- Deletion anonymizes personal fields but preserves `legal_hold` records
  (orders/payments/invoices) with personal identifiers stripped.
- Every state-granted right maps to a request type, including **appeal** of a
  denial.
- All actions audit-logged (integrates with the existing audit-logging module).

## Sub-Phases

### 4.1 Domain model (`model` checkpoint)
- Enums `app/Enums/DsrType.php` (access, deletion, correction, portability,
  opt_out, appeal) and `app/Enums/DsrStatus.php` (pending_verification,
  verified, in_progress, awaiting_info, completed, denied, appealed) — follow
  existing enum conventions.
- `php artisan make:model DataSubjectRequest -mf --no-interaction`:
  `id`, `type`, `status`, `email` (indexed), nullable `user_id`, `name`,
  `details` text, `verification_token` (hashed) + `verified_at`,
  `due_at`, `extended_at` + `extension_reason`, `denial_reason`,
  `parent_request_id` (appeals), `completed_at`, timestamps. No CompanyScope.
- `due_at` = verified_at + `config('privacy.dsr.response_days')`; appeal
  requests link to the denied parent.

### 4.2 Public intake + verification (`intake` checkpoint)
- `GET/POST /privacy/requests` — Inertia form (type, email, name, details);
  throttled (5/hour/IP); honeypot field.
- On submit: create `pending_verification` record, send signed verification
  mail (TTL from config). `GET /privacy/requests/verify/{signed}` marks
  verified, starts the clock, emails confirmation with reference number.
- Unverified requests auto-purge after 30 days (Phase 5 job).
- `opt_out` type short-circuits: also applies the consent opt-out from
  Phase 2 for that visitor when the browser session matches.

### 4.3 Filament queue (`queue` checkpoint)
- `DataSubjectRequestResource` (Filament v4 conventions: `Filament\Schemas`
  layout components, `Filament\Actions\Action`, `Heroicon` enum):
  - Table: type/status badges, due-in-days column (color-shifts at 10/3 days,
    red when overdue), default sort by `due_at`.
  - Actions: mark in-progress, complete, deny (requires reason → auto-sends
    denial mail with appeal instructions), extend (once; requires reason).
  - View page: requester details, linked user record, activity log section.
- Dashboard widget: open requests + nearest deadline.

### 4.4 Export & deletion services (`services` checkpoint)
- `app/Services/Privacy/UserDataExportService.php` — gathers per-email data:
  user profile, orders + items, quotes, payments (masked), shipments, CRM
  activities, SAM favorites, consent receipts → JSON + CSV zip stored on the
  `local` disk with a time-limited signed download link mailed to requester.
- `app/Services/Privacy/UserDataDeletionService.php` — anonymizes user fields
  (name → "Deleted User", email → hash-stub, phone/address nulled), detaches
  tokens/sessions, preserves `legal_hold` records with identifiers stripped,
  writes an anonymization receipt.
- Both invoked from Filament actions; deletion requires a confirm modal
  restating legal-hold behavior.

## Files to Create/Modify

| File | Action |
|---|---|
| `app/Enums/DsrType.php`, `app/Enums/DsrStatus.php` | create |
| `database/migrations/*_create_data_subject_requests_table.php` | create |
| `app/Models/DataSubjectRequest.php` + factory | create |
| `app/Http/Controllers/DataSubjectRequestController.php` | create |
| `routes/web.php` | modify |
| `resources/js/Pages/Privacy/RequestForm.vue` (+ confirmation page) | create |
| `app/Mail/` — verification, receipt, completion, denial mailables | create |
| `app/Filament/Resources/DataSubjectRequestResource.php` (+ pages) | create |
| `app/Services/Privacy/UserDataExportService.php` | create |
| `app/Services/Privacy/UserDataDeletionService.php` | create |

## Tests to Write

- `tests/Feature/Privacy/DsrIntakeTest.php` — submit, throttle, verify link
  (valid/expired), clock start, reference email (`Mail::fake()`).
- `tests/Feature/Privacy/DsrQueueTest.php` — Filament `livewire()` tests:
  list, due-date sort, complete/deny/extend actions and their side effects.
- `tests/Feature/Privacy/ExportServiceTest.php` — export contains seeded
  orders/quotes; no cross-user leakage (**critical with CompanyScope-bypassing
  admin context**).
- `tests/Feature/Privacy/DeletionServiceTest.php` — anonymization, legal-hold
  preservation, idempotency.

## Risks / Blockers

- Export/deletion touch many models; a missed relation = incomplete response.
  Mitigation: build the relation list from the Phase 1 data inventory and
  assert coverage in tests.
- Identity verification strength: email-signed-link is the accepted baseline
  for non-account holders; note for counsel whether more is wanted for
  deletion of accounts with order history.
- Admin-context queries bypass CompanyScope — services must scope strictly by
  verified email/user id.

## Definition of Done

End-to-end flow demonstrated locally (submit → verify → work → complete, plus
deny → appeal); all four test files green; no new baseline failures; pint clean.

STOP. Await phase approval.

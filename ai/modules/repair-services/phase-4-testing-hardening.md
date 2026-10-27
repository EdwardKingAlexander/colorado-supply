# Phase 4 — Testing & Hardening

## Goal

Cover the new pipeline with feature tests, verify no regressions on the
existing contact-form pattern (shared `RecaptchaService`), and do a final
pass for GES-branding leakage and pint cleanliness.

## Files to Create/Modify

- `tests/Feature/RepairServiceControllerTest.php`
  - `GET /repair-services` returns `200` and renders `RepairServices/Index`
    without authentication.
  - `POST /repair-services` with valid data + faked recaptcha verification
    (`RecaptchaService` bound to a fake/mock returning `true`, matching
    however the existing contact-form tests stub it — check for an
    existing `RecaptchaService` fake/test double first) creates a
    `RepairRequest` row and dispatches both mails (`Mail::fake()` +
    `Mail::assertSent(RepairRequestSubmitted::class)` /
    `RepairRequestAutoReply::class`).
  - Missing required fields (`name`, `email`, `equipment_type`,
    `model_number`, `issue_description`) → `422` with field-specific
    errors; optional fields (`phone`, `company`, `manufacturer`,
    `serial_number`, `urgency`) can be omitted successfully.
  - Honeypot (`website` filled) → `201` but no `RepairRequest` row created
    and no mail sent.
  - Failed recaptcha verification → `422` with `captcha_token` error, no
    row created.
- `tests/Feature/Filament/RepairRequestResourceTest.php`
  - Admin can list repair requests (`livewire()->assertCanSeeTableRecords`)
    and mark one handled/unhandled.

## Out of Scope

- No changes to unrelated contact-form tests, unless a shared test helper
  needs to be extracted (only do this if duplication becomes obviously
  unreasonable — prefer copy-paste consistent with existing test style
  over premature abstraction).

## Final Checklist

- Re-read `RepairHero.vue`, `RepairValueProps.vue`, `RepairCategories.vue`,
  `RepairProcess.vue`, and both `Mail` classes end-to-end for any GES
  name/logo/phone/URL/brochure attribution that slipped in — must be zero.
- `php artisan test` passes (full suite, not just new tests).
- `vendor/bin/pint --dirty` clean.
- Manual browser check: submit the form once end-to-end in dev, confirm
  both emails land (log driver or Mailtrap/whatever `.env` mail driver is
  configured), confirm the row appears in the Filament admin resource.

## Success Criteria

- All new tests green; full suite has no new failures.
- No GES branding anywhere in the shipped page, mail, or admin resource.
- Feature considered complete and ready for orchestrator sign-off.

**STOP. Await phase approval.**

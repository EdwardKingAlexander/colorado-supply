# Phase 1 — Backend Foundation

## Goal

Stand up the data model, mail, controller, and routes for the repair
request pipeline, mirroring the existing `ContactMessage`/`ContactController`
pattern. No frontend yet — the page route can return a placeholder Inertia
component.

## Files to Create/Modify

- `database/migrations/xxxx_create_repair_requests_table.php`
  - `name`, `email`, `phone` (nullable), `company` (nullable),
    `equipment_type` (string), `manufacturer` (nullable),
    `model_number` (string), `serial_number` (nullable),
    `issue_description` (text), `urgency` (nullable string,
    e.g. `standard`/`rush`), `ip`, `user_agent`,
    `handled_at` (nullable datetime), `handled_by` (nullable FK to
    `users.id`), timestamps, `softDeletes()`.
- `app/Models/RepairRequest.php` — `SoftDeletes`, `$fillable` for all
  intake fields + `ip`/`user_agent`/`handled_at`/`handled_by`,
  `handledBy(): BelongsTo` (→ `User`), `getIsHandledAttribute(): bool`.
  Mirrors `app/Models/ContactMessage.php`.
- `app/Http/Requests/StoreRepairRequestRequest.php` (or inline
  `$request->validate()` in the controller, matching
  `ContactController`'s style):
  ```php
  'name' => ['required', 'string', 'max:120'],
  'email' => ['required', 'email', 'max:255'],
  'phone' => ['nullable', 'string', 'max:30'],
  'company' => ['nullable', 'string', 'max:150'],
  'equipment_type' => ['required', 'string', 'max:100'],
  'manufacturer' => ['nullable', 'string', 'max:100'],
  'model_number' => ['required', 'string', 'max:100'],
  'serial_number' => ['nullable', 'string', 'max:100'],
  'issue_description' => ['required', 'string', 'max:2000'],
  'urgency' => ['nullable', 'in:standard,rush'],
  'captcha_token' => ['required', 'string'],
  ```
- `app/Http/Controllers/RepairServiceController.php`:
  - `index()` → `Inertia::render('RepairServices/Index')` (public).
  - `store(Request $request)` → honeypot short-circuit (same
    `$request->filled('website')` pattern as `ContactController`, but see
    Phase 3 note on field naming), validate, `RecaptchaService::verify(...,
    'repair_request_form')`, create `RepairRequest`, send both mails,
    return `201` JSON `{ message }`.
- `app/Mail/RepairRequestSubmitted.php` — to sales
  (`Edward@cogovsupply.com`, matching `ContactFormSubmitted`'s
  destination), includes all intake fields.
- `app/Mail/RepairRequestAutoReply.php` — to the customer, confirms
  receipt and sets expectations (free evaluation, no cost upfront,
  someone will follow up with a quote). Mirrors `ContactAutoReply`.
- `routes/web.php`:
  ```php
  Route::get('/repair-services', [RepairServiceController::class, 'index'])->name('repair-services.index');
  Route::post('/repair-services', [RepairServiceController::class, 'store'])->name('repair-services.store');
  ```

## Out of Scope

- Filament admin resource (Phase 2).
- Actual page content/design (Phase 3) — `index()` can render a stub Vue
  component that Phase 3 replaces.
- Tests (Phase 4), though a basic smoke test is fine if trivial.

## Success Criteria

- `POST /repair-services` with valid data + a valid captcha token creates a
  `RepairRequest` row and both mails are dispatched (verify manually via
  `php artisan tinker` or `Mail::fake()` in a throwaway test).
- Honeypot-filled submissions return `201` without creating a row or
  sending mail (matches `ContactController`'s behavior).
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

# Audit Logging / Activity Trail — Overview

## Problem Statement

Nothing in this application currently records who changed what, when. For a
platform selling to government and business customers, this is a standard
expectation: procurement/security reviewers routinely ask "can you show an
audit trail for record X," and right now the honest answer is no. We need a
"who did what, when" activity log covering the record types most likely to
matter in a review or dispute: `Order`, `User`, `Customer`, `Opportunity`,
and `Vendor` (compliance documents).

## Constraints

- Use `spatie/laravel-activitylog` — it's the same publisher as
  `spatie/laravel-permission` (already in use), well-established, and does
  exactly this without inventing a bespoke solution.
- **Version pin (confirmed 2026-07-05)**: production runs PHP 8.3.
  `spatie/laravel-activitylog` v5 (released 2026-03-25) requires PHP 8.4+
  as a hard minimum, so this module pins to **`spatie/laravel-activitylog:^4.0`**
  instead — compatible with PHP 8.3 and this app's declared `^8.2`
  requirement. v4 and v5 differ in schema/API (v5 removed batch support,
  renamed some methods); all of Phase 1-2's file references below assume
  the v4 API.
- Causer resolution must work across **both** auth guards (`web` and
  `admin`) — most administrative changes happen via Filament (`admin`
  guard), but customer-initiated changes (e.g., editing their own profile)
  happen via `web`. The package's default causer resolution only checks the
  default guard; this needs explicit configuration.
- Read-only from the UI side — this is a trail, not something users edit.
- Don't log every model in the app; scope to the five explicitly named
  above. Expanding coverage later is a small, additive change once the
  pattern is proven.

## Architecture (textual)

```
Model event (created/updated/deleted) on Order/User/Customer/Opportunity/Vendor
   -> LogsActivity trait (spatie/laravel-activitylog)
   -> resolves causer via a custom resolver checking `admin` guard first,
      falling back to `web` guard (config/activitylog.php)
   -> writes an `activity_log` row: subject, causer, event, changed
      attributes (before/after), description

Admin (Filament)
   -> new read-only ActivityLogResource (CRM or Admin nav group)
      lists/filters by subject type, causer, date range
```

## Data Flow

```
Order::update(['status' => 'confirmed'])
   -> Spatie\Activitylog\Traits\LogsActivity observer fires
   -> causer resolved (Admin or User, whichever guard is authenticated)
   -> activity_log row: subject_type=Order, subject_id=.., causer_type=..,
      causer_id=.., event=updated, properties={old:{...}, attributes:{...}}
   -> visible in Filament's ActivityLogResource
```

## Risks

- **PHP version mismatch** (see Constraints) — the single biggest risk;
  must be resolved before any code is written.
- **Log volume** — five models with full attribute-diff logging could grow
  the `activity_log` table quickly on a busy Order-heavy system. Phase 2
  should scope logged attributes deliberately (e.g., skip `updated_at`
  churn) rather than logging every column blindly.
- **Multi-guard causer resolution** is not the package's default behavior
  and needs explicit, tested configuration — a naive setup would silently
  log every admin-driven change as "System"/null causer, defeating the
  entire point.
- **Performance** — logging on every model event adds a write; should be
  negligible for this app's scale, but Phase 4 should confirm no N+1 or
  slowdown on bulk operations (e.g., Filament bulk actions on Orders).

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Package install, version decision, multi-guard causer config | `phase-1-foundation.md` |
| 2 | Instrument Order, User, Customer, Opportunity, Vendor | `phase-2-model-instrumentation.md` |
| 3 | Filament read-only Activity Log resource | `phase-3-admin-ui.md` |
| 4 | Testing & hardening | `phase-4-testing-hardening.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per `PROJECT_CANNON.md`.

**STOP. Await approval before any implementation begins.**

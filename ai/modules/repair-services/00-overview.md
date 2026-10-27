# Repair Services Page — Overview

## Problem Statement

Colorado Supply & Procurement is a distributor for Global Electronic
Services' (GES) industrial repair program: repair/sales/service of
industrial electronics, servo motors, AC & DC motors, and hydraulics &
pneumatics. Customers get up to 50% savings vs. buying new, a 2-year
in-service warranty, no freight cost, and no upfront cost — if a unit can't
be repaired, Colorado Supply sources and quotes an alternative.

There is currently no page describing this offering. We need a public
marketing landing page at `/repair-services`, on-brand with the existing
site (same layout, colors, header/footer), with a lead-capture form that
routes repair requests to sales — **branded entirely as Colorado Supply's
own service**, not GES's. No GES name, logo, or contact info appears
anywhere on the page or in outbound mail; Colorado Supply is the customer's
single point of contact end-to-end.

## Source Material

`ai/modules/services/2026 Brochure.pdf` — a GES-branded brochure used only
as **content inspiration** (service categories, value props, process
outline). It must not be linked, embedded, or attributed on the page.

## Constraints

- Public route, no auth required (matches `Welcome`, unlike the
  auth-gated `/store`).
- Reuse existing patterns, don't invent new ones:
  - `Contact.vue` + `ContactController` + `RecaptchaService` for the
    form/anti-spam/mail pipeline (name, email, phone, honeypot, reCAPTCHA
    v3 action token).
  - `ContactMessage` + `ContactMessageResource` (Filament) for the
    "store the lead, let admin mark it handled" pattern.
  - `AppLayout`, `Navigation.vue`, `Footer.vue`, Tailwind classes/colors
    already used on `Welcome.vue` (`amber-600` accents, `primary-700`
    hero gradient, `gray-900` dark backgrounds).
- Form fields mirror a repair intake, but only `name`, `email`,
  `equipment_type`, `model_number`, and `issue_description` are required.
  `phone`, `company`, `manufacturer`, `serial_number`, and `urgency` are
  optional — brochure confirms serial numbers aren't always available,
  model numbers should be.
- No file/photo upload in this pass (out of scope; can be a future
  enhancement).
- No new dependencies (reCAPTCHA, mail, Filament already in place).
- `Welcome.vue` gets a short teaser section (new `RepairServicesTeaser.vue`)
  linking to `/repair-services`, in addition to the nav link.
- Repair-lead notifications route to the same address as the general
  contact form (`Edward@cogovsupply.com`) — no new inbox needed.

## Architecture (textual)

```
Customer -> GET /repair-services
   -> RepairServiceController@index
   -> Inertia::render('RepairServices/Index')  [public page, on-brand]

Customer -> fills RepairRequestForm.vue -> POST /repair-services
   -> RepairServiceController@store
   -> StoreRepairRequestRequest (validation)
   -> RecaptchaService::verify(..., 'repair_request_form')
   -> RepairRequest::create(...)
   -> Mail: RepairRequestSubmitted -> sales (Edward@cogovsupply.com)
   -> Mail: RepairRequestAutoReply -> customer
   -> 201 JSON { message }

Admin (Filament)
   -> RepairRequestResource (CRM group) — list/view, mark handled/unhandled
      (mirrors ContactMessageResource)
```

## Data Flow

```
RepairRequestForm.vue (axios POST, honeypot + reCAPTCHA v3)
   -> /repair-services (POST)
   -> repair_requests table row
   -> two Mail notifications (sales + customer auto-reply)
```

## Risks

- **GES branding leakage** — brochure imagery/copy is GES-branded; every
  phase must re-verify no GES name/logo/URL/phone ends up in committed
  copy or assets.
- **Duplicate honeypot field name** — `Contact.vue`'s honeypot is a
  `company` field, but the repair form has a *real* `company` field.
  Phase 3 must use a distinct hidden honeypot field name (e.g. `website`)
  to avoid colliding with the legitimate `company` input.
- **Mail deliverability in tests** — follow existing `Mail::fake()` pattern
  from contact-form tests (if any) so Phase 4 tests don't send real email.
- **Scope creep** — brochure has ~15 pages of manufacturer lists/process
  detail; the page should summarize (categories + value props + simplified
  process), not reproduce the whole brochure.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Backend foundation (migration, model, mail, controller, routes) | `phase-1-backend-foundation.md` |
| 2 | Filament admin visibility | `phase-2-admin-visibility.md` |
| 3 | Frontend page & repair request form | `phase-3-frontend-page.md` |
| 4 | Testing & hardening | `phase-4-testing-hardening.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per `PROJECT_CANNON.md`.

**STOP. Await approval before any implementation begins.**

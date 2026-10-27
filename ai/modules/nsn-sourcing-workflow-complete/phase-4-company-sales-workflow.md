---
plan: nsn-sourcing-workflow
phase: 4
title: Company verification and sales confirmation tracking
status: completed
last_updated: 2026-10-10
depends_on: [phase-3]
checkpoint: 4.5-showing-answers-and-linking
---

# Phase 4 - Add company verification and sales confirmation tracking

- Lifecycle: COMPLETE (validated by user 2026-10-10)
- Objective: Take a PUB LOG reference (NSN/part/CAGE) through CAGE verification, a sales inquiry and an evidence-backed answer about current production and distributors.
- Dependencies: Phase 3 complete and validated (done 2026-10-10, commit a472e21d).
- Entry criteria: Lookup exposes traceable reference pairs (done); tenant/guard design settled (below).

## Decisions (user, 2026-10-10)

- **Ownership: home company.** Cases belong to a company. Admins act for the home company automatically, using the same rule as DIBBS tracking (`auth user company_id ?? first company`). There is no company picker, and a posted company_id is never trusted. If web users get access later, they are limited to their own company. Users without a company are denied.
- **Evidence: text plus file uploads.** Reply text or call notes, an optional source URL, and optional files (PDF, PNG/JPG/WebP, .eml, .msg; 10 MB each) on the private `local` disk. Files download only through an authorized admin route, never a public URL. A SHA-256 hash is stored for each file.

## Scope re-check (2026-10-10)

Still accurate. Changes since the original plan:
- Phase 3 shipped the lookup with "Open CAGE Search" and curated-part review. Phase 4 adds "Start sourcing case" on each reference row and shows the case status there.
- The catalog may live on a separate connection (`PUBLOG_DB_CONNECTION`), so cases store `pub_log_import_id`, `nsn_reference_id`, NIIN, part number and CAGE as plain values with no cross-database foreign keys. Case data stays readable if a release is later pruned. The future `publog:prune` (phase 2, undecided) must keep releases cited by cases.
- The DIBBS module (uncommitted) has a similar company-tracking pattern. Phase 4 copies the approach but does not depend on DIBBS code.

## Data model (all private tables carry company_id; children inherit ownership from the case)

| Table | Key fields |
|---|---|
| `nsn_sourcing_cases` | company_id, pub_log_import_id, nsn_reference_id, nsn, niin, part_number, cage_code, item_name snapshot, mil_spec_part_id (nullable), status (open, verifying, contacting, awaiting_response, answered, closed), production_state (unknown, current, discontinued, replaced), opened_by (admin/user type + id), closed_at, close_reason. One open case per company + NIIN + part + CAGE. |
| `nsn_cage_verifications` | case_id, outcome (verified, conflicting, inactive, unresolved), verified name/address/status, source URL, notes, verified_at, actor. Append-only; the latest one is current. Never changes `cage_entities`. |
| `nsn_sales_contacts` | case_id, website, contact name, role as stated by the source, email, phone, source URL, source date, optional vendor_contact_id / supplier_id (explicit link only). |
| `nsn_sourcing_inquiries` | case_id, sales_contact_id, channel (email, phone), body (editable draft), drafted_at, contacted_at (set only by "Mark contacted"), follow_up_on, actor. |
| `nsn_sourcing_responses` | case_id, inquiry_id (nullable), received_at, channel, text/notes, source URL, reported production_state, replacement NSN/part, MOQ, lead time, sells direct (yes/no/unknown), distributors (JSON list: name, CAGE, contact, authorization evidence), supersedes_response_id, actor. Append-only: no edit or delete. |
| `nsn_sourcing_files` | response_id, path (private disk), original name, MIME type, size, sha256, uploaded_by. |

Actor columns are type + id because Admin and User have separate ID spaces. All writes are activity-logged under log name `nsn-sourcing`.

## Sub-phases

### 4.1 Data model, ownership and policies
- Migrations and models for the tables above, factories, a `BelongsToSourcingCompany` scope/trait, and `NsnSourcingCasePolicy` (child records authorize through the case).
- Done when: cross-company reads and writes are denied in tests for both guards, a user without a company is denied, and a posted company_id is ignored.

### 4.2 Cases from the lookup and CAGE verification
- "Start sourcing case" on lookup reference rows. It opens or reuses the open case for that NIIN/part/CAGE and records the release and reference used.
- New admin resource **NSN Sourcing Cases**: list (status, production state, follow-up due, CAGE, latest answer date) and a case page with the catalog snapshot clearly labeled "PUB LOG snapshot - not verified".
- "Record CAGE verification" action, with Open CAGE Search beside it. Conflicting, inactive and unresolved outcomes are shown prominently and block "verified" wording.
- Done when: a case opens from a real lookup row, a verification is recorded with its source and date, and the imported snapshot is never relabeled as verified.

### 4.3 Sales contacts, inquiry draft and handoff
- "Add sales contact" (source URL and date required for email/phone). Optional explicit link to an existing VendorContact or Supplier. Nothing is copied into shared records automatically, and nothing guesses an email address.
- "Draft inquiry" from a template: exact NSN and part number, whether it is still produced, replacement, direct sales, MOQ, lead time, authorized distributors. The text is editable, with **Copy** and **Open in email app** (`mailto:` link; the user's own mail client sends it). The app never sends mail.
- "Mark contacted" (date and channel) is separate from drafting. Follow-up date, plus a "Follow-up due" list filter.
- Done when: tests assert `Mail::assertNothingSent()` and `Notification::assertNothingSent()` across the flow, and drafted vs contacted status is distinct.

### 4.4 Responses, evidence and production state
- "Record response": reply text or notes required, received date required, optional files and URL, reported production state, replacement, MOQ, lead time, direct sales, distributors.
- The case's production state changes only from a recorded response. Without a response it stays **Unknown**. A newer response must explicitly supersede an older one. Conflicting answers stay visible in the history.
- Private file download route: admin-only, policy-checked, streamed, with stored MIME type and `Content-Disposition: attachment`.
- Done when: unknown production is never displayed as current, conflicting responses are both visible, an upload outside the allowed types/size is rejected, and another company's file returns 403/404.

### 4.5 Showing answers and linking to curated parts
- The lookup reference row and the case list show the latest answer and its date (for example "Current - confirmed by email 2026-10-12"), or "No confirmation".
- "Add supplier to curated part" (explicit, on an answered case): pick or create a shared Supplier (name, CAGE, website only) and attach it to the curated part for that NSN with the supplier part number. If no curated part exists, the existing review flow from phase 3 is used. Private contact details are never copied.
- Done when: an end-to-end test runs lookup, case, verification, contact, inquiry, contacted, response with file, and supplier link. A desktop and 390px browser check on the full local catalog passes.

## Files to create or modify

- `database/migrations/*_create_nsn_sourcing_tables.php`; `app/Models/NsnSourcing*` and factories; `app/Policies/NsnSourcingCasePolicy.php`
- `app/Services/NsnSourcing/` (case opening, inquiry template, response recording, supplier linking)
- `app/Filament/Resources/NsnSourcingCases/` (list + view page + actions); a download route/controller for files
- Modify: `app/Filament/Pages/FetchNsnData.php` and `resources/views/filament/pages/fetch-nsn-data.blade.php` (start case and latest answer on reference rows)
- Tests: `tests/Feature/NsnSourcing/*`

## Verification

Tests for cross-company access on both guards, policy enforcement, evidence requirements, multiple contacts and distributor referrals, conflicts and supersession, drafted vs contacted, unknown production, file type/size/ownership, and no mail or notification sent. One end-to-end case on fixture data. Full suite, Pint, `git diff --check`. Browser check at desktop and 390px against the local full catalog.

## Exit criteria

An admin can move a catalog reference through CAGE verification, sales inquiry and an evidence-backed answer. Private records stay isolated by company. No production or distributor claim is shown without a dated recorded response. Verification recorded and validated by the user.

## Risks

- Stale contacts and identity ambiguity: mitigated by source and date on every contact and verification.
- Conflicting responses: both kept, with explicit supersession.
- Private data exposure: company scoping, policy checks on every action, private disk, no MCP exposure of case data.
- Uploaded files could be large or malicious: type/size limits, files only ever served as downloads, never rendered inline.
- Scope size: 5 sub-phases; checkpoint after each in this file's front matter.

## Out of scope

Automatic email sending, inbox sync, bulk outreach, stock guarantees, a new CRM, case data in the MCP tool, and web-user (customer) access UI.

## Implementation and verification (2026-10-10)

Built as planned, with these deviations:
- Case statuses simplified to open, awaiting_response, answered, closed. Status changes automatically: "Mark contacted" sets awaiting_response, the first response sets answered. Follow-up lives on the case.
- A case can show "Conflicting" when current, non-superseded responses state different production states.
- Evidence download is a Livewire action on the case page (policy plus case-ownership check, streamed as an octet-stream attachment). No new routes were added, avoiding shared files with uncommitted work.
- Ownership uses a new SourcingCompanyScope, which also constrains admins (to the home company) and returns nothing for a signed-in user without a company. The older CompanyScope does not constrain admins.
- Extra fix found during the browser check: the catalog migration now skips tables that already exist on a separate catalog connection, and its down() never drops a separately configured catalog. Before this, a fresh app DB pointed at an existing catalog DB failed to migrate, and a rollback would have dropped the 20 GB catalog. Regression test in PubLogConnectionTest.

Files: migration 2026_10_10_100000_create_nsn_sourcing_tables; models NsnSourcingCase, NsnCageVerification, NsnSalesContact, NsnSourcingInquiry, NsnSourcingResponse, NsnSourcingFile; Concerns/BelongsToSourcingCompany, Concerns/AppendOnly, Scopes/SourcingCompanyScope; NsnSourcingCasePolicy (auto-discovered); Services/NsnSourcing/{SourcingContext, NsnSourcingService, InquiryTemplate}; Filament NsnSourcingCases resource (list + view); lookup page start/status; views/filament/nsn-sourcing/response-files.blade.php; NsnSourcingCaseFactory.

Tests: NsnSourcingServiceTest (9) and NsnSourcingUiTest (6). They cover company isolation on both guards, users without a company, posted company ignored, append-only evidence, source requirements, drafted vs contacted, Mail/Notification nothing sent, unknown/current/conflicting/superseded states, file type/size/spoofed-type rejection, private storage with hash, cross-case/company download refusal, supplier link with shared facts only, and an end-to-end run through every case action. Full suite 945 passed, 5 skipped. Pint and git diff --check clean.

Browser (Playwright, installed Chrome, 1280px and 390px): scratch SQLite app DB with the full MySQL catalog (release 2026-10-01). Real lookup NSN 5310-01-134-0751 showed case status on rows; a case was started from a real reference; Draft inquiry and Mark contacted were submitted through the real modals; the seeded case rendered verification, contact, inquiry with mailto, conflicting responses, distributor and file; the evidence download returned the right name and bytes. No horizontal page overflow. The case list was made responsive (item and last answer only at 2xl, follow-up at lg, status at md, clickable rows) after the first check showed clipped columns. MySQL catalog unchanged.

STOP. Await validation.

# STATE — vendor-preferred-contacts

## Status Table

| Item | Status |
|---|---|
| Module plan | Approved |
| Phase 1 — Vendor CRUD repair + optional preferred contacts | Complete |

## Log

- **2026-07-10 (owner validated)** — Owner confirmed the repaired vendor
  creation and preferred-contact workflow worked correctly in Filament.
  Phase 1 and the module are complete.

- **2026-07-10 (phase 1 validation)** — Repaired Filament vendor creation:
  portal `user_id` is nullable for admin-managed vendors, addresses persist,
  and collision-safe slugs are generated automatically. Added optional inline
  contact create/edit/delete with name, title, email, work/mobile phones,
  notes, and a model-level single-preferred invariant; vendor list now shows
  preferred contact details. Both migrations applied locally. Validation: 7
  new VendorResource tests passed, and 21 focused vendor/compliance/store
  tests passed (136 assertions); `pint --dirty --test` and `git diff --check`
  passed. Full suite completed with the temporary 512 MB limit at the
  unchanged 71-failure baseline, with 518 passing and 5 skipped (1890
  assertions). Awaiting owner validation.

- **2026-07-10 (phase 1 approved)** — Owner approved all phases. Phase 1
  marked In Progress; implementation and validation underway.

- **2026-07-10 (plan approved)** — Owner approved starting the feature after
  `errors/errors.md` confirmed the create failure is the planned non-null
  `vendors.user_id` mismatch. Reconfirmed the single-phase scope: nullable
  portal ownership, generated slug, persisted vendor address, optional inline
  contact CRUD, and one preferred contact. Phase 1 is awaiting approval; no
  implementation started at this gate.

- **2026-07-10** — Module created after owner reported a 500 on Filament's
  add-vendor feature and requested optional preferred-contact CRUD with
  personal contact information. Cleared application/Filament caches and ran
  the two pending migrations, resolving the active missing-notifications-table
  and compiled-view-lock 500 conditions. Vendor code audit found missing
  required `user_id`/`slug` inputs and a non-persisted address field. Planned
  nullable portal ownership, model-generated slug, persisted address, and
  inline one-to-many contacts with a single-preferred invariant. Awaiting plan
  approval.

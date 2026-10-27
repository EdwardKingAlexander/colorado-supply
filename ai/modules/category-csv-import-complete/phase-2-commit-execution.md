# Phase 2 — Transactional Commit / Execution

## Goal

Take a valid `CategoryImportPlan` from Phase 1 and persist it: create the
`CREATE` nodes in dependency order, reuse the `REUSE` nodes, all inside a single
DB transaction so any failure rolls the entire import back. Must be idempotent
(re-running the same CSV creates nothing new).

## Files to Create/Modify

- `app/Services/Categories/CategoryImportCommitter.php`
  - `commit(CategoryImportPlan $plan): CategoryImportResult`
  - Refuses to commit an invalid plan (`$plan->isValid === false`) — throws /
    returns a guard result so the UI never commits a plan with row errors.
  - Wraps work in `DB::transaction(...)`.
  - Iterates nodes **top-down** (roots first): for `REUSE` resolve the existing
    `Category`; for `CREATE` call `Category::create([...])` wiring `parent_id`
    from the already-persisted ancestor. Records created/reused counts.
  - Returns a `CategoryImportResult` DTO: `createdCount`, `reusedCount`,
    optionally the created ids.
- `app/Services/Categories/CategoryImportResult.php` (readonly DTO) — commit
  outcome counts for the UI notification.

## Out of Scope

- No Filament UI (Phase 3) — this phase is callable in isolation and tested
  directly.
- No parsing/planning (Phase 1).

## Tests

`tests/Feature/Services/Categories/CategoryImportCommitterTest.php` (Pest):

- Commits a 3-level plan and asserts the exact `categories` rows + `parent_id`
  wiring (Abrasives -> Sanding -> Belts/Discs).
- Skip & reuse: pre-seed Abrasives + Sanding, commit a plan adding Belts under
  Sanding; asserts only Belts is created and it points at the **existing**
  Sanding id.
- Idempotency: committing the same plan/CSV twice creates nothing the second
  time.
- Rollback: force a failure mid-commit (e.g. a node with a slug that violates
  the unique index despite planning, simulated) and assert **no** partial rows
  remain (`categories` count unchanged).
- Guard: committing an invalid plan (has errors) does not write and surfaces a
  clear error/exception.

## Success Criteria

- Commit is transactional and idempotent; verified by the rollback and
  double-commit tests.
- All Phase 2 tests pass; `php artisan test --filter=CategoryImportCommitter`
  green.
- `vendor/bin/pint --dirty` clean on touched files.

**STOP. Await phase approval before implementation.**

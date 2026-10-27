# Phase 1 — CSV Parser & Tree Planner (pure logic)

## Goal

Turn raw CSV rows into a validated, order-independent **import plan** with no
database writes. This is the heart of the feature: hierarchy resolution,
existing-category matching (skip & reuse), slug generation, and per-row
diagnostics. UI and commit come in later phases.

## Files to Create

- `app/Services/Categories/CategoryImportPlanner.php`
  - `plan(iterable $rows): CategoryImportPlan`
  - Accepts already-parsed rows (array/collection of associative rows keyed by
    normalized header). Reading the file itself belongs to Phase 3 — the
    planner takes plain data so it is trivially unit-testable.
  - Responsibilities:
    - Normalize/validate headers (`name` required; `parent`, `description`,
      `image` optional). Unknown headers ignored.
    - Trim all cells; treat empty `parent` as top-level.
    - Build an in-memory forest keyed by **full path** (`root/.../name`, using
      case-insensitive name matching but preserving the first-seen casing).
    - Resolve `parent` references by name **order-independently** (multi-pass /
      topological), so parents may appear after children in the file.
    - Match each node against existing DB categories by `(parent_id, name)`
      walking down from the roots; existing => `REUSE`, else `CREATE`.
    - Allocate a unique slug for each `CREATE` node: `Str::slug($name)`, then
      `-2`, `-3`, ... checking both `categories.slug` in the DB **and** slugs
      already allocated within this plan.
    - Diagnose per row: blank name, unknown parent, ambiguous parent (name
      matches >1 node), duplicate row (same path twice), and cycle detection.
- `app/Services/Categories/CategoryImportPlan.php` (readonly DTO)
  - Holds: ordered `nodes` (each with resolved path, name, slug, description,
    image, parent-path, action `CREATE|REUSE`), `errors` (row number + message
    + raw row), and derived counts (`newCount`, `reuseCount`, `errorCount`,
    `isValid`).
- `app/Services/Categories/CategoryImportNode.php` (readonly DTO)
  - One resolved node; carries its action and, after commit, may hold the
    persisted model id (id assignment happens in Phase 2).

## Out of Scope

- No file reading / `maatwebsite` usage (Phase 3).
- No DB writes / `Category::create` (Phase 2).
- No Filament UI (Phase 3).

## Tests

`tests/Unit/Services/Categories/CategoryImportPlannerTest.php` (Pest):

- Builds a 3-level tree from unordered rows (child before parent) correctly.
- Marks an existing top-level + its existing child as `REUSE`, new leaf as
  `CREATE` (skip & reuse semantics), seeding real `Category` records.
- Generates unique slugs when two nodes under different parents share a name
  (auto-suffix), avoiding collision with an existing DB slug.
- Flags blank `name` with the correct row number.
- Flags an unknown / non-existent parent reference.
- Flags an ambiguous parent (name matches two nodes).
- Detects a cycle (A parent of B, B parent of A) without infinite loop.
- `isValid` is false when any error exists; counts are accurate.

## Success Criteria

- Planner is deterministic and performs **zero writes** (assertable: DB row
  count unchanged after `plan()`).
- All Phase 1 tests pass; `php artisan test --filter=CategoryImportPlanner`
  green.
- `vendor/bin/pint --dirty` clean on touched files.

**STOP. Await phase approval before implementation.**

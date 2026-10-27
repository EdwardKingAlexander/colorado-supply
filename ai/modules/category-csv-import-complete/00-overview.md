# Category CSV Import — Overview

## Problem Statement

Categories (`App\Models\Category`) form a self-referential tree
(`parent_id` -> `categories`) with `name`, `slug`, `description`, `image`.
Building deep hierarchies by hand in Filament is tedious — e.g. **Abrasives**
has many subcategories, and those have their own subcategories. Admins need a
way to **bulk-create nested categories from a single CSV upload**.

The `maatwebsite/excel` (v3.1) package is already installed and will be used to
read uploaded files. Categories are **global** (no `CompanyScope`), so no
multi-tenancy handling is required.

## CSV Format (decided)

One row per category. The hierarchy is expressed with a **parent-reference
column** (parent named by its `name`):

```
name,parent,description
Abrasives,,Top level
Sanding,Abrasives,
Belts,Sanding,
Discs,Sanding,
Grinding,Abrasives,
Wheels,Grinding,
```

- `name` (required) — the category name.
- `parent` (optional) — the `name` of the parent category. Blank = top level.
- `description` (optional) — passthrough text.
- `image` (optional, low priority) — passthrough string path/URL only; no file
  upload is performed by the importer.

Row order is **not** required to be parent-before-child; the planner resolves
the tree order-independently.

## Behavioral Decisions (decided)

- **Existing categories: skip & reuse.** A category that already exists (same
  `name` under the same resolved parent) is left untouched and reused as a
  parent for its children. Only genuinely new categories are created. The
  import is safe to re-run (idempotent).
- **Dry-run preview then confirm.** Upload -> parse + plan -> show a summary
  (`N new`, `M existing/reused`, per-row errors with row numbers) -> admin
  clicks **Confirm** to commit. The commit runs inside a single DB
  transaction; any failure rolls the whole import back.

## Architecture (textual)

```
Filament: CategoryResource -> "Import CSV" header action / ImportCategories page
   -> FileUpload (.csv / .xlsx)
   -> Excel::toArray()/toCollection() -> raw rows
   -> CategoryImportPlanner::plan(rows): builds an in-memory forest
        - normalizes headers, trims cells
        - resolves each row to a full path (root/.../name), order-independent
        - matches nodes against existing DB categories by (parent_id, name)
        - marks each node: CREATE | REUSE | ERROR(reason)
        - generates a unique slug per new node (auto-suffix on collision)
        - detects: blank name, ambiguous parent, unknown parent, cycles,
          duplicate rows
   -> returns CategoryImportPlan (counts + ordered node list + row diagnostics)
   -> UI renders preview (summary + error rows)
   -> admin confirms
   -> CategoryImportCommitter::commit(plan) inside DB::transaction:
        creates CREATE nodes in dependency (top-down) order, reuses REUSE nodes
   -> results notification (created / reused / skipped counts)
```

## Data Flow

```
CSV rows
  -> Planner (pure, no DB writes)
       -> CategoryImportPlan { nodes[], newCount, reuseCount, errors[] }
  -> [dry-run preview shown to admin]
  -> Committer (transactional)
       -> Category::create() for each new node, parent_id wired from
          already-persisted ancestors; reuse existing for REUSE nodes
  -> summary
```

## Slug Strategy & Risks

- **Global unique `slug` constraint** — `categories.slug` is globally `unique`.
  Two same-named children under different parents (e.g. two "Discs") would
  collide. **Mitigation (chosen): auto-suffix** on collision
  (`Str::slug($name)`, then `-2`, `-3`, ... checking both the DB and slugs
  already allocated in the current plan). Keeps the schema and existing store
  routing untouched.
  - *Rejected alternative:* migrate to a composite `unique(parent_id, slug)`.
    More "correct" for a tree but changes routing/lookup assumptions across the
    store and API — out of scope unless requested.
- **Ambiguous parent names** — since `parent` is matched by name, a parent name
  that matches more than one node (in the CSV or DB) is flagged as a **row
  error** in the preview rather than guessed. Admins fix the CSV and re-upload.
- **Large files** — planner is O(rows); reading is bounded by `maatwebsite`'s
  memory. A max row count / file size guard is added in the UI phase.
- **Partial failure** — the whole commit is wrapped in `DB::transaction`, so a
  mid-import error rolls everything back; the preview step already surfaces
  most problems before commit.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | CSV parser & tree planner (pure logic) | `phase-1-parser-planner.md` |
| 2 | Transactional commit / execution | `phase-2-commit-execution.md` |
| 3 | Filament upload UI (preview -> confirm) + sample template | `phase-3-filament-ui.md` |

Status tracking lives in `STATE.md`. Do not begin a phase until the prior
phase is marked **Complete** and this plan (or the specific phase) has been
approved per the project workflow.

**STOP. Await plan approval before any implementation begins.**

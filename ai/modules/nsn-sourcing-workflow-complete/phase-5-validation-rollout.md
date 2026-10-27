---
plan: nsn-sourcing-workflow
phase: 5
title: Single-copy catalog, validation and production runbook
status: completed
last_updated: 2026-10-10
depends_on: [phase-2, phase-3, phase-4]
checkpoint: 5.5-runbook
---

# Phase 5 - Single-copy catalog, end-to-end validation and production runbook

- Lifecycle: COMPLETE (validated by user 2026-10-10)
- Objective: Keep exactly one PUB LOG copy in production (~14 GB), prove the whole workflow end to end on the full catalog, and hand over a step-by-step production runbook. Deploying is a separate action you run; this phase prepares it.
- Dependencies: Phases 3 and 4 complete. This phase also closes phase 2's deferred production gates (storage and retention), using the decisions below.

## Decisions (2026-10-10)

The user asked for one copy and delegated the details ("you do what's best").

- **One copy.** Each monthly release loads beside the current one, switches over after its checks pass, and the old copy is deleted. Lookups stay up during the import, and a failed import leaves the current copy working. There is no multi-release rollback; recovery is re-importing the month's archives.
- **Drop the status and replacement history table** (`nsn_catalog_relationships`, from V_FLIS_STANDARDIZATION and V_FLIS_CANCELLED_NIIN; 6.3 GB, 30% of a release). It only feeds the collapsed "Catalog status and replacement context" section. Item records keep their CANCELLED_NIIN field, and sourcing responses record replacements. One copy is about 14 GB (references 6.1, items 4.6, CAGE 3.4 GB).
- **No extra volume.** Steady about 14 GB; peak about 28 GB during the monthly import, plus temporary files. That should fit the 50 GB server if roughly 35 GB is free. The runbook checks this first and stops if not.
- **Sourcing cases do not keep old releases.** Cases already store their own NSN, part, CAGE, item name and release date (phase 4).
- Supersedes the phase 2 proposal "keep active + previous + case-pinned". Not approved; replaced by the above.

## Why the import needs a code change

MySQL does not return disk space when rows are deleted: the table file stays at its largest size and the space is only reused. Today every release shares one set of tables (keyed by import_id), so "delete the old copy" would leave the disk at about 28 GB permanently. To really keep one copy, a new release loads into **fresh tables**, they are **swapped in** with an atomic rename, and the **old tables are dropped**, which frees the space immediately.

MySQL 8 also writes a binary log of every change by default, kept for 30 days. A 14 GB import would write roughly another 14 GB of binlog. The runbook covers keeping the binlog from filling the disk.

## Sub-phases

### 5.1 Drop the history table from imports
- Config `nsn_lookup.import_relationships` (default false). When false, the importer skips V_FLIS_STANDARDIZATION and V_FLIS_CANCELLED_NIIN, validation and counts ignore them, and the lookup omits the history section and its paging (the item's own cancelled-NIIN field stays).
- Done when: a fixture import with the flag off has no relationship rows and activates; with the flag on, behavior is unchanged; lookup tests pass both ways.

### 5.2 Fresh tables, swap and drop
- The importer writes a new release into staging tables (`*_next`). Activation validates them, then in one step renames current to `*_old` and next to live, drops `*_old`, and keeps the `pub_log_imports` history row (marked `replaced`, data removed).
- A failed or abandoned import leaves the live tables untouched; `publog:import` on restart clears leftover `*_next` tables. The free-space check uses single-copy math (one release plus 25%) and also reports MySQL's binlog setting when it can read it.
- `publog:status` shows live size, last import and whether staging tables exist. `publog:prune` is removed (nothing to prune), and `publog:activate --rollback` is retired with a clear message.
- Works on both the default connection and the separate `publog` connection. Tests run on SQLite (rename and drop supported).
- Done when: tests prove two consecutive imports leave exactly one copy, a failed second import keeps the first live and readable, a restart cleans staging, and lookups during an import read the live copy. A local MySQL run shows disk returned after the swap.

### 5.3 Keep cases and lookups correct across releases
- Opening a case and showing case status on lookup rows keep working after a new release replaces the one a case came from. The case page already shows its own snapshot. The stored `nsn_reference_id` becomes historical, which the case page will say.
- Done when: tests import release A, open a case, import release B, and confirm the case page, the lookup row status and "Start sourcing case" all behave.

### 5.4 Full-catalog acceptance (local MySQL)
- Re-import the real 2026-10-01 release locally through the new single-copy path (long-running, started with Start-Process; the benchmark database keeps serving until the swap).
- Measure the final size (expected ~14 GB), peak disk during import, duration, peak memory, and lookup latency for NSN, NIIN, part and CAGE.
- Re-run the phase 3 and phase 4 browser checks at 1280 and 390 px on the result.
- Write `acceptance-report.md` with the numbers, screenshots and known limitations.
- Done when: the report exists, size and peak match the single-copy budget, and the full suite, Pint and `git diff --check` are clean.

### 5.5 Production runbook (you run it; nothing is deployed by Claude)
`RUNBOOK.md` in this module, written for the Forge server (MySQL 8, 2 GB RAM, 50 GB disk):
1. Pre-checks: `df -h` (need about 35 GB free, otherwise stop), MySQL version, binlog setting.
2. Binlog: choose and apply a safe setting (short `binlog_expire_logs_seconds`, or skip the binlog for the catalog database) with exact Forge steps, and how to undo it.
3. Create a separate catalog database and user on the same MySQL server; set `PUBLOG_DB_CONNECTION=publog` and `PUBLOG_DB_*` in the Forge environment. Deploy the code and migrate (the catalog migration creates tables on the catalog database).
4. Get the month's three archives from DLA's site in a browser (DLA blocks automated downloads), upload them to the server, then run `publog:import` (as a Forge daemon or `nohup`) and `publog:activate`.
5. Verify with `publog:status` and a few lookups. Monthly refresh steps. What to do if an import fails or the disk runs low.
6. Leaving `PUBLOG_DB_*` unset keeps the catalog on the app database (works, uses the same ~14 GB).
- Done when: the runbook has been walked through locally against MySQL (except the Forge-specific clicks) and every command in it was run once locally.

## Files to create or modify

- `config/nsn_lookup.php` (`import_relationships`); `app/Services/PubLog/{PubLogImporter, PubLogWriter, PubLogCatalog, PubLogArchives, PubLogSchema}.php`; a new `PubLogTables` helper for staging names and the swap; `app/Console/Commands/{ImportPubLog, ActivatePubLog, PubLogStatus}.php`
- `app/Services/NsnLookupService.php`, `resources/views/filament/pages/fetch-nsn-data.blade.php` (history section only when imported)
- `app/Filament/Resources/NsnSourcingCases/NsnSourcingCaseResource.php` (historical-reference note)
- Tests: `tests/Feature/PubLog/*` (single copy, swap, failure, restart, flag on/off), `tests/Feature/NsnSourcing/*` (cases across releases)
- Module docs: `acceptance-report.md`, `RUNBOOK.md`; update `phase-2-operations.md` to point at the new model

## Verification

Full `php artisan test`, `vendor/bin/pint --dirty`, `git diff --check`. The local MySQL single-copy import is measured and recorded. Browser checks at desktop and phone widths. Tests never touch the MySQL catalog (`phpunit.xml` override stays).

## Exit criteria

Production keeps one ~14 GB copy with a ~28 GB monthly peak. Two imports in a row leave one copy. A failed import never breaks the live catalog. Cases survive a release change. The acceptance report and runbook exist, and the user validates. Deployment itself is not marked done until the user runs it.

## Risks

- **Disk on the 50 GB server is tighter than expected.** The runbook stops at the pre-check. The fallback is a delete-then-load mode (peak ~14 GB, lookup offline during import); not built unless needed.
- **Binlog growth:** addressed in the runbook. If it is ignored, the disk could fill during import.
- **Import time on 2 GB RAM:** the local import took hours. The runbook runs it in the background and lookups stay on the old copy until the swap.
- **No rollback to last month:** recovery is re-importing. The month's archives are kept until the next successful import.
- **DLA blocking automated downloads:** already the case; manual browser download is part of the runbook.

## Out of scope

Running the production deployment, buying storage, live supplier messages, and FSC-limited catalogs (possible later if 14 GB is still too much).

## Implementation and verification (2026-10-10)

- **5.1** `nsn_lookup.import_relationships` (env `PUBLOG_IMPORT_RELATIONSHIPS`, default off). `PubLogSchema::members()` returns imported members; `allMembers()` is the archive contract.
- **5.2** `PubLogTables`: staging `*_next` tables with per-import index and foreign-key names (`pl_i{id}_*`, because SQLite index names and MySQL FK names are database-wide and survive renames). Swap is one MySQL `RENAME TABLE` (or a SQLite transaction), then the old tables are dropped. One import owns staging (`manifest.staged`); starting or resuming another abandons it. Lost staging restarts the import. An activation interrupted after the swap completes on retry. The previous import is marked `replaced` and its owned archives deleted (never files outside `nsn_lookup.directory`). `--rollback` is retired; older releases cannot replace newer ones.
- **5.3** Cases match lookup rows by NIIN, part and CAGE values, so they carry across releases. The case page notes "Since replaced by a newer release".
- **Additions:** `publog:import --reimport` (rebuild the live release from the same files), `publog:discard-staging` (free staged space now), `PUBLOG_SKIP_BINLOG` (session-only `sql_log_bin=0`; needs SESSION_VARIABLES_ADMIN), `publog:status` now reports connection, staging, history flag, binlog and `--sizes` (fresh statistics).
- **5.4** Full local re-import: ~1 h 53 min, peak 435 MB, disk 19.25 to 32.21 (peak) to **12.96 GB**; lookups 4–10 ms. See [acceptance-report.md](acceptance-report.md).
- **5.5** [RUNBOOK.md](RUNBOOK.md); every command was run locally. phase-2-operations.md is marked partly superseded.
- Tests: PubLogSingleCopyTest (12) plus updated catalog tests. MySQL smoke with 3 consecutive releases. Full suite 1,020 passed, 5 skipped. Pint and diff check clean. Browser checks at 1280 and 390 px on the new copy.

STOP. Await validation.

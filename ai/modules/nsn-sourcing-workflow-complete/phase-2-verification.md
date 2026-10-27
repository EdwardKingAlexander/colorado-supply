# Phase 2 verification record

Date: 2026-10-04, local Windows / PHP 8.4 / MySQL 8.0.46. No production deployment or import.

## Implemented and checked

Additive versioned catalog migration and models, trusted-source download/check commands, manual ZIP import, per-member encoding/header/CRC checks, SHA-256 archive identity, bounded CSV records and 500-row transactions, committed byte-offset checkpoints, shared writer lock, validation, compare-and-swap activation, rollback, retained versions, history and staleness status. Existing curated records and NSN screen remain unchanged.

The importer uses a dedicated checkpointed CLI process instead of the proposed ordinary queue job. This avoids the existing short queue reservation timeout; `--max-seconds` yields between batches. A daily opt-in metadata check discovers releases without downloading or activating them. See [operations](phase-2-operations.md).

- Full suite: 821 passed, 5 skipped, 3,134 assertions, 144.94 seconds. Evidence: phase-2-full-suite.txt.
- Focused suite after the final cleanup recovery test: 26 passed, 120 assertions. Evidence: phase-2-focused-tests.txt.
- `php vendor/bin/pint --dirty`: passed; unrelated files were not reformatted. `git diff --check`: passed.
- Real MySQL fixture import, repeat preparation, activation, rollback and NIIN/reference join assertions passed. Evidence: mysql-verification.json.
- Indexed exact NSN, NIIN, part and CAGE sample queries all used their intended indexes; warm local p95 was below 0.25 ms. This is not the full-catalog/production latency acceptance test.
- Actual archive importer committed 3,653,500 item rows in 182.46 seconds, then yielded. Separate invocation resumed at row 3,654,000 and reached 3,739,000 in 5.02 seconds. PHP peak 56 MiB under a 128 MiB limit; no activation. Evidence: mysql-import-first-run.json, mysql-import-benchmark.json, mysql-resume-progress.txt.
- Seven-member sample imported 636,515 source rows through the real Laravel parser/writer, including the entire 36,515-row P_HELP file. Exact duplicate status/definition rows were deduplicated. Peak PHP allocation 58 MiB. Evidence: mysql-sample-benchmark.json.

## Capacity finding and remaining work

The uncompressed MySQL sample extrapolates to 29.49 billion bytes per full release, or about 59 GB for two copies, before app data, original ZIPs, spooling, database overhead and safety headroom. The first-100k sample is biased and extrapolated; it is not a completed full import. It does not support deploying this schema onto the approved 50 GB disk. No additional infrastructure purchase or resize was performed.

A research-only rebuild of the disposable sample using MySQL `ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8` roughly halved measured table/index storage with unchanged row counts. This is not yet an application migration change or a validated production recommendation. Evidence: mysql-compression-probe.json. Compression trades CPU and buffer-pool usage for disk space; see [official tuning guidance](https://dev.mysql.com/doc/refman/8.0/en/innodb-compression-tuning.html).

Remaining acceptance gates: choose/verify compact storage or larger capacity, complete a full real-data MySQL import with final relationship validation, measure full-size storage and refresh allowance, and validate target-host p95/workload. Phase 2 cannot be marked COMPLETE on sample results alone. Phase 3 remains NOT_STARTED.

## 2026-10-05 continuation

User requested continuation. Adopted MySQL compressed row format for the five catalog data tables in the additive migration. Metadata/control tables stay dynamic. A pre-DDL check requires 16 KiB pages and file-per-table storage. No existing application/production database was migrated.

New MySQL compressed fixture schema passed import, activation, rollback, repeat preparation and lookup checks; information_schema confirms all five data tables are Compressed. See mysql-compressed-verification.json. Pint and diff checks pass. Full application suite passed: 899 tests, five skipped, 3,616 assertions, 270.26 seconds. Evidence: phase-2-compressed-suite.txt. The larger suite includes unrelated concurrent project additions.

The retained isolated full benchmark was converted to the same compressed row format and resumed from 3,739,000 items. Its live evidence is mysql-compressed-full-progress.txt. A separate local watcher waits for this specific import process to exit, then writes mysql-full-final-report.json with import status, full table sizes and indexed lookup timings when ready. It never activates a catalog. Phase 2 remains IN_PROGRESS until the full result is reviewed; a running process or sample result is not acceptance evidence.

Subsequent result: that compressed run failed at 11,285,500 committed items on the source's sole four-digit INC. User approved schema optimization; the current implementation no longer requires compression. See [schema optimization and current verification](schema-optimization.md). The optimized full suite passes 901 tests / 5 skipped / 3,703 assertions, and the uncompressed full import has resumed successfully beyond 13.6 million items. Check mysql-optimized-full-progress.txt and the timestamp of mysql-full-final-report.json for current completion; historical evidence above describes earlier runs.

## Full-data result (2026-10-05)

After the relationship duplicate-check fix (see schema-optimization.md), the full October release imported locally and validated `ready` at 12:36 local; it was not activated.

| Table | Stored rows | Data + index |
|---|---:|---:|
| nsn_catalog_items | 17,025,715 | 4.62 GB |
| nsn_references | 16,623,521 | 6.09 GB |
| nsn_catalog_relationships | 18,269,449 | 6.34 GB |
| cage_entities | 4,158,375 | 3.35 GB |
| pub_log_definitions | 36,200 | 0.02 GB |

Allocated table bytes 20.45 GB per release; 40.89 GB for two. Validation: 0 orphan references, 930 unresolved CAGE references (retained), 1 short INC, 1 invalid-format source CAGE per CAGE file. Warm local p95 lookups: NSN 0.26 ms, NIIN 0.31 ms, part 0.40 ms, CAGE 0.60 ms, each on its intended index. Full suite: 911 passed, 5 skipped, 3,767 assertions (kind-scoped-full-suite.txt).

Capacity: two retained releases plus original archives (~0.8 GB each), one CSV spool (up to 1.3 GB), MySQL temp/redo/binlog and 25% headroom exceed a 50 GB disk that also hosts the OS and application. Production retention policy or capacity remains a user decision; this is local evidence only.

### Capacity decision (2026-10-05)

User decision: attach a dedicated block-storage volume to the production server for MySQL data (about $10/month), keeping the full catalog schema. Rejected: lookup-only slim export, and hosting the catalog database on an off-server/home machine (availability and exposure risk).

Sizing note: with today's no-pruning policy each monthly release adds ~20.5 GB of tables plus ~0.8 GB of archives, so a 100 GB volume fills in roughly four months. Proposed retention: active + previous + case-pinned releases, removed only by an explicit operator prune command; this needs approval before implementation. Moving MySQL's data directory onto the volume is a production infrastructure step outside this phase and requires its own runbook and approval.

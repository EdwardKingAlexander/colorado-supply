# Schema optimization, 2026-10-05

User approved reducing schema storage before purchasing capacity. This remains phase 2; no UI or production changes.

## Changes

- Reference relationship codes now use named variable-length columns instead of repeated JSON keys on 16.6 million rows.
- Standardization/cancellation source fields now use named nullable columns instead of repeated JSON keys and a duplicate NIIN on 18.3 million rows. Fields absent from a member remain null; fields present but blank remain empty strings. Model accessors reconstruct each exact member's field map.
- Both tables use 32-byte binary SHA-256 hashes rather than 64-character hexadecimal strings. This retains the full hash strength while shrinking data and duplicate-detection indexes. Models expose readable hexadecimal hashes.
- Kept all exact NIIN, NSN, part-number and CAGE lookup indexes. No required index was removed simply to improve the size estimate.
- Removed the earlier compression requirement to measure schema savings independently. The migration is new and undeployed; no existing application database was altered.
- INC validation now accepts shorter numeric values and reports their count, preserving source text. Full audit of 17,025,717 item rows found exactly one four-digit code (`7419` at row 11,285,949, NIIN `011668078`), eight blanks, and no other invalid numeric/length examples. Evidence: inc-source-audit.json.

## Evidence

The same real first-100k-per-member sample, plus all P_HELP rows, through the real Laravel writer on MySQL 8.0.46:

| Table | Original sample bytes | Optimized sample bytes | Reduction |
|---|---:|---:|---:|
| References | 66,846,720 | 41,533,440 | 37.9% |
| Relationships | 110,051,328 | 63,717,376 | 42.1% |

Other tables unchanged. Full-release extrapolation decreases from 29.49 GB to 21.04 GB without compression. This remains a biased sample estimate; two releases total about 42.1 GB before app data, logs, staging and headroom. It does not establish safe deployment on 50 GB.

Focused tests: 28 passed, 207 assertions, including exact source-field/hash/JSON-serialization round trips and short-INC preservation. MySQL fixture import, activation, rollback, repeat preparation and indexed queries passed in a separate fresh optimized schema. Evidence: optimized-focused-tests.txt, mysql-optimized-sample.json, optimized-capacity-estimate.json, mysql-optimized-verification.json.

Full application suite: 901 passed, five skipped, 3,703 assertions (253.66 seconds), recorded in optimized-full-suite.txt. Pint and git diff --check passed. Full import has passed 13.6 million item records at 58 MiB PHP peak; it is still running and is not yet accepted as complete.

The failed full benchmark's reference/relationship tables were verified empty before adapting their schema. The 11,285,500 committed item records were retained because their schema and source hashes are unchanged. All benchmark tables were converted back to uncompressed Dynamic row format. Full import resumed with the source-code correction; live evidence: mysql-optimized-full-progress.txt. Remaining gates are full-data validation, actual capacity, target-host preflight and human validation.

## Full-scale index binding repair

The full run completed all 17,025,717 items, then exposed a CAGE import slowdown. PHP converts numeric-looking associative keys to integers; passing `array_keys($batch)` into the existing-record query caused numeric SQL bindings for text identities. On MySQL the comparison used only the import portion of the composite index. Native prepared EXPLAIN reproduced over 1.1 million estimated candidate rows for three integer codes, versus three indexed candidates when strings are bound (mysql-binding-explain.json).

The writer now obtains identities from the original mapped values with `array_column`, preserving strings and binary hash bytes. A regression failed before the repair and passes after it, covering both numeric CAGE and NIIN bindings. Focused suite: 29 passed, 211 assertions. The local CAGE throughput increased from hundreds of rows/s to roughly 20,000 rows/s. This is measured local import throughput, not a production guarantee.

The slow worker was interrupted using KILL QUERY only on its isolated publog_benchmark connection; the importer recorded the failure and retained its checkpoint. The corrected worker resumed successfully (PHP 42100, watcher 41396; check current process state before assuming it remains active). Live output is mysql-binding-fixed-progress.txt, and the watcher replaces mysql-full-final-report.json at exit. No production connection or application database was modified.

## Nonstandard source CAGE record

The corrected run reached the final P_CAGE row, where the official source contains `` `Q9C4 ``. A complete audit found exactly one invalid-format code in P_CAGE, one in V_CAGE_ADDRESS (same code), and zero across all 16,623,521 reference rows. All codes have five characters. Evidence: cage-code-audit.json; this is not a blanket schema-drift bypass.

Importer preserves these source characters, exposes computed validity flags (no extra database column), and checkpoints warning counts with the affected rows. Length/control-character violations still fail. Phase 3 must keep strict user-input validation and disable the normal CAGE handoff for invalid source codes. New regression verifies raw data, serialized flags and warning counts. Focused suite: 30 passed / 221 assertions. Full application suite rerunning after the change.

All 17,025,717 item source rows and 4,158,375 CAGE identity rows have now imported. Items contain two exact duplicates, correctly deduplicated to 17,025,715 stored items. The new run is processing addresses; live evidence is mysql-source-tolerant-progress.txt. Full capacity/relationship validation is still outstanding.

## Relationship duplicate-check scan (2026-10-05)

V_FLIS_STANDARDIZATION throughput fell to ~280 rows/s and kept degrading. The writer's existing-row check on `nsn_catalog_relationships` filtered `import_id` and `row_hash` but not `kind`, so MySQL could use only the `import_id` prefix of the unique `(import_id, kind, row_hash)` index (EXPLAIN key_len 8, ~2.3M estimated rows). Measured on the live table with 500 hashes: 9,141 ms without `kind`, 51 ms with it (key_len 122, range). The writer now scopes the check by the batch's kind; a regression asserts every relationship duplicate-check query binds `kind`. The old worker was interrupted with KILL QUERY on its benchmark connection only; the checkpoint survived and the corrected worker resumed at ~6,500 rows/s. Live output: mysql-kind-scoped-progress.txt.

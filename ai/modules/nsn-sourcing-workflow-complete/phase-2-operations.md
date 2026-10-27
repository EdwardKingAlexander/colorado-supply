# PUB LOG catalog operations

> **Superseded in part (2026-10-10, phase 5):** production keeps **one** catalog copy. Releases load into staging tables and are swapped in; the previous copy is dropped. There is no rollback (`--rollback` is retired) and no retention of old releases. The history table is off by default (`PUBLOG_IMPORT_RELATIONSHIPS`). For production steps use [RUNBOOK.md](RUNBOOK.md). The import, validation and source-data notes below still apply.

These commands implement the catalog backend. The existing NSN lookup screen is replaced in phase 3. Production import is gated on measured capacity; no deployment has been performed.

## Import and recovery

Install the additive catalog migration through the normal deployment process. Obtain IDENTIFICATION.zip, CAGE.zip and REFERENCE.zip from the same official DLA PUB LOG release. Do not rename CSV members or combine releases. Record the published release date.

The current migration uses normal database row format; compression is no longer required. Reference and relationship source fields occupy individual variable-length columns, with 32-byte binary SHA-256 hashes. Original strings, blanks and leading zeroes are preserved. Eloquent exposes the original `codes`/`data` maps and hexadecimal hashes for consumers; raw SQL uses the named columns. The schema has not been deployed, so these changes are contained in the additive migration. Do not resume an old development database against the new layout without migrating it or rebuilding its catalog tables.

The October source has one four-digit item-name code (`7419`, NIIN `011668078`); it is preserved exactly and counted in validation under `short_item_name_codes`. No leading zero is invented.

The full source audit also found one nonstandard CAGE value, `` `Q9C4 ``, appearing once in each CAGE identity/address file and in no reference records. The importer preserves its five source characters, counts anomalies per member in `invalid_cage_source_rows`, and exposes `code_valid=false` / `cage_code_valid=false` through models. This is not a verified CAGE identity; normal CAGE Search handoff must be disabled for it. Wrong-length/control-character identifiers still fail import. Warnings are checkpointed with their rows so restarting cannot double-count them.

```shell
php artisan publog:import /private/path/to/release --release=2026-10-01 --max-seconds=300
php artisan publog:status --json
php artisan publog:import --resume=1 --max-seconds=300
```

The importer copies checked archives into private storage, spools only the current CSV, and commits each batch together with its byte-offset checkpoint. The time budget is checked between batches; hashing, spooling and final validation can exceed it. Run from a dedicated CLI process with sufficient lifetime, not the ordinary 90-second queue. Zero permits up to 23 hours per invocation. The shared writer lease is 24 hours; after a hard crash the lease may persist until expiry. Never force-release it while another importer is alive. Multiple hosts must use the same shared cache lock backend.

An interrupted or failed import retains its committed rows and original archive checksums. Restore changed/missing archives to their original bytes before resuming. Malformed source data requires review and a corrected official release; do not edit the private copy or bypass validation. Completed CSV spool files are removed on completion or recovery. A stopped download has no import record; its private download folder may be removed manually after confirming no download process is using it.

## Review, activate and roll back

```shell
php artisan publog:activate 1 --expected-current=none
php artisan publog:activate 2 --expected-current=1
php artisan publog:activate 1 --expected-current=2 --rollback
```

Activation requires a ready, validated version and an unchanged active pointer. Importing alone never activates. The optional import `--activate` uses the pointer captured when staging began and fails if another activation intervened. Each lookup must capture the active import ID once and use it for every query.

All catalog versions and original archives are retained. No automatic pruning exists: this preserves rollback and future sourcing-case provenance. Disk usage grows with each release; capacity must include retained and case-pinned snapshots. Do not delete rows directly or run migration rollback on a live catalog. Full release validation checks committed counts, orphan NIINs, unresolved CAGE references, and abnormal shrink relative to the staging baseline. Unresolved CAGE identities are counted and retained; orphan NIIN references fail the default policy.

## Refresh checks

```shell
php artisan publog:check
php artisan publog:download
```

The daily 04:30 scheduled task checks metadata only and is disabled by default. Enabling PUBLOG_CHECK_ENABLED requires the normal configuration/deployment workflow and a working Laravel scheduler. A queue worker with a scheduler-like label is insufficient. Downloads are explicit, streamed and size-limited; transient failures retry at most three times. Access denial returns a manual browser-download instruction. No failed check/download changes the active catalog. HTTP metadata is advisory; ZIP fingerprints and source validation decide release identity. ZIP timestamp coherence is a useful mixed-release guard, not cryptographic proof of a publication cycle.

## Capacity gate

Production free space must cover the next staged database and indexes, owned archives, one expanded CSV, database temporary/redo/binlog allowance, existing retained snapshots and 25% headroom. The importer's local disk check is conservative but is not a database-server capacity measurement, especially with a remote database. MySQL measurements and target-host workload checks remain necessary. Default batches are 500 rows; PHP must stay below the source-contract 96 MiB measured allocation budget under a 128 MiB limit.

PUB LOG relationships and CAGE snapshots do not establish current production, inventory, manufacturer identity or distributor authorization. Company verification and dated sales responses remain separate phase-4 work.

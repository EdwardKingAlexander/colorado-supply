# Phase 2 - Create a versioned, searchable PUB LOG catalog

- Lifecycle: IN_PROGRESS - authorized 2026-10-04
- Objective: Create a versioned, searchable PUB LOG catalog.
- Dependencies: Phase 1 complete and validated.
- Entry criteria: Source contract, hosting budgets and phase-2 scope approved.

Phase-1 refinement for approval: initial implementation runs locally using the verified October archives. Production activation requires additional server capacity and a measured MySQL benchmark; no paid resize is included. Include one-to-many standardization/cancellation records and versioned P_HELP definitions in the initial schema. Current CAGE data uses P_CAGE and V_CAGE_ADDRESS, with no V_CAGE_STATUS_AND_TYPE member. Support per-member validated encoding and manual official-ZIP import when HTTP downloads return 403. Use bounded, checkpointed chunks compatible with queue reservation limits. See source-contract.md for verified headers and index/ownership rules.

## Scoped tasks

- [x] Add additive migrations/models for imports, catalog items, references and CAGE snapshots, indexed by version and search keys. Preserve original source values and all distinct reference rows.
- [x] Implement trusted-source download and local-file import with archive path/size checks, streaming CSV parsing, header validation, hashes, locks, bounded retries and observable progress.
- [x] Stage each version, validate counts/key relationships and reject partial/corrupt/mismatched releases before atomically switching the active version. Unresolved CAGEs remain explicit references.
- [x] Add operator import/refresh command, monthly release checks, import history and staleness reporting. Failed refresh keeps the prior snapshot; concurrent requests read a consistent version.
- [x] Define retention/rollback that preserves case-linked versions. Do not touch legacy curated part/manufacturer data.
- [ ] Resolve measured storage constraint, complete full-data MySQL validation and record budget acceptance.
- [x] Approved refinement: replaced repetitive reference/relationship JSON with lossless columns and compact hashes; compared uncompressed sample storage. All relationships, original values and lookup indexes preserved. Audited all item codes and preserved the sole four-digit INC in source record 11,285,949. See schema-optimization.md; full import remains pending.
- [ ] Obtain human phase validation after all exit criteria pass.

## Files to create or modify

New database/migrations, app/Models/PubLogImport.php, NsnCatalogItem.php, NsnReference.php, CageEntity.php; app/Services/PubLog/; app/Console/Commands/ImportPubLog.php and operator commands; config/nsn_lookup.php; narrowly scoped routes/console.php changes. Refinement: dedicated checkpointed CLI import instead of an ordinary queue job, to avoid reservation timeout conflicts.

## Verification

Fixture import tests for zeros, quoted commas, multiple references, duplicates, malformed schema, unresolved CAGE, bad archives, interrupted imports, concurrent activation and repeat import. Verify old snapshot remains queryable after failure; benchmark against phase-1 budgets. Run php artisan test and php vendor/bin/pint --dirty while preserving unrelated work.

Implementation and fixture verification pass; full-data storage acceptance is outstanding. See [verification evidence](phase-2-verification.md) and [operator runbook](phase-2-operations.md). The user's 2 GB / 50 GB upgrade is recorded. The optimized uncompressed sample projects to 42.1 GB for two releases before overhead; that remains insufficient evidence for production capacity. Compression was investigated but is not required by the current migration. The full import and local index verification continue; no production change has been made.

## Exit criteria

Tests pass; import and repeat refresh are consistent; failed imports cannot publish partial data; measured resource use fits approved budgets; evidence recorded and user validation obtained.

## Risks

Large snapshots, mixed-release archives and database locking.

## Out of scope

UI replacement, sales contact, production deployment and restricted datasets.

STOP. Await phase approval.


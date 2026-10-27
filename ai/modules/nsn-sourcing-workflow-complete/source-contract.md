# Phase 1 source and implementation contract

Observed 2026-10-04 America/Denver (2026-10-05 UTC). User approved the plan and starting work. This phase changes research artifacts only; no application tables, environments or communications were changed.

## Source access and release identity

The [official reading room](https://www.dla.mil/Information-Operations/FLIS-Data-Electronic-Reading-Room/) loaded in Chrome and listed all three selected archives as updated **2026-10-01**. Search-index content was older. Direct web/PowerShell/curl requests returned HTTP 403, while ordinary browser link clicks successfully downloaded archives to temporary files. Download-event tooling timed out despite files arriving; archive contents and CRC, not the event notification, establish success.

Browser-downloaded files were copied without modifying the originals into this module's ignored `artifacts/2026-10/` folder. `source-measurements.json` records source URLs, archive SHA-256, sizes, ZIP entry timestamps, actual headers, complete parsed row counts and malformed-row counts. Release dates, ZIP timestamps and retrieval dates are separate facts. Never infer publication date from local filesystem modification time.

Archives use the exact linked base URL:
`https://www.dla.mil/Portals/104/Documents/InformationOperations/LogisticsInformationServices/FOIA/PUBLOG/`

| Archive | Verified layout | Required members for first implementation |
|---|---|---|
| IDENTIFICATION.zip | [Identification layout](https://www.dla.mil/Portals/104/Documents/InformationOperations/LogisticsInformationServices/FOIA/PUBLOG/Identification%20FOIA%20Layout.pdf), updated 2026-09-23, both pages reviewed | P_FLIS_NSN.CSV, V_FLIS_STANDARDIZATION.CSV, V_FLIS_CANCELLED_NIIN.CSV, P_HELP.CSV |
| REFERENCE.zip | [Reference layout](https://www.dla.mil/Portals/104/Documents/InformationOperations/LogisticsInformationServices/FOIA/PUBLOG/Reference%20FOIA%20Layout.pdf), updated 2022-02-22, actual current header agrees | V_FLIS_PART.CSV |
| CAGE.zip | [CAGE layout](https://www.dla.mil/Portals/104/Documents/InformationOperations/LogisticsInformationServices/FOIA/PUBLOG/CAGE%20FOIA%20Layout.pdf), updated 2026-09-11 | P_CAGE.CSV, V_CAGE_ADDRESS.CSV |

The current CAGE release removes V_CAGE_STATUS_AND_TYPE.CSV, TYPE/CAO in P_CAGE, and older address/history fields. Company name is now one address-file field; street address has three fields. Current Identification layout omits FIIG and other deprecated fields. Require current headers by name, tolerate documented optional omissions only, and stop activation for unsupported schema changes. Do not reconstruct removed fields or rely on old PDF row-count estimates.

Automatic download is not proven viable from this environment. Phase 2 must support importing local official ZIPs first, plus a bounded HTTP downloader that reports access failures and keeps the existing snapshot. No browser-cookie copying, hidden endpoint integration or website access workaround is part of the app. An operator may download through the official website and import the files.

## Parsing and joins

CSV is comma-delimited with double-quoted fields and CRLF in observed prefixes. Native CSV parsing must handle commas, escaped quotes and embedded newlines; never split rows with a simple comma operation. Source identifiers remain strings. Current samples include leading-zero NIINs and CAGE codes.

The full-file probe uses strict decoding. P_HELP fails UTF-8 on byte 0x96 and successfully parses as Windows-1252; per-file encoding is recorded. Other files are tested independently. Phase 2 must preflight each file and choose validated UTF-8 or Windows-1252 conversion before import, reject unsupported/ambiguous decoding and never silently replace characters. Preserve original archive and byte-level checksum as provenance.

- P_FLIS_NSN fields: FSC, NIIN, INC, ITEM_NAME, SOS, END_ITEM_NAME, CANCELLED_NIIN. Canonical NSN is four-character FSC + nine-character NIIN. Index both NSN and NIIN within a version. A numeric input is never cast to an integer.
- V_FLIS_PART fields: NIIN, PART_NUMBER, CAGE_CODE, CAGE_STATUS, RNCC, RNVC, DAC, RNAAC, RNFC, RNSC, RNJC, SADC, HCC, MSDS, MEDALS. Join item by version + NIIN and company by version + CAGE_CODE. Keep all distinct source rows, including rows with identical NIIN/part/CAGE but different relationship attributes. Deduplicate only exact canonical row fingerprints.
- P_CAGE: CAGE_CODE, CAGE_STATUS, COMPANY, CITY, STATE_PROVINCE, ZIP_POSTAL_ZONE, COUNTRY.
- V_CAGE_ADDRESS: CAGE_CODE, COMPANY_NAME, STREET_ADDRESS_1, STREET_ADDRESS_2, STREET_ADDRESS_3, CITY, STATE, ZIP, COUNTRY. Preserve discrepancies between summary company and address name; do not silently overwrite.
- V_FLIS_STANDARDIZATION supplies NIIN_STAT_CD, RELATED_NSN and standardization context. It has 17,400,519 rows versus 17,025,717 item rows: preserve it as a one-to-many relation, never a last-row-wins scalar. Include status in the first import: existence in P_FLIS_NSN alone does not imply active status. Conflicting statuses must be visible. V_FLIS_CANCELLED_NIIN supplies cancellation/reference context; store its relationships as supplied, with field names intact, until direction/meaning is verified against code definitions. Do not auto-substitute a replacement part.
- P_HELP supplies CODE, LITERAL, DESCRIPTION, REGS. It contains definitions such as RNCC:3 and RNVC:2. Version these with the release; unknown codes remain visibly undecoded. Store official text as source data and use concise paraphrases in UI.

RNCC:3 identifies a design-control reference; RNCC:5 is a secondary reference. RNVC:1 needs additional identifying information; RNVC:2 identifies the item; RNVC:9 has several historical/informational uses depending on RNCC. Do not flatten these to a single approved-manufacturer flag. CAGE A is active company status, while Y describes specialized-use records with contracting/distributor caveats. Neither confirms current production of a particular part. NIIN status 4 means cancelled without replacement, and 5 means cancelled-use. Full definitions are retained in fixtures.

## Data structures and constraints

Use the public-catalog tables proposed in the overview, with these refinements:

1. `pub_log_imports`: ID, state, release label/date, manifest JSON, schema/encoding info, started/completed/activated timestamps, counts and errors. `pub_log_catalog_state` holds a single active import pointer. Activation changes only that pointer in a short transaction after all checks. Readers capture one version for an entire request.
2. `nsn_catalog_items`: import_id, NIIN, FSC, canonical NSN, INC, item name, catalog status and raw source/provenance. Unique (import_id, NIIN) only after full-import duplicate validation; conflicting duplicates fail activation rather than last-row-wins. Index (import_id, NSN). Keep cancelled identifiers and related records, clearly labeled.
3. `nsn_references`: import_id, NIIN, part_number_original, part_search_key, CAGE, all relationship fields, source row and hash. Search indexes (import_id, NIIN, id), (import_id, part_search_key, id), (import_id, CAGE, id). Preserve punctuation/internal spaces and original case. Search key only trims boundary whitespace and normalizes case; retain all collisions. Enforce byte-sensitive original identity independent of database collation.
4. `cage_entities`: import_id, CAGE, summary company/status and distinct address fields with provenance. Unique (import_id, CAGE) after duplicate validation. Do not require every reference CAGE to resolve. Orphan item joins are counted, investigated and block activation above an explicitly reviewed threshold; never silently discarded.
5. Add catalog status/relationship rows and a versioned code-definition table for the selected Identification members. Detailed technical identification and colloquial names remain optional and are not necessary for initial sourcing. No History archive is required to expose cancellation status already present here.
6. Private sourcing tables require company_id and explicit actor type/ID (Admin and User have separate ID spaces). Child tables inherit case ownership; authorization resolves the parent and never trusts a posted company_id. Case evidence retains its import/reference IDs; deletion cannot cascade away a case's cited catalog data.

## Authorization and existing models

The current runtime is Laravel 13.19.0, despite older CLAUDE.md summary text. AdminPanelProvider uses the admin guard and App\\Models\\Admin. Admin has no company_id, its Spatie guard_name is web, and canAccessPanel returns true. Users have company_id. Existing CompanyScope applies only when the default guard has a user with a company ID; otherwise it does not constrain queries. It cannot secure new private sourcing records by itself.

Baseline: keep the feature in the admin panel, require an authenticated Admin and explicit selected company for sourcing writes, record the Admin actor, and show ownership in the interface. If ordinary user access is added later, deny users without a company and enforce equality with case.company_id on every query/action. Queue jobs must carry validated case/request IDs and owner context rather than rely on ambient Auth. Do not add private sourcing fields to the existing MCP output; the MCP registration has no explicit authentication middleware in routes/ai.php. Its future lookup contract should expose public catalog data only and no unauthenticated curated-record writes.

MilSpecPart requires manufacturer_id at the database level, though its form permits null. Phase 3 should make that foreign key nullable additively, preserve existing values, and prevent deletion of a manufacturer from cascading away curated parts. Imports never write MilSpecPart. An explicit save creates/links an NSN record with an optional user-selected manufacturer/reference; never create Unknown Manufacturer to satisfy a constraint. Existing Supplier and Manufacturer records are shared. VendorContact can be explicitly referenced by an admin, but private case contacts must not be copied into shared VendorContact/Supplier fields automatically.

## Resource strategy and acceptance budgets

Local runtime: PHP 8.4.25, ZIP/PDO SQLite available, SQLite app DB, database queue, retry_after=90 seconds, memory_limit=128M. About 345 GiB local disk was free before downloads. Local measurements are not production capacity evidence.

The user confirmed DigitalOcean hosting managed by Laravel Forge and willingness to upgrade if needed. Read-only Forge inspection of colorado-supply confirmed **1 GB RAM, 1 vCPU and 25 GB SSD**, MySQL 8.0, PHP 8.3 and Ubuntu 24.04. Overview disk usage was 40.49% and memory usage 56.83% (later rounded metrics 41% and 59%). This implies approximately 14.9 GB free from displayed capacity/percentage, not an exact filesystem reading. No server settings were changed. The queue worker shown is `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`; its misleading label is Schedule:work. Neither of the two listed scheduled tasks runs Laravel's scheduler. Verify cron separately before relying on automatic refresh; the worker label is not proof of scheduler operation.

All 9 CSVs were fully parsed: **77,370,292 data records**, zero malformed rows, and all ZIP streams passed CRC checks. The three archives total **808,927,323 bytes**; their members total **5,387,830,072 bytes** uncompressed. Four core-table samples extrapolate to **12,230,746,928 bytes** in SQLite, excluding status rows and MySQL-specific overhead. First-100k samples are biased and this is an estimate, not a full database measurement. Current 25 GB disk cannot provide a safe active-plus-staged refresh allowance. Production import is blocked pending additional capacity; local phase-2 implementation is feasible. Plan for at least a 4 GB RAM / 80 GB disk class as an initial sizing candidate, then confirm a full MySQL benchmark and headroom before any paid resize or production load. A dedicated catalog database is an alternative if main-server workload requires separation.

`benchmark_sample.php` imports 100,000 real rows per selected file into separate disposable SQLite databases, in transactions of 500 rows, stores raw JSON and adds three indexes. It never boots Laravel or accesses app data. `sample-benchmark.json` records timing, index query plans and memory. Each sample used 2 MiB PHP-reported peak allocation; this excludes native SQLite/ZIP RSS and Laravel overhead. All sampled exact lookups used indexes. Small warm-cache sample latency is not a full-catalog or production guarantee.

Implementation budgets: start at 500-row batches; require measured full Laravel-worker PHP peak below 96 MiB under the existing 128 MiB limit. Keep individual queued chunks below 60 seconds and the configured reservation timeout longer than the job timeout; never run an entire full import as one ordinary 90-second-reserved job. A dedicated CLI import can checkpoint batches; a scheduler may dispatch resumable bounded work. Show progress and resume/idempotency independently of scheduler lifetime.

Lookup acceptance target: p95 under 500 ms for indexed exact NSN/NIIN/part/CAGE requests with 25-result pages against a full staged catalog on the target host, excluding browser rendering. Full-import duration is measured and reported; do not promise one based on tiny samples. Daily bounded metadata checks can discover monthly releases; a failed check must not damage active data.

Required free space before a refresh: active retained snapshots + measured staged database/index size + archive storage + database temporary/index/WAL allowance + case-pinned versions + at least 25% headroom. Use actual target DB measurements before production activation. Stream archive members rather than unpacking the entire 5+ GB dataset to disk. Archive/schema/checksum validation precedes activation.

## Verification and outstanding gate

Research probes and fixtures are reproducible evidence, not production importer implementation. Small acceptance fixtures must distinguish genuine source rows from deliberately synthetic missing-company/invalid-input cases. Exact row-count/CRC/encoding outcomes and final sample scenarios are in source-measurements.json and fixtures/README.md.

Production capacity is now verified and insufficient for safe full refresh. Proposed scope clarification: phase 2 builds and tests the importer locally against verified files and includes a MySQL benchmark; an approved capacity increase and a full target-host check remain hard production activation gates. This clarification requires review before advancing. No claim of a completed hosted import or working replacement UI is made in phase 1.

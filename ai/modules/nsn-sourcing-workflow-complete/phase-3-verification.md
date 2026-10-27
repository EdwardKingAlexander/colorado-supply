# Phase 3 implementation and verification

## Scope authorized 2026-10-09

User moved to the NSN interface while working remotely, explicitly deferring server storage and other tasks. Phase 2 local import is usable evidence; production capacity and retention gates remain open. No production data or settings changed.

## Implemented

- Shared read-only NsnLookupService searches a single activated PUB LOG release. Strict NSN/NIIN/CAGE validation, case-normalized exact part matching that preserves punctuation and leading zeros.
- Bounded reference pages and separate item status-context pages; ambiguous part/CAGE results link to full item references. Subsequent pages retain their original release and submitted query.
- Distinct invalid_input, catalog_unavailable, no_match, match and operational_failure outcomes. Staleness is a separate flag; failures do not disclose internal error text.
- Admin page shows release date, data-quality warnings, source row/code meanings, all paginated relationships and company snapshots. Nonstandard CAGE source codes are displayed but have no normal CAGE handoff.
- Read-only CAGE Search/copy handoff. No current-production, authorized-distributor, inventory or procurement-history claims.
- Explicit curated-part review opens the existing create form with validated reference data, no inferred manufacturer, no automatic save. Existing curated records link to review; compact/formatted NSN duplicates are rejected without overwriting selections.
- MCP contract version 2 accepts legacy nsn input as well as type/query and pagination. success now means a catalog match, never persistence; saved=false and mil_spec_part_id=null. Consumers relying on old auto-save behavior must migrate.
- Queue button removed. The old job class remains solely for deserializing old jobs; it fails explicitly with one attempt and instructions to use the immediate lookup. No unsupported background completion claims or ownerless saved results.

## Verification status

- Added 14 regression cases in tests/Feature/PubLog/NsnLookupTest.php: validation, zeros, exact/ambiguous parts, outcomes, paging and release isolation, anomalies, staleness, safe rendering, admin access, locked state, explicit save, preservation, retired job, status pages and MCP schema.
- Static review against installed Filament/Laravel MCP source completed, including schema signature and create-form lifecycle. All application references to fetchAndPersistNsnData, fetchViaPython, queueFetchNsnData and FetchNsnDataJob::dispatch removed. git diff --check passed.
- PHP tests and Pint NOT RUN: php is absent from PATH; access to installed C:/Users/babyd/.config/herd/bin/php84/php.exe is denied by this session's filesystem permissions. No attempt to change permissions or bypass this restriction.
- Live desktop/mobile browser verification NOT RUN because the PHP application could not be started under these permissions. Responsive layout is implemented but remains unverified.
- Previous 911-test pass predates this interface change and is not evidence for it.

## Required next checks when PHP is accessible

1. Run php artisan test tests/Feature/PubLog/NsnLookupTest.php and resolve any failures.
2. Run php artisan test and php vendor/bin/pint --dirty, then git diff --check.
3. Verify desktop/mobile on an isolated fixture database: NSN/NIIN/part/CAGE, no catalog, no match, stale/anomalous records, pagination, copy-code and explicit curated save. Do not activate or migrate the full production dataset as part of this check.
4. Record evidence and obtain user validation before phase completion.

The existing local application has no activated catalog unless explicitly configured; the new interface will truthfully show Catalog not available. Fixtures are confined to test databases and are never loaded into production by this change.

## Resumed verification

- PHP remains unavailable in this restricted session; no alternate installed runtime was found in the workspace or standard program locations. User asked whether another accessible PHP runtime or a normal Herd terminal is available.
- Fixed CAGE clipboard fallback when navigator.clipboard is absent. Ran check-clipboard.cjs against the actual Blade click handler: reproduced the missing-API error before the change, then passed all three outcomes (successful copy, denied permission, API absent).
- Operational lookup failures now discard all partial item/reference/company/status arrays and pagination flags. Added a late-query failure regression; PHP suite now has 14 cases, still NOT RUN.
- git diff --check passed. Runtime/application and visual verification remain pending, so phase 3 is not complete.

Run from the project directory in an ordinary Herd terminal, in order, stopping if any command fails:

```powershell
php artisan test tests/Feature/PubLog/NsnLookupTest.php
php artisan test
php vendor/bin/pint --dirty
git diff --check
```

These test commands use the project's testing configuration (SQLite in memory); they do not activate the full imported catalog. Browser verification must still use an isolated fixture environment after the tests pass.

## Verified 2026-10-10 (Claude Code session, Herd PHP 8.4 available)

Focused tests, first run: 12 passed, 2 failed.

1. "stays on one release after activation changes": a test bug. `activate()` sets `activated_at` on a different model instance, so the test's `$import->update(['activated_at' => null])` was a no-op (the attribute wasn't dirty). Fixed with `$import->refresh()->update(...)`. NsnLookupService already rejects deactivated snapshot IDs correctly.
2. "rejects curated prefill from a release that was never activated": a real bug. CreateMilSpecPart::fillForm used findOrFail/firstOrFail, so the Livewire page threw ModelNotFoundException instead of returning 404. Changed to `find() ?? abort(404)`, `abort_unless(...->exists(), 404)` and `first() ?? abort(404)`.

Browser checks against an isolated SQLite fixture DB in the session scratchpad (migrated, PubLogFixtures::ready + activate, test admin with email MFA, mail to log, `php artisan serve` on 127.0.0.1:8123; real local DB untouched). Desktop at 1080px in Chrome (widest the screen allows; sidebar layout) and 390x844 mobile via Playwright + installed Chrome. No horizontal page overflow at either width.

- NSN search: item, company snapshots, references, Open CAGE Search / Copy CAGE code / Review as new curated part all render.
- NIIN no match, invalid NSN, part number (ambiguous), CAGE: correct outcome messages.
- Curated handoff: create form pre-fills NSN, part number and description; manufacturer left empty.
- Bug found and fixed, Copy CAGE code did nothing: `@js()` is not compiled inside `<x-filament::button>` attributes, so Alpine received the literal `@js($reference['cage_code'])` and failed silently. The earlier check-clipboard.cjs ran the source handler, not the rendered one, so it missed this. The code now lives in the wrapping span's `x-data`. After the fix the click shows "Copied" and the clipboard holds the CAGE (verified headless and headed). Regression added: the rendered page must not contain `@js(` and must contain the `code:` binding. It fails without the fix and passes with it.
- Bug found and fixed (pre-existing, from the terminal UI redesign): the curated-form tip icon used uncompiled Tailwind `w-4 h-4` and rendered full-size, pushing the text off-screen. Gave `.mspc-tip-icon` an explicit 1rem size and the text `min-width: 0`. Rechecked at 390px: no overflowing elements.

Final: PubLog tests 39 passed; full suite 925 passed, 5 skipped, 0 failed; Pint (four phase 3 files formatted) and git diff --check clean. Nothing committed.

## Local full catalog, 2026-10-10 (user chose "full real catalog")

- New setting `nsn_lookup.connection` (env `PUBLOG_DB_CONNECTION`; empty = app default, so behavior is unchanged when unset). New `publog` MySQL connection in config/database.php (utf8mb4_bin). PUB LOG models use the `UsesPubLogConnection` trait; PubLogCatalog/Downloader/Importer/Writer go through `PubLogDb::connection()`; the catalog migration's `getConnection()` honors the setting. Catalog tables are never joined to app tables (checked DIBBS matcher and catalog importer).
- phpunit.xml forces `PUBLOG_DB_CONNECTION=""` so tests never touch the MySQL catalog. Verified: MySQL import/state/definition counts identical before and after a full suite run.
- Local .env points at MySQL 127.0.0.1:13316 `publog_benchmark`. Import 1 (release 2026-10-01: 17.0M items, 16.6M references, 4.16M CAGE, 18.3M relationships) activated with `publog:activate 1 --expected-current=none`.
- Real lookups (NSNs from local DIBBS RFQs): 3-26 ms warm, about 100 ms on the first query.
- Tests: new PubLogConnectionTest (import, activate and lookup on a separate connection, with nothing written to the default DB; plus the default fallback). Full suite 927 passed, 5 skipped. FetchSamOpportunitiesJobPersistenceTest failed once under --parallel and passed 3/3 alone and on rerun (intermittent, unrelated).
- Phase 2 relevance: this is the shape production needs for a dedicated catalog volume (catalog in its own MySQL database). Retention and the volume runbook are still undecided.

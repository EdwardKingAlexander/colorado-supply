# Acceptance report: single-copy PUB LOG catalog (phase 5)

Date: 2026-10-10. Environment: local Windows, MySQL 8.0.46 on port 13316 (`publog_benchmark`, binary log off), PHP 8.4, app on SQLite with `PUBLOG_DB_CONNECTION=publog`.

## Full-catalog run

Command (re-import of the live 2026-10-01 release through the new path):

```
php artisan publog:import <archives> --release=2026-10-01 --reimport --activate
```

| Measure | Result |
|---|---|
| Rows imported | 17,025,715 items · 16,623,521 references · 4,158,375 CAGE (summary + address) · 36,200 definitions |
| History table | Not imported (default). 0 rows |
| Validation | 0 orphan references · 930 unresolved CAGE references (kept, flagged) · 1 short item code · 1 nonstandard CAGE value |
| Duration | 18:50:37 to about 20:43, **~1 h 53 min** (items ~16k rows/s; references slowed to ~3.7k rows/s as indexes grew) |
| Importer memory | Peak **435 MB** working set |
| Catalog DB on disk before | 19.25 GB (old copy, including the 6.3 GB history table) |
| Peak during import | **32.21 GB** (old copy plus new staged copy) |
| After swap | **12.96 GB** on disk (table data 11.3 GB: references 5.86, items 3.90, CAGE 1.51, definitions 0.02) |
| Staging left behind | None (`staging_tables: false`) |
| Previous import | Marked `replaced`; data dropped; the operator's source archives were not touched |
| Lookups during import | Served from the live copy (NSN lookup 55 ms mid-import) |
| Lookup latency after | 4–10 ms warm for NSN, NIIN, part, CAGE and no-match |

**Production budget** (monthly refresh from one ~13 GB copy): about 13 GB live + 13 GB staged + 1.3 GB largest spooled CSV + 0.8 GB archive copy, **≈ 28 GB peak**, back to **≈ 13 GB** after the swap. The first production import starts from empty, so it peaks around 15 GB. The runbook's 35 GB free-space pre-check leaves about 25% headroom.

## Browser checks (Playwright, installed Chrome, 1280 px and 390 px)

Scratch SQLite app database with seeded sourcing cases opened from the replaced import 1, against the new live import 2:
- PUB LOG Lookup on NSN 5310-01-134-0751: rows show their cases ("Answered · Conflicting", "Awaiting response · No confirmation yet") matched across releases; "Start sourcing case" on the others.
- New case started from a real reference; Draft inquiry and Mark contacted submitted through the real modals.
- Seeded case page shows "Since replaced by a newer release", the verification, distributor and conflicting answers; evidence download returns the right file.
- No horizontal page overflow on any page at either width.

Screenshots: session scratchpad `p5shots/` (not committed).

## Automated tests

- `PubLogSingleCopyTest` (12): one copy after consecutive imports, the live copy readable while staged and after a failed import (which abandons the staged one), restart after lost staging, re-import rules, history off by default, staging schema parity, interrupted activation completes, cases across releases, rollback retired, status reporting, discard staging, binlog option.
- Existing catalog tests updated for the new model (staged data read from staging tables; rollback test replaced by a replacement test). The history import is still covered with the flag on.
- MySQL smoke (throwaway database): three consecutive releases, one copy each time, unique per-import foreign key names (`pl_i1_ref_import` … `pl_i3_ref_import`), no leftover tables.
- Full suite: 1,020 passed, 5 skipped, 0 failed (4 workers). Pint and `git diff --check` clean.

## Issues found and fixed during acceptance

- `publog:status --sizes` showed stale sizes (MySQL caches table statistics for up to 24 h; it still listed the dropped 6.34 GB history table). It now requests current statistics.

## Known limitations

- **No rollback** to a previous month by design; recovery is re-importing. Older releases cannot replace a newer one.
- **Peak disk is two copies** during the monthly import. If the server cannot spare ~28 GB, a delete-then-load mode (lookups offline during import) would be needed; not built.
- **Brief empty window at swap:** between the table rename and the active-pointer update (milliseconds), a lookup can return no results.
- **Import time on the 2 GB production server** is untested; expect longer than the local ~2 h. Lookups stay up meanwhile.
- **Binary log:** production MySQL keeps binlogs 30 days by default. Use `PUBLOG_SKIP_BINLOG=true` with the `SESSION_VARIABLES_ADMIN` grant, or purge after import (runbook step 2 / section 9).
- Deployment itself has not been performed; it is your action following the runbook.

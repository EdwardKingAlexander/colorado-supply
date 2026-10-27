# DIBBS RFQ Tracking — Runbook

What runs, what the alerts mean, and what to do. Background:
`00-overview.md`, `findings.md` (verified DIBBS access contract).

## What runs automatically (America/Denver)

| When | What | Command / job |
|------|------|---------------|
| Daily 07:00 | Download + import any of the last 5 posting days not yet imported, rebuild catalog matches, queue the digest | `DibbsDownloadJob` (`dibbs-download`) |
| Daily 08:30 | Digest fallback (sends at most once a day) | `DibbsDailyDigestJob` (`dibbs-digest`) |
| Weekdays 10:00 | Alert if no new file for > 2 business days | `php artisan dibbs:check-freshness` |
| Sundays 03:00 | Prune old untouched RFQs and raw files | `php artisan dibbs:prune` |

Requires: the scheduler (`php artisan schedule:work` locally, cron
`* * * * * php artisan schedule:run` in production) **and** a queue worker
(`php artisan queue:work`). After deploying code changes, restart the worker.

## Run a download by hand

```
php artisan dibbs:download            # same as the 07:00 run, right now
php artisan dibbs:download --days=25  # first fill on a fresh install: everything DIBBS still lists (~1 month)
php artisan dibbs:download --force    # run even when DIBBS_ENABLED=false
```
A new server starts with an empty database (local data is not deployed):
run `--days=25` once after `php artisan migrate`, then let the schedule take
over. Matches rebuild and the digest is queued automatically.

## Where to look

- **Automation → Upload DIBBS File**: "Ingestion health" (latest file, last
  automatic run and its result, consecutive failures, next run) and the list
  of recent files with row counts and errors.
- **Automation → DIBBS RFQs**: the first stat card turns red when the feed is
  behind.
- Logs: `storage/logs/laravel.log`, search `DIBBS`.

## Alerts (email to `DIBBS_ALERT_EMAIL`, falling back to `SAM_ALERT_EMAIL`)

### "DIBBS RFQ download failed: Could not read the DIBBS RFQ listing"
DIBBS was down, blocked us, or changed its page.
1. Open `https://www.dibbs.bsm.dla.mil/RFQ/RFQDates.aspx?category=recent` in a
   browser. If it loads, the site is up.
2. Usually it's a DIBBS outage; tomorrow's run catches up (5-day backfill).
3. If it keeps failing while the site works in a browser, DLA may be blocking
   the automated client or changed the consent banner/listing. Use the manual
   upload (below) and investigate with
   `MSYS_NO_PATHCONV=1 php ai/modules/dibbs-rfq-tracking/probe.php "/RFQ/RFQDates.aspx?category=recent"`.
   Never switch to a browser User-Agent to get around a block.

### "N DIBBS file(s) could not be imported … layout mismatch"
DLA changed a file format. Nothing from that file was imported.
1. Compare with `/Rfq/RfqFileDefs.aspx` (index) or `/refs/help/Quoting/Batch/BatchFormat.aspx`.
2. Update `InFileParser::LAYOUT` / `BatchQuoteFileParser::BQ_COLUMNS` and the
   fixtures, then re-import (below).

### "No new DIBBS RFQ file since …" (freshness check)
Usually the scheduler or queue worker is not running.
1. Check Ingestion health: if "Last automatic download" is old or "Never", start
   the scheduler/worker.
2. A federal holiday can trigger this once (holidays aren't modelled); ignore
   if DIBBS posted nothing that day.

## Manual upload (fallback)

1. In DIBBS: Solicitations → RFQs → Recent.
2. Download the day's **Index File** (`inYYMMDD.txt`) and **Batch Quote File**
   (`bqYYMMDD.zip`). Keep DLA's file names.
3. Automation → Upload DIBBS File → upload both → Import files.
Uploading a file that was already imported does nothing (deduplicated by content).

## Re-import a day

Raw files are kept for 60 days (`DIBBS_RETENTION_RAW_FILE_DAYS`).

```php
php artisan tinker
$f = App\Models\DibbsFile::where('name', 'in261002.txt')->first();
$f->update(['status' => 'pending']);
app(App\Services\Dibbs\DibbsFileImporter::class)->import($f);
app(App\Services\Dibbs\DibbsRfqMatcher::class)->rebuild();
```
Re-importing is safe: rows upsert on (solicitation, purchase request) and keep
their first-seen date. If the raw file was pruned, re-download it from DIBBS
and use the upload page.

## Turning things off

| Setting (`.env`) | Effect |
|------------------|--------|
| `DIBBS_ENABLED=false` | Stops automatic downloads and freshness alerts. Upload still works. |
| `DIBBS_DIGEST_ENABLED=false` | Stops the digest. |
| `DIBBS_DIGEST_MIN_MATCH=approved_source` | Digest only lists catalog / approved-source matches. |

## Data retention

`php artisan dibbs:prune --dry-run` shows what would go. Pruned weekly: RFQs
closed more than 180 days (`DIBBS_RETENTION_CLOSED_RFQ_DAYS`) that nobody
acted on (no status beyond "new", for any company) and that match nothing, with
their batch quote rows and orphaned approved sources; raw files older than 60
days (import records stay).

## Batch quote export

- The app never submits quotes or stores DIBBS credentials. Export → review →
  upload yourself in DIBBS (Quoting → Batch Quote Upload). Max 75 lines per
  file; larger exports are zipped parts.
- Representations come from Automation → DIBBS Quoting Profile and must be
  re-confirmed on every change.
- If DIBBS rejects an upload, note the error and the export reference
  (`BQ-…`, stored on each quoted RFQ with the exact lines sent), then compare
  against BatchFormat.aspx.
- **Open item (6.3):** the first real upload has not happened yet (DIBBS
  registration pending as of 2026-10-04).

## Performance reference (2026-10-04, local SQLite, 10,961 RFQs)

Import of the largest day (5,429 lines) 0.23 s; match rebuild 7 ms; RFQ list
query (7,577 rows, first page) 13 ms; digest build 34 ms; full download of 5
posting days ~56 s (mostly the 2 s politeness delay).

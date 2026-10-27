# PUB LOG catalog: production runbook

For the Forge server (Ubuntu, MySQL 8, 2 GB RAM, 50 GB disk). Claude does not run any of this; you do. Every command was run once locally against MySQL 8.0 (see [acceptance report](acceptance-report.md)).

**What you end up with:** one copy of the DLA PUB LOG catalog, about **13 GB** on disk (measured), in its own MySQL database on the same server. Each month the new release loads beside the old one (disk peaks around **28 GB**; the first import from empty peaks around 15 GB), switches over when its checks pass, and the old copy is dropped. Lookups keep working throughout. A failed import never touches the live copy.

---

## 1. Pre-checks (stop if any fail)

SSH in (`ssh forge@<server>`), then:

```bash
df -h /                 # need about 35 GB free (one copy + a staged copy + temp files)
free -h                 # 2 GB RAM is enough; the importer peaked at 435 MB locally
mysql -V                # MySQL 8.x
mysql -uforge -p -e "SELECT @@log_bin AS binlog_on, @@binlog_expire_logs_seconds/86400 AS keep_days"
```

If free space is under ~35 GB, **stop**. Options: free disk, attach a volume, or ask Claude for the "delete then load" mode (peak ~14 GB, but lookups are offline during the import).

## 2. Create the catalog database

In Forge: **Server → Database → Add Database**, name `publog`, with a new user `publog` and a strong password. Then set binary collation and the binlog privilege:

```bash
mysql -uforge -p <<'SQL'
ALTER DATABASE publog CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
GRANT SESSION_VARIABLES_ADMIN ON *.* TO 'publog'@'%';
FLUSH PRIVILEGES;
SQL
```

(If Forge created the user as `'publog'@'localhost'`, use that host in the GRANT. Check with `SELECT user, host FROM mysql.user;`.)

**Why the binlog grant:** MySQL 8 records every change in its binary log and keeps it 30 days by default, so a 14 GB import would write about 14 GB of binlog. The catalog is always re-importable, so the app turns the binlog off **for its own import session only** when `PUBLOG_SKIP_BINLOG=true`. Nothing else on the server is affected.

## 3. Environment

Forge → **Site → Environment**, add:

```dotenv
PUBLOG_DB_CONNECTION=publog
PUBLOG_DB_HOST=127.0.0.1
PUBLOG_DB_PORT=3306
PUBLOG_DB_DATABASE=publog
PUBLOG_DB_USERNAME=publog
PUBLOG_DB_PASSWORD=<the password from step 2>
PUBLOG_SKIP_BINLOG=true
```

Leave `PUBLOG_IMPORT_RELATIONSHIPS` unset (off). It would add the ~6 GB status/replacement history.

*Alternative:* leave all `PUBLOG_DB_*` unset to keep the catalog in the app's main database. That works the same and uses the same ~13 GB, but in your main database (bigger backups).

## 4. Deploy

Push and deploy as usual. The deploy's `php artisan migrate --force` creates the sourcing-case tables in the app database and the catalog tables in `publog`. If the catalog tables already exist there, the migration leaves them alone, and rolling back never drops them.

Check: `php artisan publog:status` shows `"connection": "publog"` and `"active_import_id": null`.

## 5. Get the release files

DLA blocks automated downloads, so use a browser:

1. Open the [FLIS Data Electronic Reading Room](https://www.dla.mil/Information-Operations/FLIS-Data-Electronic-Reading-Room/), PUB LOG section.
2. Download **IDENTIFICATION.zip**, **CAGE.zip** and **REFERENCE.zip** from the **same** release, and note the release date shown (for example 2026-10-01). Together they are about 0.8 GB.
3. Upload them:

```bash
ssh forge@<server> 'mkdir -p ~/publog-incoming/2026-10'
scp IDENTIFICATION.zip CAGE.zip REFERENCE.zip forge@<server>:~/publog-incoming/2026-10/
```

Don't rename the files or mix releases.

## 6. Import and activate

```bash
cd ~/<site-directory>
nohup php artisan publog:import ~/publog-incoming/2026-10 --release=2026-10-01 --activate \
  > storage/logs/publog-import.log 2>&1 &
```

- It runs in the background; closing SSH is fine. Locally the full release took **about 2 hours** (~42 million rows); expect longer on 2 GB RAM.
- Follow it with `tail -f storage/logs/publog-import.log`, `php artisan publog:status`, and `df -h /`.
- `--activate` swaps it in as soon as validation passes. Without it, run `php artisan publog:activate <id> --expected-current=<current id or none>` yourself after reviewing `publog:status`.

**If it stops** (server restart, killed, error): the live catalog is untouched. Resume from the last checkpoint with `php artisan publog:import --resume=<id> --activate` (the id is in the log and in `publog:status`).

## 7. Verify

```bash
php artisan publog:status --sizes
```

Expect `active_import_id` set, `release_date` = your release, `staging_tables: false`, `binlog.enabled` as before, and `sizes_gb` totaling about 11–12 GB of table data (about 13 GB on disk). Then search a few NSNs in **NSN Procurement → PUB LOG Lookup**.

Remove the uploaded zips: `rm -r ~/publog-incoming/2026-10`. The app keeps its own copy of the current month's files under `storage/app/private/publog` until the next successful import, which lets you rebuild the live copy if needed.

## 8. Monthly refresh

Repeat steps 5–7 with the new release date. Disk peaks near 28 GB while it loads, then returns to ~13 GB. The previous month's tables and archive copy are deleted after the swap. `publog:status` flags the catalog as stale after 45 days.

## 9. When something goes wrong

| Situation | What to do |
|---|---|
| Import failed with a data or validation error | The live copy is untouched. Read the error in `publog:status`. Usually it needs a corrected DLA release. To free the staged space now: `php artisan publog:discard-staging`. |
| Disk getting full during an import | Stop it (`pkill -f "publog:import"`), then `php artisan publog:discard-staging --force`. Free disk, then start again. |
| Started a different month by mistake | Starting another import automatically discards the previous staged one. |
| Live copy needs rebuilding from the same month's files | `php artisan publog:import <dir> --release=<date> --reimport --activate` |
| Want to go back to last month | Not possible: only one copy is kept. Re-import last month's files (older releases can't replace a newer one). |
| `PUBLOG_SKIP_BINLOG` error at import start | The DB user lacks `SESSION_VARIABLES_ADMIN`. Grant it (step 2), or remove `PUBLOG_SKIP_BINLOG` and after the import run `mysql -uforge -p -e "PURGE BINARY LOGS BEFORE NOW();"` to reclaim the binlog space. |

## 10. Undo

Remove the `PUBLOG_*` environment lines and redeploy. The lookup then shows "Catalog not available". To reclaim the space, drop the `publog` database in Forge. Sourcing cases keep their own copy of NSN, part, CAGE and item name, so they stay readable.

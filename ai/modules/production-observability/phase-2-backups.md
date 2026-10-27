# Phase 2 — Automated Backups

## Blocking Question (must be answered before a real backup can run)

Where should backups actually be stored (S3 bucket, or another off-server
target), and does that destination's credentials already exist in `.env`,
or does the orchestrator need to provision them? Checked: `.env` currently
has `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`/`AWS_BUCKET` present but
empty. This phase can be built and tested against a local/fake disk
regardless, but the first real backup run needs real credentials supplied
directly in `.env` by whoever owns the AWS account — never typed into
chat.

## Goal

Automated, scheduled, off-server database + file backups with failure
notifications and a verified restore path.

## Files to Create/Modify

- `composer.json` — add `spatie/laravel-backup`.
- `config/backup.php` (published) — configure:
  - `disks` → the S3 (or equivalent) disk once credentials are available;
    default to `local` only for pre-credential testing, never as the real
    production target.
  - What gets backed up: the database connection, plus any
    non-regeneratable file storage (e.g., uploaded SAM opportunity
    documents/attachments if stored on local/S3 disk — confirm exactly
    which storage paths hold non-regeneratable data vs. rebuildable cache/
    generated files, and only back up the former to keep backup size/cost
    sane).
  - Retention policy (`config/backup.php`'s cleanup strategy) — e.g., keep
    daily backups for 7 days, weekly for a month, monthly for a year (a
    reasonable default; adjust if the orchestrator has a specific
    compliance-driven retention requirement).
  - Notifications on backup failure (email at minimum, matching this app's
    existing Mailgun setup).
- `routes/console.php` (or wherever this app schedules commands — confirm
  Laravel 12's convention already in use here) — schedule
  `backup:run` daily and `backup:clean` to enforce retention.

## Out of Scope

- No changes to error monitoring (Phase 1).

## Tests

- `tests/Feature/BackupConfigurationTest.php`: `config('backup.backup.destination.disks')`
  resolves to the intended disk; the scheduled command is registered.
- **Manual, actually-executed restore drill** (not a unit test): run
  `backup:run`, download the resulting backup, restore it into a scratch/
  local database, and confirm the data matches. Document the exact steps
  taken and their outcome in this module's `STATE.md` log — a backup
  nobody has restored from is not a verified backup.

## Success Criteria

- Scheduled backups run and land in the configured off-server destination.
- Failure notifications actually fire when tested (e.g., temporarily
  misconfigure the disk and confirm the notification arrives, then revert).
- At least one full restore drill executed and documented.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**

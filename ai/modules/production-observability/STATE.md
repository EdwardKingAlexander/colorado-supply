# Production Observability (Error Monitoring + Backups) — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Approved | 2026-07-09 | User approved proceeding with all unblocked phases |
| 1 | Error monitoring (Sentry) | Awaiting Validation | 2026-07-09 | Hosting confirmed as Laravel Forge; implemented, tested, still needs a real Sentry DSN to verify against a live/sandboxed project |
| 2 | Automated backups | Blocked | 2026-07-09 | Structurally implemented against the `local` disk per the plan's explicit allowance; still genuinely blocked on AWS credentials for the real off-server destination and the restore drill |
| 3 | Testing & hardening | Not Started | 2026-07-04 | |

## Log

- 2026-07-04 — Plan drafted. One of four gap areas planned after the user
  asked for a gov/B2B-readiness assessment (siblings: `ai/modules/audit-logging`,
  `ai/modules/admin-account-security-complete`, and a direct `ComplianceAuditTool`
  bug fix already applied).
  - Confirmed via `composer.json`/`.env` inspection: no error monitoring
    package installed, no backup package installed, `AWS_ACCESS_KEY_ID`/
    `AWS_SECRET_ACCESS_KEY`/`AWS_BUCKET` present in `.env` but empty.
  - Chose Sentry over Laravel's first-party Flare tentatively, since Flare
    is tightly coupled to Forge/Vapor hosting and there's no evidence this
    project uses either — flagged as a confirm-before-Phase-1 item rather
    than assumed.
  - Chose `spatie/laravel-backup` (confirmed Laravel 12-compatible via
    research) for automated DB+file backups.
  - Both phases are genuinely blocked on information/credentials only the
    orchestrator has (hosting platform + Sentry DSN; backup destination
    credentials) — these cannot be fabricated or guessed, and per this
    app's own operating rules, credentials are never entered by the AI,
    only by whoever owns them, directly in `.env`.
  - Flagged sensitive-data scrubbing (Sentry) and an actually-executed
    restore drill (backups) as non-negotiable success criteria — a backup
    that's never been restored from, or an error tracker that's never had
    its scrubbing verified against a real payload, isn't actually done.
  - Awaiting orchestrator approval of the overall plan, plus answers to
    both blocking questions, before implementation begins.
- 2026-07-09 — User approved proceeding with all unblocked phases and
  confirmed production hosting is Laravel Forge, unblocking Phase 1.
  - `composer require sentry/sentry-laravel` (installed `^4.26`), published
    `config/sentry.php`.
  - `app/Support/SentryEventScrubber.php` (new): `before_send` callback
    referenced as an array-callable (`[SentryEventScrubber::class,
    'scrub']`, not a Closure) specifically so `config/sentry.php` stays
    safe to run through `php artisan config:cache` on deploy — a plain
    Closure in a cached config file would break `config:cache` entirely,
    which the plan's assumed API didn't flag. Redacts `Authorization`,
    `Cookie`, `X-Xsrf-Token` headers and a fixed list of sensitive field
    names (`password`, `tax_id`, `card_number`, `cvv`, `token`, etc.) from
    both request data and breadcrumb metadata, recursively.
  - `traces_sample_rate` set to a conservative `0.1` default (env
    override-able) — this phase is error capture, not full APM, per the
    plan's own scope boundary.
  - `bootstrap/app.php` — `\Sentry\Laravel\Integration::handles($exceptions)`
    added to `withExceptions()`, per the Laravel 12 wiring documented in
    the installed `sentry/sentry-laravel` v4 README (confirmed against the
    actual installed version's docs, not assumed from an older Sentry
    Laravel version).
  - `SENTRY_LARAVEL_DSN` added blank to `.env` (this repo has no
    `.env.example` file at all, so the plan's assumed file doesn't exist
    here — `.env` itself is gitignored, confirmed via `git check-ignore`,
    so this is safe). The real DSN still needs to be entered directly in
    `.env` by whoever owns the Sentry project; never pasted into chat.
  - `tests/Feature/SentryIntegrationTest.php`: 2/2 passing, using a
    fake in-memory `TransportInterface` bound to the SDK hub (no real
    Sentry project needed) — one test proves `before_send` actually
    redacts a sensitive field before the event would leave the server,
    the other proves a thrown exception reaches the transport as a
    captured event.
  - `vendor/bin/pint --dirty` clean (109 files).
  - **Not yet verified**: success criteria explicitly calls for confirming
    delivery against a real or sandboxed Sentry project once a DSN
    exists — that hasn't happened, since no DSN has been supplied (by
    design; this session never asks for or handles one). This phase can
    be `Awaiting Validation` but not `Complete` until that DSN exists and
    a real end-to-end delivery is confirmed.
- 2026-07-09 — Phase 2 implemented structurally, still genuinely blocked
  on real credentials (per the plan's own explicit allowance to build and
  test against `local`/fake disk regardless):
  - `composer require spatie/laravel-backup`, published `config/backup.php`.
  - `source.files.include` scoped to `storage_path('app')` only (covers
    both the `local` and `public` disks — contract documents, SAM
    opportunity documents, parsed document artifacts) rather than the
    whole codebase, which is already in git — keeps backup size/cost sane
    per the plan's own guidance. Excludes the package's own
    `backup-temp` dir and Livewire's transient `livewire-tmp` uploads dir.
    `SamStateFileManager`'s JSON state files were left in scope (they live
    under `storage/app` too) rather than carved out — they're small and
    excluding them would need a narrower, more fragile exclude rule for
    negligible benefit.
  - `database.default` connection included in `source.databases` (via the
    package's own `env('DB_CONNECTION', 'mysql')` default, unchanged).
  - Retention tuned to match the plan's literal wording ("daily for 7
    days, weekly for a month, monthly for a year"): `keep_all_backups_for_days
    = 7`, `keep_daily_backups_for_days = 0` (redundant with the 7-day
    all-backups window), `keep_weekly_backups_for_weeks = 4`,
    `keep_monthly_backups_for_months = 12`, `keep_yearly_backups_for_years
    = 0` (no compliance-driven indefinite-retention requirement was
    given, so not adding one).
  - Failure/success notifications wired to `mail`, recipient now reads
    from a new `BACKUP_NOTIFICATION_EMAIL` env var (left as the package's
    placeholder default) rather than left hardcoded to the package's
    `your@example.com` example — **the real recipient address still needs
    to be set**, not a credential but still an open item.
  - `destination.disks` and `monitor_backups[].disks` both explicitly left
    on `'local'` with an inline comment explaining why and what to change
    once S3 credentials exist — a same-server-only "backup" isn't a real
    off-server backup, so this must not silently look done.
  - Scheduled `backup:clean` (01:00), `backup:run` (01:30), and
    `backup:monitor` (03:00) daily in `routes/console.php`, matching the
    existing `business:check-deadlines` scheduling convention
    (`withoutOverlapping()->onOneServer()`).
  - `tests/Feature/BackupConfigurationTest.php`: 4/4 passing (destination
    disk, source include/exclude paths, database connection, scheduled
    commands all registered).
  - **Real functional smoke test run** (not the full plan-required restore
    drill, but real signal): ran `php artisan backup:run --only-files`
    against the actual local disk — it zipped 25 files and successfully
    wrote `storage/app/private/2026-07-09-20-16-27.zip` (confirmed present
    on disk, then deleted as a throwaway test artifact — gitignored, never
    committed). This proves the include/exclude/zip/disk-write pipeline
    genuinely works, not just that the config file parses.
  - **Two real problems surfaced by that same test run, both worth flagging
    directly** (see chat — not burying these in a log only the orchestrator
    might not read):
    1. `php artisan backup:run --only-db` failed: the `sqlite3` CLI binary
       isn't installed on this Windows dev machine, so `spatie/db-dumper`
       can't shell out to dump the local SQLite database. This is a local
       dev-environment gap only. **Confirmed 2026-07-09 by the user:
       production runs MySQL** (matching `source.databases`'s existing
       `env('DB_CONNECTION', 'mysql')` default), so `mysqldump` is the
       actual code path in production and this SQLite-only gap does not
       reproduce there.
    2. Both the failure and success notification attempts threw
       `Unable to send an email: Domain mail.cogovsupply.com is not
       allowed to send: Account Inactivity (code 403)`. **Corrected
       2026-07-09 by the user: this is expected in local dev** (Gmail is
       connected as the receiving side for local testing) — production
       Mailgun sends correctly and reaches the user's account. Not a real
       issue; initial framing of this as a live production problem was
       wrong.
  - `vendor/bin/pint --dirty` clean (112 files).
  - Remains genuinely `Blocked`: no AWS/off-server credentials exist in
    this environment to switch `destination.disks`/`monitor_backups` to
    `s3` and run the real restore drill the plan's success criteria
    require. Per this app's own operating rules, those credentials must be
    entered directly in `.env` by whoever owns the AWS account, never
    typed into chat.

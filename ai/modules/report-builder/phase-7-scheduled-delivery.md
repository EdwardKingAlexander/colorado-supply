---
plan: report-builder
phase: 7
title: Scheduled email delivery
status: not_started
last_updated: 2026-10-23
depends_on: [phase-6]
checkpoint: none
---

# Phase 7 — Scheduled Delivery

## Goal

Users can have a saved report emailed as CSV, XLSX or PDF to themselves and
other users in their company on a daily, weekly or monthly schedule. It runs
reliably, with a run history and safe failure handling.

## Success criteria

- A schedule has a frequency (daily, weekly on chosen weekdays, monthly on day
  1–28 or "last day"), a send time, a timezone (default `America/Denver`), a
  format, recipients (company users only), an optional message, and an
  "only send if there are rows" toggle.
- Delivery runs **as the schedule owner at send time**. Recipients who are no
  longer in the owner's company are dropped and logged. If the owner leaves
  the company or loses view access, the schedule pauses itself and the owner
  is told.
- Run log per schedule: status, row count, duration, error.
- After 3 failures in a row the schedule pauses itself and the owner is
  notified.
- Every email has a signed one-click "Pause this schedule" link, and the
  owner can resume it in the UI.
- A user can have at most 20 active schedules.

## Sub-phases

### 7.1 — Schema and model — `checkpoint: schema`
- Migration `create_report_schedules_table`: `report_id`, `user_id` (owner), `frequency` enum, `weekdays` json nullable, `day_of_month` nullable (0 = last), `send_time` (time), `timezone`, `format` enum, `message` text nullable, `skip_if_empty` bool, `is_active` bool, `consecutive_failures` tinyint, `next_run_at` (UTC, indexed), `last_run_at`, timestamps.
- `report_schedule_recipients` (`report_schedule_id`, `user_id`; unique pair).
- `report_runs` (`report_schedule_id` nullable, `report_id`, `trigger` enum schedule/manual, `status`, `row_count`, `duration_ms`, `error` text, `created_at`).
- `ReportSchedule::computeNextRunAt()` uses Carbon in the schedule's timezone and is DST-safe.
- **Done when:** unit tests cover next-run calculation for each frequency, DST transitions, month-end and leap years.

### 7.2 — Dispatcher and job — `checkpoint: job`
- `routes/console.php`: `Schedule::command('reports:dispatch-due')->everyFifteenMinutes()->withoutOverlapping()`.
- The `DispatchDueReportSchedules` command selects active schedules with `next_run_at <= now()` (with a `lockForUpdate` claim that advances `next_run_at` first, so a rerun never double-sends) and queues `RunReportScheduleJob`.
- `RunReportScheduleJob` (tries 3, backoff 60/300/900):
  1. re-check owner access
  2. resolve the valid recipients
  3. run the report
  4. export it through the phase 6 exporters
  5. attach the file (or a signed link if it's over 10 MB)
  6. send `ScheduledReportMail`
  7. write a `report_runs` row
  8. reset or increment the failure count
- **Done when:** feature tests cover due selection, no double send, access loss, recipient pruning, skip-if-empty, failure count and auto-pause.

### 7.3 — Mail — `checkpoint: mail`
- A `ScheduledReportMail` markdown mailable: report name, the plain-English filters (`DefinitionDescriber`), row count, KPI summary when the report has a KPI chart, "Open in portal", and the signed pause link (`URL::signedRoute`, no login needed, pauses only that schedule).
- Note: Mailgun failures in local dev are expected (see memory). Tests use `Mail::fake()`.
- **Done when:** mailable render test and pause-link test pass.

### 7.4 — UI — `checkpoint: ui`
- `Components/Reports/ScheduleDialog.vue` from the builder and the library: create, edit, pause and resume, delete, "Send now" (manual trigger, throttled 5/hour), a recipient picker (company users, searchable) and the next 3 send times previewed.
- Runs tab on the builder: the last 20 runs.
- **Done when:** Playwright creates a schedule, sends now and sees a run row.

## Files to create

- Migrations, `ReportSchedule`, `ReportRun` models + factories, `DispatchDueReportSchedules` command, `RunReportScheduleJob`, `ScheduledReportMail` + view, `ReportScheduleController`, `ReportSchedulePauseController` (signed), `ReportSchedulePolicy`, Vue components, tests.

## Files to modify

- `routes/web.php`, `routes/console.php`, `Builder.vue`, `Index.vue`, and the Forge setup notes (scheduler + queue worker must be running: add to the module's deploy notes in phase 8).

## Risks

- Sending data to former employees. Recipients and owner access are
  re-checked on every run.
- Clock and DST edge cases. Store UTC `next_run_at` and compute it in the
  schedule's timezone, with tests.
- Mail volume and cost. Per-user schedule cap and `skip_if_empty`.

## Definition of done

Phase tests pass, the build passes, the full suite passes, and pint has been
run on the edited files. Set STATE to `Awaiting Validation`.

**STOP. Await phase approval.**

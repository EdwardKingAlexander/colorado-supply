---
plan: sam-psc-discovery
phase: 3
title: Control panel, schedule and measurement
status: completed
last_updated: 2026-10-04
depends_on: [phase-2]
checkpoint: 3.4-live-validation
---

# Phase 3 — Control panel, schedule and measurement

## Goal

Expose PSC discovery in the control panel and the daily 6am fetch, show what
PSC search adds, and prove the gain with a live run.

## Sub-phases

### 3.1 Control panel form
Steps:
1. Rename the field to "Also search these PSC codes" (`psc_search`), options
   from `config('sam_opportunities.psc_codes')` with descriptions (add a
   `psc_descriptions` map to config), default = configured list.
2. Helper text explains NAICS OR PSC discovery and the extra request count.
3. Session persistence (added 2026-10-04) carries `psc_search`; restore
   validation filters unknown PSCs as it does NAICS.
DoD: Livewire tests for default, persistence, and dispatched params.

### 3.2 Results visibility
Steps:
1. "Query Parameters Used" lists PSC terms with per-term match counts (same
   chip style as NAICS counts).
2. Summary shows "Found only via PSC: N".
3. Results table: add a PSC filter to the existing filter bar; card/table rows
   show PSC alongside NAICS.
DoD: view tests assert per-term counts and the only-via-PSC figure.

### 3.3 Scheduled fetch
Steps:
1. `routes/console.php` 6am job passes `psc_search` = configured PSC list
   (user decision: scheduled fetch searches both).
2. Add `config('sam_opportunities.max_terms_per_fetch')` guard (default from
   Phase 1 measurements) with a logged warning when exceeded.
DoD: `SamScheduledFetchTest` asserts PSC terms dispatched.

### 3.4 Live validation
Steps:
1. Restart queue worker; run one control-panel fetch (14 days, defaults).
2. Record: total, found-only-via-PSC, per-term counts, duration, any 429.
3. Compare with the 2026-10-04 NAICS-only baseline (151 fetched / 14 days /
   20 codes).
DoD: numbers recorded in `STATE.md` log.

## Files

Modify: `app/Filament/Pages/FetchSamControlPanel.php`,
`resources/views/filament/pages/fetch-sam-control-panel.blade.php`,
`config/sam_opportunities.php`, `routes/console.php`.

## Tests

- Update: `FetchSamControlPanelFormPersistenceTest`, `SamScheduledFetchTest`.
- New: panel view test for PSC counts.
- Full suite + pint.

## Success criteria

- Live run shows notices found only via PSC (N > 0) and no failed fetch.
- Scheduled fetch includes PSC terms.

## Risks / blockers

- Quota: if 429s appear in live validation, lower the term cap before enabling
  the schedule.

STOP. Await phase approval.

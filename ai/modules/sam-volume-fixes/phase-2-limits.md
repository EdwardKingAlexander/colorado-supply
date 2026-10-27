# Phase 2 - Ingestion and display limits

- Objective: Prevent display caps from discarding opportunities before persistence.
- Dependencies: Phase 1 validated.
- Entry criteria: Pagination reliable; data/display contract agreed.
- Tasks: Separate fetched, filtered, saved and displayed counts/data; review export/job consumers.
- Verification: Records beyond display cap remain persisted/exportable; compatibility tests.
- Exit criteria: Display limits do not lose ingested records; evidence recorded.
- Risks: Payload size and existing response consumers.
- Out of scope: Industry discovery and scheduler scope.
- Lifecycle: VERIFYING
- Checklist: [x] Design [x] Implementation [x] Verification

STOP. Await phase validation before starting phase 3.


## Authorized implementation contract

- `FetchSamOpportunitiesTool::fetch` gains an optional apply-limit flag, default true for existing callers. The job disables it; no user-supplied parameter bypasses resolver validation.
- Job saves all post-filter results transactionally and records the actual saved count. Its shared state retains the complete matching set for exports. Tool does not publish a premature legacy success state when called for ingestion.
- Panel slices data on load and labels fetched, matched, saved and displayed counts explicitly. Direct MCP callers and old state files remain compatible.
- Tests: real job + fake HTTP with more matches than display limit, excluded matches, upserts, export beyond displayed rows, panel slice, direct-call limit, missing IDs and empty result counts.



## Verification - 2026-10-04

- Initial three regression tests failed on the old save path; final four integration cases pass (28 assertions).
- A display limit of one saves all three filtered matches, excludes an unrelated match, upserts on repeat, and renders only one opportunity in Livewire.
- Admin export retrieves a matching notice outside the panel slice. Direct tool calls still return their capped response.
- A two-page fixture saves all 1,001 matches despite a display limit of one. Shared job state retains all 1,001 for export.
- Missing notice IDs are skipped with actual saved/skipped counts; zero-match queries report zero saved/displayed.
- Full application suite: 731 passed, 5 skipped, 2,747 assertions, 122.79 seconds. No failures on this run. Output: `phase-2-full-suite.txt`.
- Focused Pint, `php vendor/bin/pint --dirty --test`, and `git diff --check` passed.
- Shared result state backed up/restored around the full suite. No live job, deployment, or real notifications executed. Existing unrelated edits preserved.
- Phase 2 implementation is verified and awaiting user validation. Phase 3 remains NOT_STARTED.

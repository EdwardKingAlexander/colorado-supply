# Phase 1 - Pagination and completeness

- Objective: Retrieve sequential SAM pages and report missing pages honestly.
- Dependencies: Completed investigation; user authorization to fix issues one by one.
- Entry criteria: Confirmed page-index contract and reproducible 2,500 -> 1,000 record loss.
- Tasks: Fix offsets; retain advertised totals across empty/no-data pages; flag incomplete results; avoid caching partial responses and invalidate old namespace; propagate partial status/counts to panel; replace incorrect tests.
- Files: SamApiClient, SamMultiNaicsFetcher, SamOpportunitiesCache, SamResponseBuilder, FetchSamOpportunitiesTool, panel view, focused tests.
- Verification: Request-sensitive 2,500-record regression; empty page, cap, HTTP failure cases; incomplete status/cache/panel tests; SAM tests; full suite and formatter.
- Exit criteria: All 2,500 records retrieved once; missing pages reported as partial with records retained; targeted tests pass, broader failures documented; tracker synchronized.
- Risks: Dynamic totals; preserve shared state around existing tests; no live requests in tests.
- Out of scope: Result limits, broader discovery, scheduler defaults, unrelated fixes.
- Lifecycle: COMPLETE (validated by user instruction to advance on 2026-10-04)
- Checklist: [x] Implementation [x] Automated verification [x] Reviewable result

## Evidence - 2026-10-04

- New contract regressions failed before implementation in three cases, then all seven pagination tests passed (28 assertions).
- Independent investigation probe now retrieves all 2,500 records exactly once via pages 0/1/2, missing=0.
- Six new integration cases cover incomplete empty/error/no-data/capped pages, retry instead of caching, last-page keyword match, complete cache reuse, persisted diagnostics and rendered warning even with zero filtered matches.
- Final SAM check: 309 passed. The combined run also selected the known unrelated QuotePolicy test, which failed in combination; it passes alone (1 passed / 6 assertions).
- Full suite before the last metrics correction: 725 passed, 5 skipped, 2 failed. The SAM metrics failure was corrected and passes in the final 309-test SAM run. The other failure was the QuotePolicy isolation issue documented in the prior SAM repair state.
- `php vendor/bin/pint --dirty --test`, focused formatting and `git diff --check` pass.
- Shared SAM result file backed up/restored around suite runs. No live fetch job, deployment or notifications executed.
- Category import, CRM and SAM mobile layout/test changes belong to other work and were preserved. The SAM view contains both this warning and concurrent layout edits.
- Full and SAM output saved locally in `full-suite.txt` and `sam-suite.txt` (module directory is git-ignored by repository convention).

No ingestion-limit or search-scope behavior changed. Phase 2 stays NOT_STARTED pending validation.

STOP. Await phase validation before phase 2.


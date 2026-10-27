# Current work: NSN lookup interface

- Plan and source of truth: [NSN state](ai/modules/nsn-sourcing-workflow/STATE.md); [phase 3](ai/modules/nsn-sourcing-workflow/phase-3-lookup-experience.md).
- Lifecycle: BLOCKED (verification); phase 3 authorized 2026-10-09.
- Current task: Local interface implementation written; runtime verification pending.
- Last completed action: Fixed CAGE clipboard fallback (three JS checks passed) and partial-result handling on lookup failure; 14 PHP regression cases written but not run.
- Next action: Run focused/full tests and Pint once PHP is accessible; validate desktop/mobile UI.
- Blockers: This session denies access to installed Herd PHP; tests/formatter/application preview not run. Production storage, retention and deployment deferred by user.
- Verification: Static review, diff check and three clipboard JS checks passed; 14 PHP regression tests written, NOT RUN. Previous 911-test suite predates interface changes. See module phase-3-verification.md.

# Previous work: SAM admin Favorites repair

- Plan: [SAM admin Favorites](ai/modules/sam-admin-favorites/00-overview.md).
- Current phase: 2; lifecycle VERIFYING / Awaiting Validation.
- Current task: Full-data import COMPLETE (status ready, not activated). Awaiting user validation of phase 2 and a decision on the production capacity gate.
- Last completed action: 2026-10-05 12:36 full import finished `ready`: 17,025,715 items, 16,623,521 references, 4,158,375 CAGE entities, 18,269,449 relationships, 36,200 definitions; 0 orphan references, 930 unresolved CAGE references, 1 short INC, 1 invalid source CAGE (`Q9C4). Allocated 20.45 GB per release (40.89 GB for two). Indexed lookup p95 0.26-0.60 ms. Full suite 911 passed / 5 skipped / 3,767 assertions. Evidence: mysql-full-final-report.json, kind-scoped-full-suite.txt.
- Next action: User validates phase 2. Capacity decision (user, 2026-10-05): add a dedicated block-storage volume for MySQL (~$10/month); no slim export, no off-server/home DB. Open: retention cap (proposed: keep active + previous + case-pinned releases, explicit prune command) because unbounded retention grows ~21 GB/month. Suggested volume size 100 GB. Phase 3 NOT_STARTED. No importer running.
- Blockers: None.
- Decisions: Keep admin authentication/MFA and customer ownership intact; no deployment or credentials changes.
- Verification evidence: Module verification.md, full-tests.txt and final-regressions.txt; admin-only request returns 200; authentication/MFA retained; actor ownership isolated.

# Previous work: NSN sourcing workflow

- Master plan: [Overview](ai/modules/nsn-sourcing-workflow/00-overview.md); [module state](ai/modules/nsn-sourcing-workflow/STATE.md).
- Current phase: Phase 2 - versioned catalog importer.
- Lifecycle: PAUSED (unfinished) - user paused work 2026-10-05; resume from Next action.
- Current task: Full-data import resumed with relationship duplicate-check fix; awaiting completion, final validation (orphan/unresolved-CAGE counts) and capacity report.
- Last completed action: 2026-10-05 (Claude) fixed relationship import slowdown: writer duplicate check omitted `kind`, so MySQL used only the import_id prefix of (import_id, kind, row_hash) and scanned ~2.3M index rows per 500-row batch (9.1 s vs 51 ms measured). Added kind filter + regression test; PubLog tests 25 passed / 223 assertions; Pint clean. Interrupted old worker via KILL QUERY (checkpoint retained) and resumed: ~280 -> ~6,500 rows/s. Earlier: CAGE anomaly `Q9C4 tolerated; P_CAGE, V_CAGE_ADDRESS and V_FLIS_PART fully imported.
- Next action: Running importer PHP 42372, watcher 34820 (detached). Live output: ai/modules/nsn-sourcing-workflow/mysql-kind-scoped-progress.txt. Watcher writes mysql-full-final-report.json on exit (check its timestamp; prior interrupted report kept as mysql-relationship-scan-interrupted-report.json). Do not start a duplicate importer. Risk: max_orphan_references=0 and full-data orphan count is still unmeasured.
- Blockers: No implementation blocker. Optimized uncompressed catalog capacity and relationship validation remain pending; the 21.04 GB per-release sample estimate does not yet establish safe two-version headroom on 50 GB.
- Decisions: Local phase 2 only; retain all versions; preserve existing NSN UI and unrelated SAM, DIBBS, category and Filament work. User reports upgraded server; no paid infrastructure action or production import authorized here.
- Verification evidence: [Schema optimization](ai/modules/nsn-sourcing-workflow/schema-optimization.md), index-binding-tests.txt and mysql-binding-explain.json. All items imported; CAGE now progresses at roughly 20k rows/s with 58 MiB PHP peak. Check report timestamps on resumption because mysql-full-final-report.json can describe a prior interrupted run until the current worker finishes.

# Previous work: SAM result-volume fixes

- Master plan: [SAM volume fixes](ai/modules/sam-volume-fixes/00-overview.md)
- Current phase: [2 - Ingestion and display limits](ai/modules/sam-volume-fixes/phase-2-limits.md)
- Lifecycle: VERIFYING - implementation and automated checks finished; awaiting user validation
- Current task: Review the completed ingestion/display repair.
- Last completed action: Full application suite passed: 731 tests, five skipped, 2,747 assertions. Formatter and diff checks passed.
- Next action: After user validation, phase 3 addresses broader discovery, explicit industry scope and defaults.
- Blockers: None. The previously observed QuotePolicy order issue did not fail this full run.
- Decisions: Phase 1 COMPLETE after user instructed advancement. Phase 2 job ingests all deduplicated/post-filter matches; direct tool calls retain capped responses. Shared job state/export contains every match; panel slices on load. Actual saved/skipped counts are recorded. Phase 3 NOT_STARTED.
- Verification: Four new integration cases pass (28 assertions), including 1,001 matches persisted with display limit one, export beyond the visible slice, upserts, filter exclusions, missing-ID and zero-result counts. Initial regressions failed before repair. Full suite output in ai/modules/sam-volume-fixes/phase-2-full-suite.txt.
- Scope: No search-default, API-cap, environment or deployment changes. Shared state restored after suite. Unrelated category, CRM and SAM mobile layout/test work preserved.

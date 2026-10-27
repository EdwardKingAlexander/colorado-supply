# NSN sourcing workflow state

- Master plan: [Overview](00-overview.md).
- Current phase: ALL PHASES COMPLETE (2026-10-10). Deployment = user runs RUNBOOK.md.
- Current task: Phase 3 verified 2026-10-10 (tests, Pint, desktop + 390px mobile on an isolated fixture DB). Awaiting user validation.
- Last completed action: 2026-10-10 Claude Code: ran the 14 regressions (2 failed), fixed both, fixed a broken Copy CAGE button and an overflowing tip on the curated-part form found in browser checks. See phase-3-verification.md "Verified 2026-10-10".
- Next action: None in this module. Production deployment per RUNBOOK.md when the user is ready.
- Blockers: None for phase 3. Production storage/retention and deployment deferred by user on 2026-10-09.
- Decisions: User explicitly authorized interface work now; phase 2 production capacity/retention gates remain outstanding, not silently completed. Local fixture/catalog evidence is sufficient for interface development. No infrastructure, activation, purchases or outreach. Immediate read-only lookup replaces queue UI; retired queued scraper jobs must fail explicitly rather than silently save data.
- Verification: 2026-10-10 full suite 925 passed, 5 skipped, 0 failed; PubLog 39 passed; Pint and git diff --check clean.

| Phase | Lifecycle |
|---|---|
| 1 source contract | COMPLETE |
| 2 importer | COMPLETE - validated by user 2026-10-10 (single-copy storage measured in phase 5) |
| 3 lookup interface | COMPLETE - validated by user 2026-10-10 |
| 4 company/sales workflow | COMPLETE - validated by user 2026-10-10 |
| 5 rollout | COMPLETE - validated by user 2026-10-10 |


Resumed: PHP remains inaccessible. Reviewing error-result consistency and clipboard fallback while awaiting a usable runtime; phase remains blocked on execution checks.

Latest progress: CAGE clipboard fallback reproduced and fixed; three JavaScript outcome checks passed against the actual handler. Late-query failures now clear partial results; 14 PHP regressions written, not run. PHP runtime access still required. See phase-3-verification.md for commands and evidence.

- 2026-10-10: User validated phase 3 after using the full-catalog lookup ("absolutely baller"). Marked COMPLETE.
- 2026-10-10: Phase 4 scope re-checked. User decisions: cases owned by the home company (DIBBS rule, no picker); evidence = text + private file uploads. Data model and sub-phases 4.1-4.5 written. Awaiting phase approval.
- 2026-10-10: Phase 4 implemented (sub-phases 4.1-4.5), 15 new tests, full suite 945 passed. Browser-verified at 1280/390 against the full catalog. Also fixed catalog migration safety (skip existing tables; never drop a separate catalog on rollback). Not committed. Awaiting validation.
- 2026-10-10: User validated phase 4. Marked COMPLETE.
- 2026-10-10: Retention decided. The user wants ONE copy and delegated details: swap-then-drop via fresh tables (MySQL keeps deleted-row space), drop the relationships table (6.3 GB), ~14 GB steady / ~28 GB monthly peak, no volume. Replaces the "active + previous + case-pinned" proposal. Phase 5 rescoped and awaiting approval.
- 2026-10-10: Phase 5 built: staging/swap single copy, history off, --reimport, discard-staging, binlog skip, status sizes. Full local re-import: peak 32.2 GB, final 12.96 GB, ~1h53m, lookups 4-10 ms. RUNBOOK.md and acceptance-report.md written. Phases 2 and 5 awaiting validation. Not committed.
- 2026-10-10: User validated phases 2 and 5. Module COMPLETE.

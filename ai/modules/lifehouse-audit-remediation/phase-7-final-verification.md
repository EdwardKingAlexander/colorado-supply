# Phase 7 - Final Verification and Closeout

## Goal

Prove the phased fixes improved the audit without introducing regressions.

## Scope

- Run a fresh clean Lighthouse audit for the same target and compare scores to
  the Phase 1 baseline.
- Verify the issue ledger in `STATE.md` is complete and accurate.
- Run project-standard checks appropriate to the touched files:
  - `npm run build`
  - `php artisan test` or targeted tests where full suite failures are known
    unrelated.
  - `vendor/bin/pint --dirty`
- Capture remaining accepted-risk items, especially third-party cookie behavior
  and any hosting-only header work.

## Files to Create/Modify

- `ai/modules/lifehouse-audit-remediation/STATE.md`
- Optional:
  - `ai/modules/lifehouse-audit-remediation/artifacts/`
    - Final Lighthouse reports/screenshots.

No application changes should be introduced in this phase unless validation
finds a regression; any regression fix should be scoped and recorded.

## Success Criteria

- Final scores and findings are recorded.
- All phases are `Complete` or any remaining work is explicitly `Blocked` with
  an owner and reason.
- No unexplained console errors or broken requests remain on the audited page.
- The module can be closed by the orchestrator.

**STOP. Await phase approval.**

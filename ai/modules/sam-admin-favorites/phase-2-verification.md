# Phase 2: Verify repair

- Objective: Confirm the repair and document rollout requirements.
- Dependencies / entry: Phase 1 regression checks pass.
- Tasks: Run full application suite and Pint, inspect generated admin route and rendered view, record results and migration requirement.
- Verification: Full tests, focused security/ownership tests, formatter and diff checks.
- Exit: Evidence recorded; deliver repair for user validation.
- Risks: Unrelated workspace edits and existing test isolation. Preserve them; no production commands.
- Out of scope: Deploying, modifying credentials or existing favorites, unrelated API feature work.

Lifecycle: VERIFYING / Awaiting Validation. Automated verification completed: 911 passed, 5 skipped, 3,766 assertions; final regressions 6 passed / 37 assertions; Pint and diff checks passed. Rollout and manual validation steps are recorded in verification.md.



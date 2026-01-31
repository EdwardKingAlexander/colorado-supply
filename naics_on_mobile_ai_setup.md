# NAICS-on-Mobile: AI Module Setup Instructions

This document explains **where** and **how** to set up the required AI governance files
to proceed with the *NAICS-on-mobile* change in compliance with:

- `project_cannon.md`
- `CLAUDE.md`

This is an **instructional meta-file**. It does not contain feature plans or code.

---

## Correct Module

**Module name:** `frontend-catalog`

**Why this module**
- NAICS filtering affects **catalog discovery**
- The change is **mobile UX / presentation**
- No backend schema or ingestion changes are required
- Keeps scope narrow and auditable

Do **not** create a new module for this task.

---

## Required Directory Structure

Create the following directory structure **exactly**:

```
ai/
└─ modules/
   └─ frontend-catalog/
      ├─ MODULE_SCOPE.md
      ├─ TASKS.md
      └─ frontend-catalog.state.json
```

If `ai/` or `ai/modules/` does not exist, create them.

---

## File Responsibilities

### 1. MODULE_SCOPE.md
Purpose: **Hard boundaries**

This file defines what the module:
- Owns
- Explicitly does NOT own
- Is allowed to change
- Is forbidden to touch

It must:
- Be short
- Be explicit
- Prevent scope creep

No tasks. No phases. No code.

---

### 2. TASKS.md
Purpose: **Planner → Orchestrator handshake**

This file contains:
- Task ID
- User-facing problem statement
- Acceptance criteria
- Non-goals
- Phases (Phase 1 only initially)

Rules:
- No code
- No implementation details
- Stops after planning

AI planners must halt after producing or updating this file.

---

### 3. frontend-catalog.state.json
Purpose: **Machine-enforced control**

This file tracks execution state and prevents unauthorized progress.

Initial values should be:

```json
{
  "module": "frontend-catalog",
  "current_task": "naics-on-mobile",
  "current_phase": "planning",
  "approved_phases": [],
  "completed_phases": [],
  "orchestrator_approval": false,
  "last_updated": "<ISO-8601 timestamp>"
}
```

AI tools must read this file before acting.

---

## Workflow Summary

1. Create the directory and files
2. Define scope in `MODULE_SCOPE.md`
3. Stub the task in `TASKS.md`
4. Set state to `planning`
5. **STOP**

No coding may occur until:
- Phase 1 is explicitly approved
- `frontend-catalog.state.json` is updated accordingly

---

## Authority Chain

If instructions conflict:
1. `project_cannon.md`
2. `CLAUDE.md`
3. Module files in `ai/modules/frontend-catalog/`

---

## Final Note

This setup ensures:
- Context efficiency across AI tools
- Strict phase control
- Zero accidental execution
- Clean audit trail

Do not skip this step.

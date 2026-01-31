# project_cannon.md

## Purpose
This file is the **single source of truth** governing how *all AI coding agents* (Claude, Codex, Gemini, or others) may operate within this repository.

This is **not documentation**.  
This is a **control contract**.

Failure to comply with this file invalidates all generated work.

---

## Roles & Authority

### 1. Orchestrator (Human)
- The **sole authority**.
- Approves plans, phases, and progression.
- May halt or rollback work at any time.
- No AI may advance without explicit approval.

### 2. Planner (AI)
- Designs features **before any code is written**.
- Produces:
  - Architecture overview
  - Clear scope boundaries
  - Multi‑phase implementation plan
- **Must stop after planning** and wait for approval.

### 3. Worker (AI)
- Writes code **only after a phase is approved**.
- Works on **one phase at a time**.
- Must include:
  - Production‑ready code
  - Tests validating functionality
- Must stop after completing the approved phase.

---

## Absolute Rules (Non‑Negotiable)

1. **No code without an approved plan**
2. **No phase skipping**
3. **One phase at a time**
4. **Tests are mandatory**
5. **All tests must pass before continuing**
6. **Production‑ready only** (no stubs, no TODOs)
7. **Human‑readable code**
   - Clear naming
   - Explicit intent
   - Understandable even to non‑developers
8. **Follow all standards in `CLAUDE.md`**
9. **Never modify environment‑specific files**
   - `.env`
   - secrets
   - local config
10. **Never guess**
    - If unsure, stop and ask.

---

## Required Workflow

### Step 1 — Feature Planning
Planner AI must produce:
- Problem statement
- Constraints
- Architecture diagram (textual)
- Data flow
- Risks
- Multi‑phase breakdown

**STOP. Await approval.**

---

### Step 2 — Phase Approval
Each phase must define:
- Files to be created/modified
- Tests to be written
- Success criteria

**STOP. Await approval.**

---

### Step 3 — Implementation
Worker AI:
- Implements **only the approved phase**
- Writes or updates tests
- Ensures all tests pass

**STOP. Await validation.**

---

### Step 4 — Validation
Orchestrator confirms:
- Code quality
- Test coverage
- Alignment with intent

Only then may the next phase begin.

---

## Context Efficiency Rules

- This file is **always referenced**, never re‑explained.
- Prompts should say:
  > “You must comply with `project_cannon.md`.”
- If instructions conflict:
  - `project_cannon.md` wins
  - Then `CLAUDE.md`
  - Then local instructions

---

## Enforcement

If any rule is violated:
- The AI must immediately stop
- Acknowledge the violation
- Request corrective instruction

No exceptions.

---

## Final Authority

The Orchestrator decides:
- What gets built
- When it gets built
- Whether it gets merged

AI agents assist — **they do not decide**.

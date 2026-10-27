---
name: parallel-delegation
description: Default way of working for Colorado Supply (server + mobile). Use at the start of EVERY task to decide whether to do it alone, hand it to one subagent, or split it across parallel subagents. Goal is speed without losing correctness.
---

# Parallel Delegation

The owner's objective is **speed**. Subagents are the default whenever they
make a task finish sooner. Use exactly as many agents as the work needs: one
agent if one can do it, more only when the work splits into independent
pieces.

Applies to every agent (Claude Code, Codex, Muse, others) in both
repositories:

- Server: `C:\users\babyd\herd\colorado-supply`
- Mobile: `C:\users\babyd\programming\colorado-supply-mobile`

## 1. Decide (every task, before starting)

| Situation | Do this |
|---|---|
| A few tool calls, one file, a quick answer | Do it yourself. Spawning costs more than it saves. |
| One sizable job you'd otherwise do while blocking on it (long refactor, research, a full phase) and you have other work to do meanwhile | **One** subagent, in the background. |
| Two or more pieces with no shared files and no ordering between them (e.g. a `[server]` phase and an independent `[mobile]` phase; research on 3 libraries; tests for unrelated modules) | **One subagent per piece**, launched together in a single message so they run at the same time. |
| Pieces that edit the same files, or B needs A's output | Run them in order (one agent, or sequential agents). Never parallelize them. |
| Broad search across many files where you only need the conclusion | One read-only search subagent. |

When unsure, prefer fewer agents.

## 2. Split work safely

- **One owner per file.** Two agents must never edit the same file. Split by
  repository, module or directory.
- **Cross-repo work:** the natural split is one agent for the server repo and
  one for the mobile repo, each running that repo's checks.
- **Shared tracking files** (`ai/modules/*/STATE.md`,
  `ai/reference/remaining.md`) are updated **only by the orchestrating
  (parent) agent**, never by subagents.
- **Git:** each subagent commits only its own files, only in its own repo.
  The parent handles anything spanning both repos.

## 3. Write the brief (the subagent knows only what you tell it)

Every brief includes:

1. **Goal** and the exact scope (which phase file or task, which repo, which
   directories it may edit, what is out of scope).
2. **Pointers, not copies:** the phase file path, `AGENTS.md`, and the key
   source files to read.
3. **What is already known or ruled out**, so it doesn't redo your work.
4. **Rules:** follow the repo's `AGENTS.md`; no stubs/TODOs; never guess an
   API shape; never edit `.env` files; don't touch the other repo unless the
   brief says so; don't update STATE.md.
5. **Checks it must run** (server: `php artisan test`,
   `vendor/bin/pint --dirty`, `npm run build`; mobile: `npx expo lint`,
   `npx tsc --noEmit`, `npm test`, `npx expo-doctor`).
6. **Report format:** files changed, commits, exact check output, deviations
   from the spec with reasons, open questions for the owner.

## 4. After agents finish

- **Verify; don't trust.** Read the diff or rerun the key checks before
  reporting success. A subagent's "all passing" is a claim until you see it.
- Resolve conflicts or gaps yourself, then update STATE.md and report to the
  owner in one combined summary.
- Never present a pending agent's results as known. If asked, say it is
  still running.

## 5. The planning workflow still applies

Subagents do not bypass approval gates (see the Feature Planning Workflow in
`AGENTS.md`). Only approved phases are delegated for implementation.
Planning and research for a plan may be delegated at any time.

## Harness notes

- **Claude Code:** `Agent` tool (`run_in_background: true` for parallel
  work; several `Agent` calls in one message run concurrently;
  `subagent_type: "Explore"` for read-only searches).
- **Muse:** `subagent_spawn`.
- **Codex:** use its subagent / parallel task feature when available.
- **No subagent tool available:** do the work inline, in the same order
  this skill would have used.

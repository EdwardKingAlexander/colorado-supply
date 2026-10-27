# Phase 1 - Trace and reproduce

- Objective: Identify concrete causes of reduced result counts.
- Dependencies: Existing SAM repair plan and code.
- Entry criteria: Read prior master plan, state, and current phase.
- Scoped tasks: Trace defaults, API pagination, filtering, caching, and persistence; inspect history; reproduce suspected defects without running the fetching job.
- Verification: Request-sensitive pagination reproduction, official API contract, sanitized live read-only probes where available.
- Exit criteria: Findings distinguish verified defects from hypotheses and expected filtering effects.
- Risks: Live API outages or quota limits; local state contaminated by earlier tests.
- Out of scope: Production writes, notifications, application repairs, broad feature redesign.
- Lifecycle: COMPLETE
- Checklist: [x] Code trace and evidence complete.
- Evidence: `probe.php` reproduction and live probes on 2026-10-04; source locations and counts recorded in `findings.md`. No application behavior modified.

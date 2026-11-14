# SAM.gov API Key Format Fix — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-11-13 | Owner: "Complete sam-api-format-fix" |
| 1 | New key and host verification | Complete | 2026-11-13 | New `SAM-` key works on both hosts; full panel-equivalent fetch 104/104 on default host. See findings.md |
| 2 | Self-explaining credential errors | Complete | 2026-11-13 | Plus key redaction in exception text (2.6). Full suite 1540 passed / 5 skipped |

## Decisions

- 2026-11-13 — Default host stays `sam.gov/api/prod`. `api.sam.gov` works with
  the new key and did not rate-limit (0 × 429 in 104 calls), but 3 of 104
  requests timed out there vs 0 of 104 on the default. Switch with
  `SAM_API_BASE_URL` if ever needed; no code change.

## Owner to-do

- Set the new `SAM_API_KEY` in the Forge environment, then `config:cache`.

## Log

- 2026-11-13 — Investigated panel 400s. `sam:diagnose` reproduced HTTP 400.
  Isolated by varying one parameter at a time: any non-`SAM-` key gets 400
  `Validation failure`; a bogus `SAM-<uuid>` key gets 401 `Invalid API Key`.
  Configured key is an old-format bare UUID. `api.sam.gov` returns a proper
  401 for a bogus `SAM-` key (was empty 404 for old keys). Plan written.
- 2026-11-13 — Owner issued a new `SAM-` key. Probes: 200 on both hosts,
  CAGE lookup 200, `sam:diagnose` healthy.
- 2026-11-13 — Owner asked to complete the module. Phase 1: full NAICS + PSC
  fetches on both hosts (findings.md). Found the key in `laravel.log` via a
  cURL timeout message; masked locally and fixed in code.
- 2026-11-13 — Phase 2 built:
  - `SamApiClient`: `api_key_format` / `bad_request` / clearer 401 messages
    with the remedy; `hasCurrentKeyFormat()`; key redaction.
  - `SamMultiNaicsFetcher`: stops after the first credential failure.
  - `sam:diagnose`: key-shape labels, body-aware 400 verdict, remedy text.
  - Stale "gateway outage" comments corrected.
  - Tests updated: 3 existing tests used a 401 to mean "an ordinary failure"
    and now use 403/500, because a 401 intentionally stops the run.
  - Full suite: 1540 passed, 5 skipped, 0 failed. Pint clean on edited files.
- 2026-11-13 — All phases Complete; directory renamed with `-complete`.

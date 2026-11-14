# SAM.gov API Key Format Fix — Overview

## Problem

Since some time after 2026-10-04 (last successful live fetch), every SAM.gov
query from the admin Fetch SAM control panel fails with HTTP 400. The panel
shows the generic "SAM.gov API request failed" for every NAICS and PSC term.

## Root cause (reproduced live 2026-11-13)

SAM.gov now rejects old-format API keys **before** authenticating them.
`SAM_API_KEY` in `.env` is a bare 36-character UUID (the old key style). The
opportunities endpoint now only accepts keys of the form `SAM-<uuid>`.

Evidence, all against `https://sam.gov/api/prod/opportunities/v2/search` with
an otherwise identical, valid query:

| `api_key` sent | Response |
|---|---|
| (none) | 400 `Required parameter 'api_key' is not present.` |
| our real key (bare UUID) | 400 `Validation failure` |
| bogus bare UUID, 3-char, 40-char alnum | 400 `Validation failure` (same) |
| bogus `SAM-00000000-…` | **401** `Invalid API Key` |
| `SAM-` + our real key | 401 `Invalid API Key` |

So the 400 is a format constraint on `api_key` (the body is Spring's stock
`HandlerMethodValidationException` problem-detail), not a problem with dates,
`ncode`, `ptype`, `limit` or any other parameter: removing or changing those
never changed the answer. Prefixing our existing key does not work; a **new
key must be issued** from the SAM.gov account.

Secondary findings:

- **`api.sam.gov` is answering again for new-format keys.** A bogus `SAM-…`
  key gets a proper `401 API_KEY_INVALID` from
  `https://api.sam.gov/opportunities/v2/search`; an old-format key still gets
  the empty-bodied 404 we attributed to a "gateway outage" in July. That
  outage diagnosis was probably this same key-format change, rolled out on
  the official host first. To be confirmed in Phase 1 with the new key.
- The entity endpoint (`sam.gov/api/prod/entity-information/v3/entities`,
  used by `CageDirectory`) still returns 200 for any key, so CAGE lookups are
  not broken today, but they depend on the same old key and the same
  unofficial host.
- `php artisan sam:diagnose` misreads this failure: it reports "the route
  itself is the problem rather than the credential" because the keyless probe
  also returns 400. It only compares status codes, not bodies.
- `SamApiClient::buildErrorResponse()` has no message for 400 and the 401
  message does not say what to do, so the panel gave no hint that the key was
  the cause.
- GSA's public docs (open.gsa.gov) do not mention the format change; the
  changelog there ends in 2021.

## Constraints

- No change to API routes, response shapes or the mobile app. `[server]` only.
- Never log or display the API key (existing `***` masking stays).
- `.env` files are edited by the owner, not by agents.
- Keep the existing 404 semantics (`SamApiClient404SemanticsTest`) intact.

## Fix (two parts)

1. **Credential (owner action, the actual fix).** Issue a new public API key
   at SAM.gov (Profile → Account Details → Request Public API Key), put it in
   `SAM_API_KEY` locally and on Forge. Then verify which host to use.
2. **Code (so this cannot be silent again).** Recognise the key-format 400 and
   the 401, name the cause and the remedy in the panel, the failure alert
   email and `sam:diagnose`, and warn before fetching when the configured key
   is not in `SAM-` format.

## Data flow (unchanged)

```
Panel / 6am schedule → FetchSamOpportunitiesTool → SamMultiNaicsFetcher
  → SamApiClient::fetchPage() → SAM.gov   ← fails here with 400
  → failed_naics[] → state file → panel + SamFetchFailedNotification
```

Phase 2 changes only what `SamApiClient` reports on 400/401 and what
`sam:diagnose` concludes; the failure already propagates to the panel.

## Risks

| Risk | Mitigation |
|---|---|
| New key also fails on `sam.gov/api/prod` (unofficial host) | Phase 1 tests both hosts; `SAM_API_BASE_URL` switches host without a deploy of code |
| `api.sam.gov` enforces daily quotas the unofficial host did not (a full fetch is ~72–107 calls) | Phase 1 runs one full fetch and checks for 429s / rate-limit headers before choosing the host |
| SAM.gov keys may expire or be rotated again | Phase 2 makes the failure self-explaining; alert email names the remedy |
| Key-format rule is inferred from behaviour, not documented | Preflight check only **warns**; it never blocks a fetch |
| Entity endpoint starts validating keys too | Phase 1 verifies CAGE lookup with the new key |

## Phases

| # | Phase | Scope | Depends on |
|---|-------|-------|-----------|
| 1 | New key and host verification | `[server]` owner + agent, config only | — |
| 2 | Self-explaining credential errors | `[server]` code + tests | 1 (for the live check only) |

Phase 2's code and tests do not need the new key and can be built while the
owner is getting it.

## Sources

- https://open.gsa.gov/api/get-opportunities-public-api/
- Live probes recorded above (2026-11-13)

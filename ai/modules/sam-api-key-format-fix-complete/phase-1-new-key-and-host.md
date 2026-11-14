# Phase 1 — New key and host verification `[server]`

## Goal

Fetching works again, on a host we have verified with the new key.

## Steps

### 1.1 Owner: issue a new key

1. Sign in at sam.gov → Profile → Account Details → enter password →
   **Request Public API Key** (or regenerate). The new key should start with
   `SAM-`.
2. Set `SAM_API_KEY=<new key>` in the local `.env` and in the Forge
   environment. Run `php artisan config:clear` (Forge: redeploy or
   `config:cache`).

Done when: the key in `.env` starts with `SAM-`.

### 1.2 Agent: verify both hosts with the new key

Run the same one-term query (7-day window, `ncode=423840`, `limit=1`) against:

- `https://api.sam.gov/opportunities/v2/search` (official)
- `https://sam.gov/api/prod/opportunities/v2/search` (current default)

Record status, body excerpt, `totalRecords` and any rate-limit headers in
`findings.md` in this module.

Done when: at least one host returns 200 with a `totalRecords` value.

### 1.3 Agent: choose the host

- If `api.sam.gov` works: run one full default fetch against it
  (`SAM_API_BASE_URL` override, `clearCache`), watching for 429s. If clean,
  propose making it the default in `config/services.php` (base_url and
  entity_url) and correcting the "gateway outage" comments there and in
  `SamApiClient`. **Owner decides** before the default changes.
- If only `sam.gov/api/prod` works: keep the default; note it in `findings.md`.

Done when: host decision is recorded in `STATE.md`.

### 1.4 Agent: end-to-end check

- `php artisan sam:diagnose` → healthy.
- One fetch from the admin panel (default NAICS + PSC, 14 days): zero failed
  terms, result count in the same range as the 2026-10-04 run (~2,600).
- One CAGE lookup through `CageDirectory` succeeds with the new key.

## Files

- `.env` (owner only)
- `config/services.php`, `app/Services/SamApiClient.php` comments/defaults —
  only if the owner approves the host change in 1.3
- `ai/modules/sam-api-key-format-fix-complete/findings.md` (new)

## Tests

No new tests unless the default host changes; then update any test that
asserts the default URL and run `php artisan test --filter=Sam`.

## Success criteria

- Panel fetch completes with zero failed terms.
- Host choice is evidence-based and recorded.

## Risks / blockers

- Blocked until the owner supplies the new key.
- If the new key fails on both hosts, stop and report the exact bodies; do
  not guess further parameters.

STOP. Await phase approval.

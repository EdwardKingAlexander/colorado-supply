# Phase 2 — Self-explaining credential errors `[server]`

## Goal

When SAM.gov refuses the key (bad format, invalid, expired), the panel, the
alert email and `sam:diagnose` say so and say what to do. No more generic
"SAM.gov API request failed".

## Sub-phases

### 2.1 `SamApiClient`: classify 400 and 401

File: `app/Services/SamApiClient.php`

- In `buildErrorResponse()`:
  - 400 whose JSON `detail` is `Validation failure` **and** the configured key
    does not start with `SAM-` → `error_type: 'api_key_format'`, message:
    "SAM.gov rejected the API key format. Issue a new key at SAM.gov (Account
    Details → Request Public API Key) and update SAM_API_KEY."
  - Any other 400 → `error_type: 'bad_request'`, message includes SAM.gov's
    own `detail` / `errorMessage` text (first 200 chars) instead of the
    generic string.
  - 401 → keep `error_type: 'authentication'`; message: "SAM.gov refused the
    API key (invalid or expired). Issue a new key and update SAM_API_KEY."
- `categorizeHttpError()`: add `400 => 'bad_request'`.
- The key itself is never included in messages or logs.

Done when: unit tests in 2.4 pass.

### 2.2 Fail fast on credential errors

File: `app/Services/SamMultiNaicsFetcher.php`

A credential failure is identical for every term. When the first term
returns `api_key_format` or `authentication`, stop the loop and return that
single failure instead of making ~100 more doomed calls (and ~1–2 minutes of
waiting in the panel). Other failure types keep today's partial-success
behaviour.

Done when: a fetch with a refused key makes exactly one HTTP call.

### 2.3 `sam:diagnose`: correct verdict

File: `app/Console/Commands/SamApiDiagnose.php`

- `describeKeyShape()`: recognise `SAM-<uuid>` as "current format" and a bare
  UUID as "old format (rejected by SAM.gov since late 2026)".
- `verdict()`: before the generic "Unexpected HTTP" branch, add:
  400 with key + keyless body differs from keyed body (compare
  `body_excerpt`, not just status) → "API key rejected (format)", with the
  remedy. Keep the 401/403 branch; update its text to the same remedy.

Done when: against today's live state the command prints the key-format
verdict rather than "the route itself is the problem".

### 2.4 Tests

- `tests/Feature/Services/SamApiClientTest.php` (extend):
  - 400 `Validation failure` + old-format key → `api_key_format` and remedy text.
  - 400 `Validation failure` + `SAM-` key → `bad_request` carrying the detail.
  - 400 with another detail → message contains that detail.
  - 401 `{"errorMessage":"Invalid API Key"}` → `authentication` + remedy text.
- `tests/Feature/Services/SamPscSearchTermsTest.php` or a new fetcher test:
  credential failure on term 1 stops the run (`Http::assertSentCount(1)`);
  a 500 on term 1 does not.
- `tests/Feature/Console/SamApiDiagnoseTest.php` (extend): key-format 400
  verdict; 401 verdict; key shape descriptions.
- `tests/Feature/Jobs/FetchSamOpportunitiesJobAlertingTest.php`: alert for a
  credential failure contains the remedy text.

### 2.5 Checks

`php artisan test`, then `vendor/bin/pint` on the edited files only.

### 2.6 Added during implementation: redact the key from exception text

Found in the Phase 1 full fetch: cURL timeout messages quote the full URL,
key included, and flowed into the log, the panel, the state file and the alert
email. `SamApiClient::redactApiKey()` masks `api_key=` in network and
unexpected-exception messages. Test:
`SamApiClientTest` → "redacts the API key that cURL quotes in timeout messages".

## Out of scope

- Any panel layout change (the existing failure list already shows `error`).
- Blocking fetches on key format (the rule is inferred, so warn only).
- Mobile app (does not call SAM fetch).

## Success criteria

- With an old-format or invalid key, the panel shows one failure naming the
  key and the remedy, after a single API call.
- `sam:diagnose` names the credential as the cause.
- Full suite green; existing 404-semantics tests untouched.

## Risks

- SAM.gov may reword `Validation failure`; the fallback `bad_request` branch
  still surfaces SAM's own text, so the message degrades gracefully.
- Fail-fast changes the "one result per term" shape for credential failures;
  check `getSummary()` / `getFailedNaics()` consumers handle a short results
  array (they count what is present, so expected fine; covered by a test).

STOP. Await phase approval.

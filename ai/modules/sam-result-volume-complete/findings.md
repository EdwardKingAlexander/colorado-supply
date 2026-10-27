# SAM result-volume investigation - 2026-10-04

## Conclusion

There is a verified pagination defect that discards matching results beyond the first page. A separate, intentional August change made previously ignored NAICS restrictions effective, explaining why older broad result counts can be much higher. Current defaults and search semantics further restrict discovery. The evidence does not establish that pagination caused the user's particular recent low-count run: that requires a run exceeding 1,000 matches for an individual NAICS. The available local logs end in August, and the shared result file contains test fixtures.

No application code or search defaults changed during this investigation. The standalone probe bypasses jobs, persistence, caching, and notifications.

## 1. Confirmed defect: pagination skips almost all remaining pages

`app/Services/SamApiClient.php:97` passes `$pagesFetched * 1000` as offset. GSA defines offset as a zero-based **page index**, so the correct progression is 0, 1, 2, not 0, 1000, 2000.

Source: https://open.gsa.gov/api/get-opportunities-public-api/

The request-sensitive reproduction in `probe.php` simulates 2,500 matching records:

```
requested_page_indexes: [0, 1000]
expected_records: 2500
actual_records: 1000
missing_records: 1500
reported_success: true
reported_truncated: false
```

Run: `php ai/modules/sam-result-volume-complete/probe.php`.

Live verification against the configured sam.gov endpoint, NAICS 339113, September 1-30, 2026, notice types k/o/s/p:

| Limit | Offset | Total | Returned | Evidence |
|---|---|---|---|---|
| 2 | 0 | 51 | 2 | First two notices |
| 2 | 1 | 51 | 2 | Exactly notices 3-4 from the limit-4 request |
| 2 | 1000 | 51 | 0 | Out-of-range page returns HTTP 200, empty data |
| 4 | 0 | 51 | 4 | Confirms page index rather than record offset |

Run: `php ai/modules/sam-result-volume-complete/probe.php --live` (consumes four API requests).

The faulty pagination was introduced in commit `5f7166b1` on August 24. Earlier code had no pagination, so this is an incomplete repair of first-page truncation, not evidence that previously working pagination regressed.

## 2. Confirmed defect: incomplete results look complete

At `SamApiClient.php:131`, an empty page terminates the loop without marking the response truncated even when the API reports more matches. Later-page errors and page caps can set `truncated`, but `SamMultiNaicsFetcher` treats them as successful, caches them, and the tool never forwards the incompleteness to its final summary. Its `summary.total_records` is records received before deduplication, not SAM's advertised total (`FetchSamOpportunitiesTool.php:676`). An operator cannot reliably see missing pages from those counters.

## 3. Main historical scope change: NAICS filtering became real

Commit `a3570fce` on August 24 changed the ignored `naics` request parameter to the effective `ncode` parameter (`SamApiClient::buildQueryParams`). Prior repair evidence recorded nine identical 1,000-record responses, deduplicated to 1,000 broad notices. Those historical results were not restricted to the selected industries.

Independent current live counts, identical September 1-30 dates and k/o/s/p notice types:

| NAICS scope | SAM totalRecords |
|---|---:|
| No NAICS restriction | 14,872 |
| 339113 - Surgical Appliance and Supplies Manufacturing | 51 |
| 423840 - Industrial Supplies Merchant Wholesalers | 1 |

These are comparisons of search scope, not promises that all 14,872 notices are relevant to the business. They do show why restricting discovery to the company's selected NAICS codes can produce dramatically fewer results.

The comma-separated notice-type request worked for the measured case: combined total 51 equaled k=37 + o=8 + s=4 + p=2. No evidence of notice-type union loss in this probe.

Run: `php ai/modules/sam-result-volume-complete/probe.php --live --counts` (consumes seven API requests).

## 4. Existing restrictions and UX limitations

- **Panel defaults:** nine primary NAICS, seven days, nationwide, final maximum 1,000 (`FetchSamControlPanel.php:48`). Thirteen configured secondary NAICS are not initially selected.
- **No explicit all-industries search:** an empty NAICS override restores all 22 configured codes (`SamParameterResolver.php:103`). Keywords and PSC codes only filter notices already fetched under those NAICS; they do not discover matches outside them.
- **Keyword scope:** title and solicitation number only (`FetchSamOpportunitiesTool.php:587`), not description content. PSC, keywords, and set-asides combine with AND; terms within the keyword list combine with OR.
- **Final cap applies to saved results too:** `array_slice` at `FetchSamOpportunitiesTool.php:390` limits the result passed to the job. `FetchSamOpportunitiesJob.php:188` saves only those returned opportunities. This is not merely table pagination.
- **Scheduled query differs from panel:** seven days, Colorado, limit 100 (`routes/console.php`). Resolver callers that omit overrides also default to Colorado and limit 100. A daily scheduled result can therefore be much smaller than a nationwide manual result.
- **Small Business toggle means SBA only:** resolver returns `['SBA']`, excluding separate WOSB, SDVOSB and other set-aside codes. The helper text does explain SBA, but the broad toggle label may suggest otherwise.

These are existing behaviors; not all are regressions or defects. Changes should reflect the desired discovery scope rather than maximizing irrelevant counts.

## Verification and test blind spot

`php artisan test tests/Feature/Services/SamApiClientPaginationTest.php`: **6 passed, 22 assertions**. The tests feed response sequences independent of the requested offset and explicitly assert `[0, 1000, 2000]` at line 69. They encode the defect. The independent request-sensitive reproduction fails the actual completeness expectation.

Local shared state is dated August 25 and contains synthetic NAICS 111111/222222/333333, so its zero count is not evidence of a current live failure. No present production logs or exact recent user query were available. Live probes succeeded; an endpoint outage did not cause these diagnostic requests to fail.

## Recommended repair sequence

1. Correct page indexes and replace the misleading pagination test with request-sensitive fixtures. Mark premature empty pages as incomplete; propagate incomplete results and advertised totals through cache, summary, and UI.
2. Separate result ingestion from display limits, with explicit total/fetched/filtered/displayed counts. Avoid losing records merely because the display is capped.
3. Add an explicit all-industries mode and decide whether PSC/keyword searches should discover opportunities independently of selected NAICS. Make the date range, industry scope, and scheduled/manual defaults visible and consistent where intended.

Implementation remains a separate task; no repair or restored-volume claim is made here.

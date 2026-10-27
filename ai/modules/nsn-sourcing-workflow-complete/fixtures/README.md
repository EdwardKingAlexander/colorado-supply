# PUB LOG acceptance fixtures

Source: official DLA archives linked from the reading room, release listed 2026-10-01, downloaded and verified 2026-10-04 America/Denver. Exact URLs, file sizes, headers, SHA-256 and counts are in ../source-measurements.json. The full archives remain in ../artifacts/2026-10/ for reproducible research and are not application seed data.

`acceptance-cases.json` is the compact downstream test fixture. Its source rows are unchanged except for an added `source_record` field: one-based CSV data-record position, excluding the header. This is not necessarily a physical line number. Candidate-sample `.CSV.json` files retain broader research evidence and should not all be loaded into each application test.

Verified scenarios:

| Scenario | Source evidence | Expected behavior |
|---|---|---|
| NSN and NIIN equivalence | FSC 5940 + NIIN 000000045 | Both 5940-00-000-0045 and 000000045 find the same item |
| Multiple CAGEs | NIIN 000000045 has CAGE 24039 and 0CEN3 | Display both; preserve the leading zero |
| Company join | CAGE 24039 resolves to VARO LLC in this snapshot | Snapshot identity shown, live verification remains pending |
| Ambiguous part/reference number | BHMA-A156.4 occurs under NIINs 000000057 and 000000058 (and others in full data) | Never choose one NSN automatically |
| Inactive design reference | 000000045 / UK 60A890D255 / 24039 has RNCC 3 and RNVC 9 | Preserve and label relationship codes, not a current-production claim |
| Cancelled item | 4935-00-000-0012 has NIIN status 4 | Display cancelled-without-replacement catalog status; production remains unknown |
| Missing company | Synthetic: omit real CAGE 24039 from the fixture's company tables | Preserve the reference and show unresolved company |
| No match | Synthetic: query 9999999999999 against this small fixture | No match in loaded snapshot; do not claim invalid/excluded globally |
| Invalid input | Synthetic: abc5940000000045 | Reject, instead of removing letters and accepting |

The odd-looking values in early source records are authentic archive values, not invented test data. Do not clean or reinterpret them. The fixture proves structure and joins, not real-world commercial suitability. Codes in `definitions` are copied from official P_HELP data. That member requires Windows-1252 decoding for this release; it is not safely readable as strict UTF-8.

Verification: `python ai/modules/nsn-sourcing-workflow/verify_fixtures.py` passes 10 assertions covering the examples, join completeness and complete-file probe results. Research benchmarks write only isolated databases inside this ignored module. Never run them against a production connection.

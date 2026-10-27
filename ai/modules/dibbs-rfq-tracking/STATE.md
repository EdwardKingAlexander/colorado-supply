# DIBBS RFQ Tracking — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-10-04 | Approved by user 2026-10-04 |
| 1 | Discovery spike: layouts, access, fixtures | Complete | 2026-10-04 | Automated access WORKS (honest UA). Approved by user |
| 2 | Ingestion foundation | Complete | 2026-10-04 | Live: 10 files / 10,961 RFQs imported, 0 errors. Approved |
| 3 | RFQ tracking UI and pipeline | Complete | 2026-10-04 | Approved by user |
| 4 | NSN catalog and approved-source matching | Complete | 2026-10-04 | Approved by user (catalog data still to be loaded) |
| 5 | Daily digest email | Complete | 2026-10-04 | Approved by user |
| 6 | Batch quote export | Blocked | 2026-10-04 | Built + 26 tests. 6.3 waits on the user's DIBBS registration |
| 7 | Hardening and operational readiness | Awaiting Validation | 2026-10-04 | Health, freshness alert, prune, runbook, E2E test; perf far under targets |
| 8 | Approved-source report and CAGE lookup | Awaiting Validation | 2026-10-04 | 95 of top 100 approved CAGEs named live; 8 tests |

## Decisions

- 2026-10-04 — User has a DIBBS vendor account (CAGE + login). Corrected later the same day: registration is still in progress with DIBBS customer service.
- 2026-10-04 — Data access: automated daily download + manual upload fallback.
- 2026-10-04 — Scope: track/filter, NSN catalog matching, daily digest, batch quote export.
- 2026-10-04 — App never stores DIBBS credentials or submits quotes; honest
  User-Agent only (no browser spoofing); upload fallback if DLA blocks.

## Log

- 2026-10-04 — Plan written. Research: daily files `inYYMMDD.txt` (140-char
  fixed width), `bqYYMMDD.zip` (batch-quote templates + approved sources),
  listed at `/RFQ/RFQDates.aspx?category=recent`; layouts at
  `/Rfq/RfqFileDefs.aspx` (banner-gated, not yet captured); viewing needs no
  login, quoting does; DLAD 5.202 lets DLA post on DIBBS instead of SAM.gov.
- 2026-10-04 — User approved the plan and started Phase 1.
- 2026-10-04 — Phase 1 done (findings.md). Honest client not blocked; consent
  banner per host (www + dibbs2), files on dibbs2/Downloads/RFQ/Archive.
  Layouts captured from DLA's own pages (no operator help needed): index 140-char
  fixed width (13 fields), AS 4-col CSV, batch quote 121-col CSV (127-col spec),
  75-line upload limit. Unique key (solicitation, PR); reissues occur. Volume
  378–5,436 lines/day; 78% of 10-02 lines in our FSCs. AMSC table from DFARS PGI
  217.75. Fixtures committed: tests/Fixtures/dibbs/{in,bq,as}261002.txt.
  ~14 DIBBS requests total, >=2 s apart.
- 2026-10-04 — User approved Phase 1 (incl. compliance note) and started Phase 2. Contact address for the User-Agent not given: made configurable (DIBBS_USER_AGENT).
- 2026-10-04 — Phase 2 implemented: migrations (dibbs_files, dibbs_rfqs,
  dibbs_approved_sources, dibbs_quote_templates), models + factories,
  InFileParser (byte-sliced 140-char layout, Latin-1), BatchQuoteFileParser
  (DLA `","` separator), BatchQuoteZip, DibbsFileImporter (sha256-idempotent,
  upsert on natural keys, reissues keep first_seen, bq<->RFQ linking in either
  order), DibbsClient (per-host consent, no redirects on downloads, content
  validation, honest UA, DLA hosts only), DibbsDownloadJob (backfills last 5
  posting days; alerts on listing/import failure, not on missing files),
  schedule 07:00 America/Denver, DibbsUpload page (/admin/dibbs-upload),
  DibbsIngestionFailedNotification, config/dibbs.php. 31 DIBBS tests.
- 2026-10-04 — Live run: listing + 10 files (09-25..10-02), 56 s, all imported.
  Found two real-data issues and fixed them: (1) DLA local stock numbers have
  letters in the NIIN (5330LN0035636, 9535LLNCA9756): 74 valid RFQs had been
  rejected; (2) DLA does not escape quotes inside values (part number
  `RK 800M4-T 1-1/2"`), which broke CSV parsing; now split on `","`.
  Re-imported from stored copies: 10,961 RFQs (74 LSNs), 11,497 quote rows
  (0 unlinked), 11,335 approved sources, 0 row errors, 0 failed files.
  Full suite: 786 passed, 5 skipped, 1 failed = VendorResourceTest contacts
  (pre-existing flaky, passes 4/4 in isolation; documented in sam-fetch-repair-complete).
- 2026-10-04 — User set DIBBS_USER_AGENT (company + contact; verified not browser-like), approved Phase 2, started Phase 3.
- 2026-10-04 — Phase 3 implemented: DibbsRfqResource (/admin/dibbs-rfqs, read-only;
  list with mobile summary column, default filters "in our product codes" +
  "hide closed", sort by return-by; filters: closing within, competition
  open/approved-source, set-aside, AMSC, status (untracked = new), automated
  award, latest file, quantity range; persisted per session), view page
  (RFQ / DIBBS links / tracking / approved sources / activity history),
  actions set status / assign owner / notes (table + view page), bulk
  reviewing / no-bid, DibbsRfqStats widget. dibbs_rfq_trackings table
  (company-scoped via CompanyScope; admins act for the home company),
  activity-logged (log name "dibbs"). DIBBS URLs verified live: rfqrec.aspx?sn=,
  RFQNsn.aspx?value=, RA/Quote/QuoteFrm.aspx?sn=, PDF at
  dibbs2/Downloads/RFQ/{last char of solicitation}/{sol}.PDF (2 examples).
- 2026-10-04 — Found and fixed a pre-existing admin-wide bug: stale published
  Filament table views (resources/views/vendor/filament-tables, published
  2025-10-05, never customized) lacked Filament 4.11's `.fi-ta-main` wrapper,
  so every table with rows laid out header/filters/rows/pagination side by
  side. Removed (same approach as commit 8eee789d for the MFA input).
- 2026-10-04 — Real data: list warm ~0.7 s server (full admin page, local
  debug), detail 0.16 s. Full suite: 794 passed, 5 skipped, 2 failed, both
  pre-existing flakes unrelated to DIBBS: QuotePolicyTest "super_admins can
  access quotes" (QuoteFactory picks status 'ordered' 1 in 5, which correctly
  blocks convertToOrder) and CustomerResourceGooglePlacesTest billing address
  (passes in isolation; order-dependent).
- 2026-10-04 — User approved Phase 3 and started Phase 4.
- 2026-10-04 — Phase 4 implemented: catalog import (Mil-Spec Parts → Import
  catalog; CSV/XLSX, preview → confirm, template; manufacturers/suppliers by
  CAGE then name, created when missing; NSNs normalized to the catalog's dashed
  format), dibbs_rfq_matches + DibbsRfqMatcher (nsn_catalog 100,
  approved_source 70 via manufacturer or supplier CAGE; full rebuild, 34 ms on
  10,961 RFQs), RebuildDibbsMatchesJob (unique; queued by MilSpecPart /
  Manufacturer / Supplier hooks only when NSN/CAGE change; suspended during bulk
  import), rebuild after DIBBS downloads/uploads and on demand. RFQ list: Match
  column (sortable by score), "Catalog match" filter, Rebuild matches action;
  detail: "Your catalog" (suppliers, last price paid), ✓ on approved sources we
  buy from + "Your source" name, "Add NSN to catalog" action.
  Deviation: the weak FSC signal is not stored as a match (it would add a row
  for ~70% of RFQs; "In our product codes" already filters on it).
  Bug found in testing: all-digit CAGE keys were lost by Collection::merge()
  renumbering; fixed with key assignment.
  Tests: 12 new (52 DIBBS total). Full suite 833 passed, 5 skipped, 1 failed
  (QuotePolicyTest flake, pre-existing).
- 2026-10-04 — Real data: catalog empty, 0 manufacturers/suppliers with CAGE,
  so 0 matches. 7,577 open RFQs in our product codes; 4,439 carry approved-
  source lists. Top approved CAGEs on open RFQs: 07TA6 (114 NSNs), 75Q65 (76),
  0PMN3 (68), 56878 (54), 73197 (45); DLA's as-file often omits company names.
- 2026-10-04 — User approved Phase 4 and started Phase 5.
- 2026-10-04 — Phase 5 implemented: DibbsDigestBuilder (sections: in catalog,
  approved source, in product codes [capped, "and N more"], closing soon),
  DibbsDailyDigestNotification + emails/dibbs-digest (compact, phone-friendly),
  DibbsDailyDigestJob (once per posting file via dibbs_files.digest_sent_at,
  once per day via cache, no empty digests, failed sends not marked sent),
  queued by DibbsDownloadJob after a new index file is imported + matched;
  fallback schedule 08:30 America/Denver. Config dibbs.digest.* (recipients
  DIBBS_DIGEST_EMAILS → alert email; min_match; section_limit; closing days).
  Deviation (from real data): "closing soon" = RFQs we're reviewing/quoting +
  untouched catalog/approved-source matches, NOT every product-code RFQ: that
  was ~4,300 RFQs in a 3-day window. Quoting RFQs added (the plan excluded
  them; a "you're quoting this and it closes tomorrow" reminder is the most
  useful line in the email).
  Real data (Oct 2 file): "DIBBS RFQs: 311 new (Oct 2)", built in ~80 ms,
  ~84 KB HTML; rendered and inspected, not sent (local Mailgun send failures
  are expected per project memory).
  Tests: 9 new (61 DIBBS). Full suite 843 passed, 5 skipped, 0 failed.
- 2026-10-04 — User approved Phase 5 and started Phase 6.
- 2026-10-04 — Phase 6 implemented: dibbs_quoting_profiles + quote columns on
  trackings; DibbsQuotingProfile (representations with DLA's code lists, never
  defaulted, confirmation required on every save, activity-logged);
  BatchQuoteExporter (validates, fills only vendor columns into DLA's preserved
  bq rows, Latin-1 + CRLF, `","` format, <=75 lines per file with -partN files
  zipped, marks quoted + snapshot of exact lines + who confirmed); pages
  DIBBS Quoting Profile (/admin/dibbs-quoting-profile) and DIBBS Quote Worksheet
  (/admin/dibbs-quote-worksheet: inline price/delivery, edit-quote modal with
  "pick an approved source", per-row Ready?/problems, Review & export with
  representation summary + required attestation, download); RFQ list/detail
  "Add to quote worksheet" (pre-fills DLA delivery days + our approved
  CAGE/part, never price).
  v1 scope (from BatchFormat.aspx rules): BI only; domestic end product (D is
  valid under every col-69 indicator); part offered 1/3/4/5 (1 checked against
  the approved-source list); quality codes 6/7/8 per col-117 requirement.
  Blocked with a reason: BW/AB, foreign end products, alternate products,
  CAGE/PN corrections, quality 1/2, HUBZone waiver, joint ventures, zero price.
  Bug found in testing: numeric-string array keys ('1','3'…) become ints, so a
  strict in_array never matched and every approved-source quote was blocked;
  fixed.
  Tests: 26 new (golden column-by-column test on a real DLA row). Full suite
  867 passed, 5 skipped, 2 failed = known flakes (VendorResourceTest phone,
  QuotePolicyTest random status), both pass on rerun.
  OPEN — 6.3: the user must complete the quoting profile, export one low-value
  quote, upload it in DIBBS (Quoting → Batch Quote Upload) and report whether
  DIBBS accepts it. Not done by the app or by Claude (needs the user's DIBBS
  login and real representations).
- 2026-10-04 — User: DIBBS registration is stuck with customer service; 6.3 (real upload) deferred until the account exists. Phase 7 started.
- 2026-10-04 — Phase 7 implemented: DibbsHealth (latest file, business days
  behind, last run + consecutive failures recorded by DibbsDownloadJob, next
  run), Ingestion health panel on Upload DIBBS File + red stat on RFQ list when
  stale, `dibbs:check-freshness` (weekdays 10:00, alerts after >2 business
  days), `dibbs:prune` (weekly; keeps anything tracked beyond "new" or matched
  by ANY company; raw files after 60 days, import rows kept; --dry-run),
  stored_path nullable, config dibbs.retention.*, RUNBOOK.md.
  Performance (real data, SQLite): import of 5,429-line day 0.23 s (target
  <60 s), match rebuild 7 ms, list query 13 ms (target <500 ms), digest 34 ms.
  Tests: 10 new incl. an end-to-end business day (download → import → match →
  digest → worksheet → export). 97 DIBBS tests; full suite 879 passed,
  5 skipped, 0 failed.
  Live: real download run picked up a new posting day (10-04: in + bq), health
  recorded ok, freshness current. Real prune dry run: 1 RFQ eligible.
  Open: 6.3 real DIBBS upload (user registration pending).
- 2026-10-04 — Wrap-up item 5 (user request), done:
  * SAM fetch schedule now ->timezone('America/Denver') (was 06:00 UTC =
    midnight Mountain); test asserts the timezone.
  * Flaky tests fixed: QuotePolicyTest (fixed quote status), VendorResourceTest
    (factories used Faker phoneNumber() with extensions that tel() rejects),
    DibbsDailyDigestTest (DibbsFileFactory named files from a random date that
    could equal the fixture day in261002 -> name now follows posting_date).
    Verified 15/15 and 20/20 clean reruns.
  * Stale Filament overrides: removed 122 untouched copies published
    2025-10-05 (65 differed from Filament 4.11, 57 identical); kept the 4
    deliberately customized (layout base/simple, page index, user-menu).
    Visually checked dashboard, product edit form, DIBBS view + modal.
  * Phase 8 (approved-source report + CAGE lookup), see phase file.
  Incident: while creating a `CageEntity` model I overwrote an untracked file
  of the same name from separate in-progress PUB LOG catalog work (its own
  `cage_entities` migration already ran). Restored it from its usage (no
  timestamps, summary/address cast to array); PUB LOG's 26 tests pass. Mine
  renamed to SamCageLookup / sam_cage_lookups.
  Full suite: 887 passed, 5 skipped, 0 failed.
- 2026-10-04 — Phase 8 live: entity API pages 10 results per request (first
  version read only page 0 and recorded 90 real companies as not found; fixed
  and re-run: 95/100 named in 9 requests). Top approved sources on open RFQs in
  our product codes are medical (Cardinal Health 313 RFQs, Medline 181, Bound
  Tree 178, Owens & Minor, McKesson, Teleflex), then fasteners (PB Fasteners,
  Hi-Shear, Air Industries), Revision Military (4240), Oshkosh Defense.
- 2026-10-04 — Added `php artisan dibbs:download [--days=1..30] [--force]` (runs DibbsDownloadJob now; prints imported / already imported / not posted / failed). For a fresh production DB: `--days=25` once after migrate. 7 tests; live run: 10 already imported. Full suite 894 passed, 5 skipped. Not committed (user: another agent still working).

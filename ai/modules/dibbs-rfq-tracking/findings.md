# Phase 1 Findings — DIBBS layouts, access, fixtures (2026-10-04)

All captures are in `reference/` (git-ignored with the rest of `ai/`). Probe:
`probe.php` (honest UA, consent banner accepted once, ≥2 s between requests).
Parsing/analysis: `analyze_files.py`, fixtures: `make_fixtures.py`.

## 1.3 Automated access verdict: WORKS (honest client)

- User-Agent `ColoradoSupplyRfqTracker/0.1 (DIBBS file-access research probe)`.
  **Not blocked** by the F5 firewall. No browser spoofing needed or used.
- Flow:
  1. `GET https://www.dibbs.bsm.dla.mil/RFQ/RFQDates.aspx?category=recent`
     → `302` → `/dodwarning.aspx?goto=...` (DoD consent banner, ASP.NET form
     `FrmBody`, submit `butAgree=OK` plus hidden `__VIEWSTATE`-style fields).
  2. `POST` the form (action is relative to the banner URL) → cookies
     `SessionId`, `di`, `TS01523db3` set → original page served.
- **Files live on a second host**: `https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/{name}`.
  It shows **its own banner** (cookies are per host) and serves it **without a
  redirect**, so the form action must resolve against the requested URL.
- Missing file: observed both `404` (no redirect) and a `302` to a plain
  `http://dibbs2...:80` URL that never connects (21 s timeout). **Client rule:**
  never follow redirects on file downloads; anything but `200` with non-HTML
  content = missing.
- Content types: index `text/plain; charset=ISO-8859-1`; zips
  `application/octet-stream`. Validate content (not status) before import:
  the banner is `200 text/html`.
- Requests in this phase: ~14, all spaced ≥2 s.

## 1.1 Official layouts (from `/Rfq/RfqFileDefs.aspx`, `/refs/help/Quoting/Batch/BatchFormat.aspx`)

### Index file `inYYMMDD.txt` — fixed width, 140 chars, CRLF, latin-1

| # | Field | Width | 0-based offset | Notes |
|---|-------|------:|------:|-------|
| 1 | Solicitation # | 13 | 0 | 9th char `T`/`U` = automated-award eligible (no textual input considered) |
| 2 | NSN / Part # | 46 | 13 | 13-digit NSN when item type = 1 |
| 3 | Purchase Request # | 13 | 59 | observed 10 digits + padding |
| 4 | Return By Date | 8 | 72 | `MM/DD/YY` |
| 5 | File Name | 19 | 80 | solicitation document, e.g. `SPE1C126T1775.pdf` |
| 6 | QTY | 7 | 99 | zero-padded |
| 7 | Unit of Issue | 2 | 106 | |
| 8 | Nomenclature | 21 | 108 | |
| 9 | Buyer Code | 5 | 129 | |
| 10 | AMSC | 1 | 134 | see AMSC table below |
| 11 | Item type | 1 | 135 | `1` NSN, `2` part number |
| 12 | Small-business set-aside | 1 | 136 | `Y` SB, `H` HUBZone, `R` SDVOSB, `L` WOSB, `A` 8(a), `E` EDWOSB, `N` unrestricted |
| 13 | Set-aside % | 3 | 137 | |

Verified: all 378 lines of `in261002.txt` (and 5,436 of `in261001.txt`) are
exactly 140 chars and parse cleanly with these widths.

### Approved source file `asYYMMDD.txt` (inside `bqYYMMDD.zip`)

Quoted CSV, 4 columns: `NSN, Approved Source CAGE, Part Number, Approved Source Company Name`.
10-02: 441 rows, 224 NSNs, 266 CAGEs; 224 of 351 RFQ NSNs have ≥1 approved source.

### Batch quote file `bqYYMMDD.txt` (inside `bqYYMMDD.zip`) and upload format

- Quoted CSV (`"…","…"`), **121 columns per row** in DLA's files (spec lists
  127 numbered columns incl. reserved; full spec text in
  `reference/BatchFormat.txt`, column table in `reference/BatchFormat.columns.tsv`,
  spec last updated 30-APR-2024).
- DLA pre-fills ~30–35 columns per row (solicitation, return-by, bid type `BI`,
  days quote valid `90`, FOB/inspection `D`, line #, PR, NSN, UI, **quantity
  (49)**, **required delivery days (51)**, …).
- Vendor fills: Quoter CAGE (6), Quote-for CAGE (7), **Unit Price (50)**,
  Delivery Days (51) if different, Manufacturer/Dealer (102), Actual
  manufacturing source CAGE (103), part number offered (106–108), plus
  **legal representations**: small-business reps (13), affirmative action
  (21–22), Buy American / trade agreements (62–72), child labor
  certification (120), hazardous material (65) … 34 columns are Mandatory,
  63 Conditional.
- **Upload limit: 75 lines per file**; larger files are not processed. The
  export must split into ≤75-line files.
- Rows per (solicitation, PR) can exceed one (10-02: 395 bq rows for 378
  index lines).

## 1.4 Timing, retention, identity

- Listing ("Recent RFQs") showed **22 posting days** (09-04 → 10-02-2026), i.e.
  about a month of business days; plus `bqothers.zip` (all other open RFQs in
  batch format).
- DLA: "Posting of links to the current day files will not display until the
  next day because file updates occur throughout the day." The 10-02 zip's
  entries are stamped 10-03 00:15. **Schedule the download early next morning**
  (e.g. 06:00 MT) for the previous posting day; also backfill any listed day
  not yet imported.
- Volume varies a lot: 10-01 = **5,436 lines**, 10-02 = 378. Plan for ~100k
  lines/month: indexes on FSC, NSN, return-by; retention policy (Phase 7).
- **Unique key: (solicitation, purchase request)**: 0 duplicates within a
  day. Reissues happen: 3 of 10-02's lines also appeared on 10-01, so upsert
  on that key and keep `first_seen`.
- Some return-by dates are already past when posted (oldest seen 06/18/26):
  importer must not assume future dates.
- In our product codes: **297 of 378 lines (78%)** on 10-02 fall in our FSCs/
  groups (config `psc_groups` + `psc_codes`). Top FSCs: 5320 rivets (48), 5305
  screws (25), 4820 valves (23), 5310 nuts (20), 5325 (19).

### AMSC (DFARS PGI 217.75), grouped for a distributor

| Group | Codes | 10-02 count | Meaning for us |
|-------|-------|------:|-----|
| Open competition | **G**, **Z**, **L**, O | 170 (45%) | G complete gov data; Z commercial/COTS; L low-dollar (screened for known sources only) |
| Approved source only | **B, C, D, H, K, M, N, P, Q, R, S, T, V**, A, U, Y | 208 | Must offer an approved source's exact part (AS file); T = QPL; C = engineering source approval |

Full one-line definitions: A questionable data rights · B source control drawing ·
C engineering source approval · D data unavailable/uneconomical · G full data, no
restrictions · H insufficient data (under review) · K class 1 castings/forgings ·
L low annual buy value · M master tooling · N special test/ultra-precision ·
O not yet assigned · P no data rights, can't buy · Q inadequate data after 12+ months ·
R gov doesn't own rights · S militarily sensitive, approved sources · T QPL ·
U breakout not cost-effective · V high reliability · Y unstable design · Z commercial/NDI.

## 1.2 Fixtures (committed, public data)

`tests/Fixtures/dibbs/`, built from the 10-02 files by `make_fixtures.py`:
- `in261002.txt`: 50 lines (48 solicitations), DLA bytes preserved (CRLF,
  latin-1). Covers all 6 set-aside values and all 13 AMSC values seen that day.
- `bq261002.txt`: the 52 bq rows for those (solicitation, PR) keys.
- `as261002.txt`: 70 approved-source rows for those NSNs (39 of 47 NSNs covered).

## 1.5 Compliance note (for user review)

- Banner text saved verbatim: `reference/dod-consent-banner.html`. It is the
  standard DoD Notice and Consent Banner: USG-authorized use only; consent to
  monitoring/interception/search; not private. No statement about automated
  retrieval. Application POC: `DIBBSBsm@dla.mil`.
- DLA publishes these daily files specifically for vendors to download and use
  "for any other off-line evaluation routines" (RfqFileDefs page).
- Our client: honest User-Agent, banner accepted once per host per session,
  ≥2 s between requests, ~3–5 requests per day in production (listing + 2
  files, plus backfill when days are missed).
- Recommendation: proceed with automated download (Phase 2.4) as planned. For
  extra certainty, the user may email `DIBBSBsm@dla.mil` to confirm automated
  daily download of the public RFQ files is acceptable; not a blocker.
- Production User-Agent should name the company and a contact address the
  user chooses (config), e.g. `ColoradoSupplyRfqTracker/1.0 (+contact@…)`.

## Corrections from the Phase 2 live run (2026-10-04)

- **NSNs can contain letters**: DLA local stock numbers such as
  `5330LN0035636` and `9535LLNCA9756` (4-digit FSC + 9-character NIIN). 74 of
  10,961 lines over 09-25..10-02. They are valid RFQs.
- **Quotes inside values are not escaped** in bq/as files (e.g. part number
  `RK 800M4-T 1-1/2"`). Split rows on the `","` separator, not with a CSV
  reader.

## Impact on later phases

- Phase 2: two hosts, per-host banner, no redirects on downloads, content
  validation, unique key (solicitation, PR) with upsert, latin-1 decoding,
  CRLF, chunked inserts for 5k+ line days, schedule early morning + backfill.
- Phase 3: filters for AMSC groups (open vs approved-source) and the
  automated-award flag (solicitation char 9 = T/U).
- Phase 4: approved-source match = AS file CAGE ∈ our manufacturers/suppliers.
- Phase 6: split exports into ≤75-line files; representations columns need
  explicit operator review; start from DLA's preserved bq rows.

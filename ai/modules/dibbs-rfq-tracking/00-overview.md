# DIBBS RFQ Tracking — Overview

## Problem

DLA (Defense Logistics Agency) buys most of the hardware, fasteners, valves,
electrical and tooling items Colorado Supply sells. Many of those buys are
small purchases posted **only** on DIBBS (DLA Internet Bid Board System), not
SAM.gov: DLAD 5.202 lets DLA satisfy synopsis requirements by posting on DIBBS.
The SAM.gov integration therefore can never see them. A 360-day SAM.gov survey
(2026-10-04) found only 21 screw (PSC 5305) postings in a year, far below DLA's
real buying volume.

## Goal

A daily DIBBS pipeline inside the admin panel that:

1. **Tracks & filters RFQs** — ingests DLA's daily RFQ files; filters by our
   FSCs, set-aside, due date, quantity and AMSC; status pipeline per RFQ.
2. **Matches our NSN catalog** — flags RFQs for NSNs we can source (MilSpecPart)
   and RFQs where a manufacturer we buy from is an approved source.
3. **Emails a daily digest** of new matching RFQs before they close.
4. **Exports batch quote files** in DLA's format so many RFQs can be quoted in
   one upload to DIBBS.

## User decisions (2026-10-04)

- The company **has a DIBBS vendor account** (CAGE + login): quoting is in scope.
- Data access: **automated daily download with a manual-upload fallback**.
- Scope: all four capabilities above.

## Constraints and compliance

- **No public API.** DLA publishes daily files listed at
  `https://www.dibbs.bsm.dla.mil/RFQ/RFQDates.aspx?category=recent`:
  - `inYYMMDD.txt` — fixed-width RFQ index (140-char records): solicitation
    number, NSN, nomenclature, quantity, unit, return-by date, set-aside,
    buyer code, AMSC, etc. (layout: `/Rfq/RfqFileDefs.aspx`).
  - `bqYYMMDD.zip` — batch-quote CSV templates (`bq*.txt`) and approved-source
    files (`as*.txt`).
  - `caYYMMDD.zip` — solicitation PDFs (not ingested).
  Exact layouts are behind the DoD consent banner and were **not** retrievable
  during planning; Phase 1 captures them verbatim.
- **Viewing RFQs needs no login**; quoting does. The app will **never store
  DIBBS credentials and never submit quotes**. It produces files; a person
  reviews and uploads them in DIBBS. Quotes carry legal certifications and
  representations; a human must own every submission.
- **DoD consent banner.** Automated access must accept the "Warning and
  Consent" banner, as every browser visitor does. The client identifies itself
  honestly (User-Agent naming Colorado Supply and a contact email), waits
  between requests (≥2 s), downloads only the public daily files, and fetches
  once per day. **We do not spoof browser User-Agents** or otherwise evade
  DLA's firewall (one open-source project does; we deliberately don't). If
  DLA blocks the honest client, the manual-upload fallback is the path.
- Missing files return `302 → HTML` rather than 404 (observed by an existing
  open-source client): the downloader must validate content, not status codes.
- **Multi-tenancy:** RFQ data is public and global; tracking status, notes,
  catalog and quotes belong to the company. Admin-panel feature, admin guard.
- Mail runs through Mailgun; local-dev send failures are expected and not a
  production issue (see project memory).

## Architecture (textual)

```
Scheduler (daily, after DLA posts) ──► DibbsDownloadJob
     │  DibbsClient: consent banner, cookies, polite interval, honest UA
     │  lists recent dates ─► downloads missing inYYMMDD.txt + bqYYMMDD.zip
     ▼
DibbsFile (one row per file: date, type, source auto|upload, checksum, status)
     ▼
Importers (idempotent by file checksum):
  InFileParser   ─► dibbs_rfqs        (one row per solicitation line / NSN)
  BqFileParser   ─► dibbs_quote_templates (DLA's pre-filled batch-quote row)
  AsFileParser   ─► dibbs_approved_sources (NSN ↔ CAGE ↔ part number)
     ▼
Matcher ─► dibbs_rfq_matches (reason: fsc | nsn_catalog | approved_source_cage)
     ▼
Filament: DIBBS RFQs resource (filters, pipeline status, notes, DIBBS links)
          Quote worksheet ─► Batch quote CSV export (DLA format)
Notifications: daily digest of new matched RFQs
Fallback: "Upload DIBBS file" admin page feeds the same importers
```

## Data model (proposed; finalized in Phase 2 after Phase 1 layouts)

- `dibbs_files` — file_date, type (`in`/`bq`/`as`), source (`automated`/`upload`),
  original_name, sha256 (unique), row_count, status, error, imported_at.
- `dibbs_rfqs` — solicitation_number, nsn (13 digits), fsc (first 4), niin,
  nomenclature, quantity, unit_of_issue, return_by_date, set_aside, amsc,
  buyer_code, purchase_request, dibbs_url, raw_record, first_seen_file_id.
  Unique: (solicitation_number, nsn, purchase_request) — confirmed in Phase 1.
- `dibbs_approved_sources` — nsn, cage_code, part_number, source file.
- `dibbs_quote_templates` — rfq_id, raw template row (preserved verbatim), parsed fields.
- `dibbs_rfq_trackings` — company_id, rfq_id, status (`new`, `reviewing`,
  `quoting`, `quoted`, `no_bid`, `won`, `lost`), assigned_to, notes,
  unit_price, delivery_days, quoted_at; activity-logged (spatie/activitylog,
  already used in the app).
- `dibbs_rfq_matches` — company_id, rfq_id, reason, reference (e.g. MilSpecPart id,
  Manufacturer id), score.

Existing models reused: `MilSpecPart` (nsn, manufacturer_part_number,
manufacturer), `Manufacturer` (cage_code), `Supplier`, `ProcurementHistory`.
All four tables are currently empty or nearly so; Phase 4 includes a catalog import.

## Phases

| # | Phase | File | Depends on |
|---|-------|------|-----------|
| 1 | Discovery spike: layouts, access, fixtures | `phase-1-discovery-spike.md` | — |
| 2 | Ingestion foundation | `phase-2-ingestion.md` | 1 |
| 3 | RFQ tracking UI and pipeline | `phase-3-tracking-ui.md` | 2 |
| 4 | NSN catalog and approved-source matching | `phase-4-catalog-matching.md` | 2 (3 for UI badges) |
| 5 | Daily digest email | `phase-5-daily-digest.md` | 4 |
| 6 | Batch quote export | `phase-6-batch-quote-export.md` | 3 |
| 7 | Hardening and operational readiness | `phase-7-hardening.md` | 2–6 |

## Success criteria (module)

1. Each DLA business day's RFQ file is ingested automatically (or via upload)
   with zero duplicates, and failures alert by email.
2. An operator can open the DIBBS RFQs page, see today's RFQs in our FSCs, due
   soonest first, and move an RFQ through the status pipeline.
3. RFQs for NSNs in the catalog, or with an approved source we buy from, are
   flagged and appear in a morning digest.
4. Selected RFQs export to a batch quote file that DIBBS accepts on upload
   (verified once with a real upload by the operator).

## Risks

| Risk | Impact | Mitigation |
|------|--------|-----------|
| DLA blocks automated access (F5 WAF) | No auto ingestion | Upload fallback ships in Phase 2; alert on block |
| File layouts change | Bad/missing data | Layout-version check + row-level validation + parse error alert |
| Consent banner wording or flow changes | Download fails | Detect banner by form structure; fail loudly, fall back to upload |
| Batch quote format rejected by DIBBS | Export unusable | Start from DLA's own bq template rows; verify with one real upload |
| Volume (thousands of rows/day) | Slow UI, DB growth | Indexes on fsc/return_by_date/nsn; retention pruning (Phase 7) |
| Legal representations in quotes | Compliance exposure | App never submits; export requires explicit operator review |

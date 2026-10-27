# Phase 1 Findings — SAM.gov `ccode` contract (2026-10-04)

All evidence is live, read-only, from `probe.php` (raw JSON in `probe-*.json`,
analysis via `analyze.php`). Window: 90 days, `ptype=o,k,p,r`.

## 1.1 Matching semantics

| `ccode` | totalRecords | classificationCode values returned |
|---------|-------------:|------------------------------------|
| `53`    | 2,372 | `53` only |
| `5305`  | 15 | `5305` only |
| `530`   | 0 | — |
| `65`    | 49 | `65` only |
| `6515`  | 236 | `6515` only |
| `6532`  | 0 | — |
| `J053`  | 3 | `J053` only |
| `J0`    | 0 | — |

**`ccode` is an exact string match. No prefix matching.** `53` doesn't return
`5305` notices, and `5305` doesn't return `53` notices.

**Who uses which granularity** (top agencies, from `probe-fsgrows` /
`probe-psc4rows`):

- **2-character codes = DLA.** Every notice in groups 53, 59, 48, 47, 61, 43
  came from DLA (Aviation, Land, Maritime), 100% "Combined Synopsis/Solicitation",
  titles like `53--BOLT,MACHINE`. ~60% small-business set-aside (SBA/WOSB).
  Median response window 11–15 days.
- **4-character codes = everyone else.** 6515: VA network contracting offices;
  4820, 5340, 5975: Navy NAVSUP, Army AMC; mixed notice types incl. Sources
  Sought and Presolicitation. Median windows 8–31 days.

Consequence: the 2026-10-04 PSC→NAICS survey (4-char `ccode` only) measured the
non-DLA population and **missed DLA's volume entirely**. Its "11% → 72%
coverage" estimate applies to non-DLA notices only.

Note: SAM `offset` is a zero-based **page index** (the app's `SamApiClient`
already does this correctly; the first probe version didn't).

## 1.2 Volume, overlap and granularity decision

90-day totals for 2-char groups we sell into (`probe-fsg`):

| FSG | Group | 90-day notices |
|-----|-------|---------------:|
| 53 | Hardware & abrasives | 2,372 |
| 59 | Electrical/electronic components | 1,652 |
| 48 | Valves | 921 |
| 43 | Pumps & compressors | 595 |
| 61 | Electric wire, power distribution | 593 |
| 25 | Vehicular equipment components | 522 |
| 47 | Pipe, tubing, hose & fittings | 459 |
| 30 | Mechanical power transmission | 333 |
| 31 | Bearings | 169 |
| 41 | Refrigeration, AC, air circulation | 162 |
| 62 | Lights & lighting fixtures | 101 |
| 49 | Maintenance & repair shop equipment | 98 |
| 42 | Firefighting, rescue & safety | 85 |
| 39 | Materials handling | 71 |
| 81 | Containers & packaging | 56 |
| 44 | Furnace, steam, drying, air purification | 49 |
| 65 | Medical/dental | 49 |
| 45 | Plumbing, heating, waste disposal | 40 |
| 84 | Clothing & individual equipment | 25 |
| 34 | Metalworking machinery (incl. welding) | 15 |
| 51 | Hand tools | 12 |
| 80 | Paints, sealers, adhesives | 11 |
| 56 | Construction & building materials | 6 |
| 68, 91, 52 | Chemicals / lubricants / measuring tools | 3 / 3 / 2 |
| 79, 75 | Cleaning / office supplies | 0 / 0 |

**Overlap with our NAICS list** (52 codes, as expanded 2026-10-04), share of
each group's notices whose NAICS we already query:

| FSG | n | Already found by NAICS | Found only via PSC |
|-----|--:|-----------------------:|-------------------:|
| 53 | 2,372 | 2,036 (86%) | 336 |
| 48 | 921 | 849 (92%) | 72 |
| 59 | 1,652 | 824 (50%) | 828 |
| 47 | 459 | 191 (42%) | 268 |
| 43 | 595 | 203 (34%) | 392 |
| 61 | 593 | 111 (19%) | 482 |
| **6 groups** | **6,592** | **4,214 (64%)** | **2,378 (~26/day)** |

4-char groups: 6515 84%, 4820 78%, 5340 78%, 5975 61%, 4240 67%, 8145 57%
already found by NAICS.

**Decision (proposed, for user validation):** query **both** granularities.

- **2-char DLA groups, default on:** 53, 59, 48, 47, 61, 62, 30, 31, 41, 44,
  45, 42, 49, 39, 51, 52, 34, 80, 81, 84, 68, 79, 91, 65, 56 (25 terms).
- **2-char, offered but default off:** 43 (pumps/compressors: mostly ship
  compressor parts) and 25 (vehicle components). Large but furthest from a
  supply distributor's catalog.
- **4-char PSCs:** the configured list (30 codes) for VA/Navy/Army buyers.
  Zero-yield in the 360-day survey: 4245, 3030, 7935. Candidates to drop.

Relevance caveat: DLA Aviation dominates group 53 (1,463 of 2,372); many such
NSNs are restricted to approved sources. SAM.gov notices don't carry AMSC, so
relevance filtering by approved source belongs to the DIBBS module (Phase 4),
not here.

## 1.3 Quota and latency

- No rate-limit headers are returned (`X-RateLimit-*` etc. absent on every response).
- ~60 probe calls in this phase plus ~130 earlier today and several 20-code
  fetches: **no 429s**. The daily cap is unpublished; treat ~1,000/day as a
  working assumption until a 429 says otherwise.
- Latency: 0.9–1.2 s per call (2 s for 1,000-row pages).
- **Combined fetch estimate** (52 NAICS + 25 FSG + 30 PSC = 107 terms; 7–14-day
  windows fit one page per term): ~107 calls, **~2–2.5 minutes**. Within the job's
  300 s timeout, but close; Phase 2 should raise `FetchSamOpportunitiesJob::$timeout`
  (e.g. 600 s) and keep the per-term cache.
- At one scheduled fetch plus a few manual fetches per day: ~300–500 calls/day.

## Impact on Phase 2 plan

- PSC term validation must accept 2-char numeric FSGs and 4-char alphanumeric
  PSCs (e.g. `J053`), and reject 3-char codes (always 0 results).
- Config gains a separate `psc_groups` list (2-char) alongside `psc_codes`.
- Raise job timeout; keep 429 back-off as planned.
